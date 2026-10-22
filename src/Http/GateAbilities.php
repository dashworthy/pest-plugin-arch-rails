<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Http;

use BackedEnum;
use Closure;
use Dashworthy\PestPluginArchRails\Core\MethodSource;
use Illuminate\Support\Facades\Gate;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use SplFileInfo;
use Throwable;

/**
 * Finds the permission abilities source code checks, reading the syntax tree so comments never count and the Gate
 * facade is recognised however it is imported (or through Laravel's global Gate alias).
 *
 * A check is any of: Gate::allows/denies/check/authorize/inspect/any/none, the same methods on Gate::forUser($user),
 * a can()/cannot()/cant()/canAny() method call on any object, $this->authorize() and $this->authorizeForUser(), the
 * #[Authorize] attribute, Authorize::using() and a 'can:ability' middleware string. Blade views cannot be parsed as
 * PHP, so they are scanned as text (Blade comments removed) for @can, @cannot, @canany, their @else forms and Gate
 * calls.
 *
 * The ability may be a string literal, an array of them, a class constant or an enum case's ->value; constants and
 * enum cases are resolved by loading the class. A check given a model or other argument after the ability is a policy
 * check, not a permission, so it is left out of permission matching entirely. An ability built at runtime cannot be
 * read from source, so whoever needs it must pass it in by other means.
 */
final class GateAbilities
{
    private const string GATE = Gate::class;

    private const array GATE_METHODS = ['allows', 'denies', 'check', 'authorize', 'inspect', 'any', 'none'];

    private const array CAN_METHODS = ['can', 'cannot', 'cant', 'canany'];

    /** Files without any of these words cannot contain a check, so they are never parsed. */
    private const string TRIGGER = '/gate|can|authorize/i';

    private const string BLADE_PATTERN = '/(?:@(?:can|cannot|canany|elsecan|elsecannot|elsecanany)|Gate::(?:allows|denies|check|authorize|inspect|any|none))\s*\(\s*(\[[^\]]*\]|\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")\s*([,)]?)/';

    /**
     * Permission abilities checked in a PHP file's contents or in a bare code snippet (which gets an opening tag).
     *
     * @return array<int, string>
     */
    public static function inCode(string $code): array
    {
        if (stripos($code, '<?') === false) {
            $nodes = MethodSource::parseCode('<?php '.$code);

            return self::inNodes($nodes === [] ? MethodSource::parseCode('<?php '.$code.';') : $nodes);
        }

        return self::inNodes(MethodSource::parseCode($code));
    }

    /**
     * Permission abilities checked in a Blade view, read as text.
     *
     * @return array<int, string>
     */
    public static function inBlade(string $code): array
    {
        $code = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $code);
        preg_match_all(self::BLADE_PATTERN, $code, $matches, PREG_SET_ORDER);
        $abilities = [];

        foreach ($matches as $match) {
            if ($match[2] === ',') {
                continue;
            }

            preg_match_all('/\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)"/', $match[1], $strings, PREG_SET_ORDER);

            foreach ($strings as $string) {
                $doubleQuoted = ($string[2] ?? '') !== '';

                if ($doubleQuoted && str_contains($string[2], '$')) {
                    continue;
                }

                $abilities[] = $doubleQuoted ? stripcslashes($string[2]) : str_replace(['\\\\', "\\'"], ['\\', "'"], $string[1] ?? '');
            }
        }

        return array_values(array_unique(array_filter($abilities, fn (string $ability): bool => $ability !== '')));
    }

    /**
     * What authorize() depends on, or null when some way out of the method ignores the check.
     *
     * Either a Gate::authorize() statement at the top level of the method, before any return statement (including
     * one nested in an earlier block), guards the whole method by throwing; or every return decides: it is a check,
     * `false`, a negation, an && with a deciding side, an || or ternary whose sides all decide.
     *
     * `unresolved` is true when a deciding check names its ability in a way that cannot be read from source.
     *
     * @return array{abilities: array<int, string>, unresolved: bool}|null
     */
    public static function decidingMethod(ReflectionMethod $method): ?array
    {
        $class = $method->getDeclaringClass();
        $source = MethodSource::find($class, $method->getName());

        if (! $source instanceof MethodSource) {
            return null;
        }

        $returns = $source->nodesOfType(Return_::class);
        $firstReturn = $returns === [] ? PHP_INT_MAX : min(array_map(fn (Return_ $return): int => $return->getStartFilePos(), $returns));
        $guards = [];
        $unresolved = false;

        foreach ($source->node->stmts ?? [] as $statement) {
            if ($statement->getEndFilePos() >= $firstReturn) {
                break;
            }

            if ($statement instanceof Expression && $statement->expr instanceof StaticCall && self::isGateCall($statement->expr) && self::methodName($statement->expr) === 'authorize') {
                [$found, $policy, $missing] = self::abilitiesOf($statement->expr, $class->getName());
                $guards = [...$guards, ...$found, ...$policy];
                $unresolved = $unresolved || $missing;
                $guarded = true;
            }
        }

        if (isset($guarded)) {
            return ['abilities' => array_values(array_unique($guards)), 'unresolved' => $unresolved];
        }

        $abilities = [];
        $checked = false;

        foreach ($returns as $return) {
            if (! $return->expr instanceof Expr || ! self::decides($return->expr)) {
                return null;
            }

            foreach (self::checksIn([$return->expr]) as $call) {
                [$found, $policy, $missing] = self::abilitiesOf($call, $class->getName());
                $abilities = [...$abilities, ...$found, ...$policy];
                $unresolved = $unresolved || $missing;
                $checked = true;
            }
        }

        return $checked ? ['abilities' => array_values(array_unique($abilities)), 'unresolved' => $unresolved] : null;
    }

    /**
     * Permission abilities checked anywhere in the PHP files beneath the directories, outside any vendor directory.
     *
     * @param  array<int, string>  $directories
     * @return array<int, string>
     */
    public static function inDirectories(array $directories): array
    {
        $abilities = [];

        foreach ($directories as $directory) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS));

            /**
             * A package installed on its own has a vendor directory beneath the one being searched, and an ability
             * named in a dependency is not one this code checks.
             *
             * @var SplFileInfo $file
             */
            foreach ($files as $file) {
                $path = $file->getPathname();

                if (! $file->isFile() || $file->getExtension() !== 'php' || str_contains($path, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                    continue;
                }

                $code = (string) file_get_contents($path);

                if (preg_match(self::TRIGGER, $code) !== 1) {
                    continue;
                }

                $found = str_ends_with($path, '.blade.php') ? self::inBlade($code) : self::inNodes(MethodSource::parseCode($code));
                $abilities = [...$abilities, ...$found];
            }
        }

        return array_values(array_unique($abilities));
    }

    /**
     * Whether the expression can only grant access through a check. Under negation && behaves as || and the
     * reverse, and `false` stops deciding (`! false` grants).
     */
    private static function decides(Expr $expr, bool $negated = false): bool
    {
        if ($expr instanceof BooleanNot) {
            return self::decides($expr->expr, ! $negated);
        }

        $isAnd = $expr instanceof BooleanAnd || $expr instanceof LogicalAnd;
        $isOr = $expr instanceof BooleanOr || $expr instanceof LogicalOr;

        if ($isAnd || $isOr) {
            /** @var BooleanAnd|LogicalAnd|BooleanOr|LogicalOr $expr */
            return ($isAnd !== $negated)
                ? self::decides($expr->left, $negated) || self::decides($expr->right, $negated)
                : self::decides($expr->left, $negated) && self::decides($expr->right, $negated);
        }

        if ($expr instanceof Ternary) {
            return self::decides($expr->if ?? $expr->cond, $negated) && self::decides($expr->else, $negated);
        }

        if ($expr instanceof ConstFetch) {
            return ! $negated && $expr->name->toLowerString() === 'false';
        }

        if ($expr instanceof MethodCall && self::methodName($expr) === 'allowed') {
            return self::isCheck($expr->var);
        }

        return self::isCheck($expr);
    }

    private static function isCheck(Node $node): bool
    {
        if ($node instanceof StaticCall) {
            return self::isGateCall($node) || self::isAuthorizeUsing($node);
        }

        if (! $node instanceof MethodCall && ! $node instanceof NullsafeMethodCall) {
            return false;
        }

        $name = self::methodName($node);

        return in_array($name, self::CAN_METHODS, true)
            || (in_array($name, ['authorize', 'authorizeforuser'], true) && $node->var instanceof Variable && $node->var->name === 'this')
            || (in_array($name, self::GATE_METHODS, true) && $node->var instanceof StaticCall && self::isGateClass($node->var) && self::methodName($node->var) === 'foruser');
    }

    private static function isGateClass(StaticCall $call): bool
    {
        return $call->class instanceof Name && in_array(ltrim($call->class->toString(), '\\'), [self::GATE, 'Gate'], true);
    }

    private static function isGateCall(StaticCall $call): bool
    {
        return self::isGateClass($call) && in_array(self::methodName($call), self::GATE_METHODS, true);
    }

    private static function isAuthorizeUsing(StaticCall $call): bool
    {
        return $call->class instanceof Name && self::isAuthorizeName($call->class) && self::methodName($call) === 'using';
    }

    private static function isAuthorizeName(Name $name): bool
    {
        $name = ltrim($name->toString(), '\\');

        return $name === 'Authorize' || str_ends_with($name, '\\Authorize');
    }

    private static function methodName(StaticCall|MethodCall|NullsafeMethodCall $call): ?string
    {
        return $call->name instanceof Identifier ? $call->name->toLowerString() : null;
    }

    /**
     * Check calls and #[Authorize] attributes among the nodes.
     *
     * @param  array<Node>  $nodes
     * @return array<int, StaticCall|MethodCall|NullsafeMethodCall|Attribute>
     */
    private static function checksIn(array $nodes): array
    {
        /** @var array<int, StaticCall|MethodCall|NullsafeMethodCall|Attribute> $checks */
        $checks = (new NodeFinder)->find($nodes, fn (Node $node): bool => self::isCheck($node)
            || ($node instanceof Attribute && self::isAuthorizeName($node->name)));

        return $checks;
    }

    /**
     * @param  array<Node>  $nodes
     * @return array<int, string>
     */
    private static function inNodes(array $nodes): array
    {
        $visitor = new class(self::permissionsOf(...)) extends NodeVisitorAbstract
        {
            /** @var list<?string> */
            private array $classes = [];

            /** @var list<string> */
            public array $abilities = [];

            /** @param Closure(Node, ?string): array<int, string> $permissionsOf */
            public function __construct(private readonly Closure $permissionsOf) {}

            public function enterNode(Node $node): null
            {
                if ($node instanceof ClassLike) {
                    $this->classes[] = $node->namespacedName?->toString();
                }

                if ($node instanceof String_ && preg_match('/^can:([^,]+)$/', $node->value, $match) === 1) {
                    $this->abilities[] = $match[1];
                }

                $self = $this->classes === [] ? null : $this->classes[array_key_last($this->classes)];
                $this->abilities = [...$this->abilities, ...($this->permissionsOf)($node, $self)];

                return null;
            }

            public function leaveNode(Node $node): null
            {
                if ($node instanceof ClassLike) {
                    array_pop($this->classes);
                }

                return null;
            }
        };

        new NodeTraverser($visitor)->traverse($nodes);

        return array_values(array_unique($visitor->abilities));
    }

    /**
     * The permissions a node checks, or none when it is not a check.
     *
     * @return array<int, string>
     */
    private static function permissionsOf(Node $node, ?string $self): array
    {
        $isCheck = self::isCheck($node) || ($node instanceof Attribute && self::isAuthorizeName($node->name));

        return $isCheck && ($node instanceof StaticCall || $node instanceof MethodCall || $node instanceof NullsafeMethodCall || $node instanceof Attribute)
            ? self::abilitiesOf($node, $self)[0]
            : [];
    }

    /**
     * The check's abilities: [permissions, policy abilities, whether some ability could not be read].
     *
     * @return array{0: array<int, string>, 1: array<int, string>, 2: bool}
     */
    private static function abilitiesOf(StaticCall|MethodCall|NullsafeMethodCall|Attribute $call, ?string $self): array
    {
        $offset = ($call instanceof MethodCall || $call instanceof NullsafeMethodCall) && self::methodName($call) === 'authorizeforuser' ? 1 : 0;
        $args = array_values(array_filter($call instanceof Attribute ? $call->args : $call->getRawArgs(), fn (Node $arg): bool => $arg instanceof Arg));
        $first = $args[$offset] ?? null;

        if (! $first instanceof Arg) {
            return [[], [], true];
        }

        $items = $first->value instanceof Array_
            ? array_map(fn (?Node\ArrayItem $item): ?Expr => $item?->value, $first->value->items)
            : [$first->value];
        $abilities = [];
        $unresolved = false;

        foreach ($items as $item) {
            $ability = $item instanceof Expr ? self::resolve($item, $self) : null;

            if ($ability === null) {
                $unresolved = true;
            } else {
                $abilities[] = $ability;
            }
        }

        $isPolicy = count($args) > $offset + 1;

        return $isPolicy ? [[], $abilities, $unresolved] : [$abilities, [], $unresolved];
    }

    /**
     * A string literal, a class constant or an enum case's ->value, or null when it cannot be read from source.
     */
    private static function resolve(Expr $expr, ?string $self): ?string
    {
        if ($expr instanceof String_) {
            return $expr->value;
        }

        if (($expr instanceof PropertyFetch || $expr instanceof NullsafePropertyFetch) && $expr->name instanceof Identifier && $expr->name->toString() === 'value') {
            $case = self::constant($expr->var, $self);

            return $case instanceof BackedEnum && is_string($case->value) ? $case->value : null;
        }

        $value = self::constant($expr, $self);

        return is_string($value) ? $value : null;
    }

    private static function constant(Expr $expr, ?string $self): mixed
    {
        if (! $expr instanceof ClassConstFetch || ! $expr->class instanceof Name || ! $expr->name instanceof Identifier) {
            return null;
        }

        $class = $expr->class->toString();

        if (in_array(strtolower($class), ['self', 'static'], true)) {
            $class = $self;
        }

        if ($class === null || strtolower($class) === 'parent') {
            return null;
        }

        try {
            if (! class_exists($class) && ! interface_exists($class)) {
                return null;
            }

            $constant = new ReflectionClass($class)->getReflectionConstant($expr->name->toString());

            return $constant === false ? null : $constant->getValue();
        } catch (Throwable) {
            return null;
        }
    }
}
