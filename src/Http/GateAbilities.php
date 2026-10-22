<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Http;

use Dashworthy\PestPluginArchRails\Core\MethodSource;
use Illuminate\Support\Facades\Gate;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use SplFileInfo;

/**
 * Finds the abilities source code checks with a string literal, reading the syntax tree so comments never count and
 * the Gate facade is recognised however it is imported (or through Laravel's global Gate alias).
 *
 * A check is any of: Gate::allows/denies/check/authorize/inspect/any/none (a literal or an array of literals), a
 * can()/cannot()/cant() method call on any object (a user, auth()->user(), $this->user(), or a route), and a
 * 'can:ability' middleware string. Blade views cannot be parsed as PHP, so they are scanned as text for @can,
 *
 * @cannot, @canany and Gate calls.
 *
 * An ability built at runtime cannot be read from source, so whoever needs it must pass it in by other means.
 */
final class GateAbilities
{
    private const string GATE = Gate::class;

    private const array GATE_METHODS = ['allows', 'denies', 'check', 'authorize', 'inspect', 'any', 'none'];

    private const array CAN_METHODS = ['can', 'cannot', 'cant'];

    private const string BLADE_PATTERN = '/(?:@(?:can|cannot|canany|elsecan|elsecannot)|Gate::(?:allows|denies|check|authorize|inspect|any|none))\s*\(\s*(\[[^\]]*\]|\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")/';

    /**
     * @return array<int, string>
     */
    public static function inCode(string $code): array
    {
        if (! str_starts_with(ltrim($code), '<?php')) {
            $code = '<?php '.$code;
        }

        $nodes = MethodSource::parseCode($code);

        return self::inNodes($nodes === [] ? MethodSource::parseCode($code.';') : $nodes);
    }

    /**
     * Abilities checked in a Blade view, read as text.
     *
     * @return array<int, string>
     */
    public static function inBlade(string $code): array
    {
        preg_match_all(self::BLADE_PATTERN, $code, $matches);
        $abilities = [];

        foreach ($matches[1] as $argument) {
            preg_match_all('/\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)"/', $argument, $strings, PREG_SET_ORDER);

            foreach ($strings as $string) {
                $abilities[] = stripslashes(($string[2] ?? '') !== '' ? $string[2] : ($string[1] ?? ''));
            }
        }

        return array_values(array_unique(array_filter($abilities, fn (string $ability): bool => $ability !== '')));
    }

    /**
     * Abilities checked in the body of a method, read from the file that declares it, so an inherited method is read
     * from its parent.
     *
     * @return array<int, string>
     */
    public static function inMethod(ReflectionMethod $method): array
    {
        $node = MethodSource::find($method->getDeclaringClass(), $method->getName())?->node;

        return $node instanceof ClassMethod ? self::inNodes($node->stmts ?? []) : [];
    }

    /**
     * The abilities authorize() actually depends on, or null when some way out of the method ignores the check: every
     * return must be a check, its negation, or an && that contains one, unless a Gate::authorize() statement guards
     * the whole method by throwing.
     *
     * @return array<int, string>|null
     */
    public static function decidingMethod(ReflectionMethod $method): ?array
    {
        $node = MethodSource::find($method->getDeclaringClass(), $method->getName())?->node;

        if (! $node instanceof ClassMethod) {
            return null;
        }

        $guards = [];

        foreach ($node->stmts ?? [] as $statement) {
            if ($statement instanceof Expression && $statement->expr instanceof StaticCall && self::isGateCall($statement->expr) && self::methodName($statement->expr) === 'authorize') {
                $guards = [...$guards, ...self::abilitiesOf($statement->expr)];
            }
        }

        if ($guards !== []) {
            return array_values(array_unique($guards));
        }

        /** @var array<int, Return_> $returns */
        $returns = (new NodeFinder)->findInstanceOf($node->stmts ?? [], Return_::class);
        $abilities = [];

        foreach ($returns as $return) {
            if (! $return->expr instanceof Expr || ! self::decides($return->expr)) {
                return null;
            }

            $abilities = [...$abilities, ...self::inNodes([$return->expr])];
        }

        return $abilities === [] ? null : array_values(array_unique($abilities));
    }

    /**
     * Abilities checked anywhere in the PHP files beneath the directories, outside any vendor directory.
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
                $found = str_ends_with($path, '.blade.php') ? self::inBlade($code) : self::inCode($code);
                $abilities = [...$abilities, ...$found];
            }
        }

        return array_values(array_unique($abilities));
    }

    private static function decides(Expr $expr): bool
    {
        return match (true) {
            $expr instanceof BooleanNot => self::decides($expr->expr),
            $expr instanceof BooleanAnd, $expr instanceof LogicalAnd => self::decides($expr->left) || self::decides($expr->right),
            $expr instanceof MethodCall && self::methodName($expr) === 'allowed' => self::isCheck($expr->var),
            default => self::isCheck($expr),
        };
    }

    private static function isCheck(Node $node): bool
    {
        return ($node instanceof StaticCall && self::isGateCall($node))
            || (($node instanceof MethodCall || $node instanceof NullsafeMethodCall) && in_array(self::methodName($node), self::CAN_METHODS, true));
    }

    private static function isGateCall(StaticCall $call): bool
    {
        return $call->class instanceof Name
            && in_array(ltrim($call->class->toString(), '\\'), [self::GATE, 'Gate'], true)
            && in_array(self::methodName($call), self::GATE_METHODS, true);
    }

    private static function methodName(StaticCall|MethodCall|NullsafeMethodCall $call): ?string
    {
        return $call->name instanceof Identifier ? $call->name->toLowerString() : null;
    }

    /**
     * @param  array<Node>  $nodes
     * @return array<int, string>
     */
    private static function inNodes(array $nodes): array
    {
        $abilities = [];

        foreach ((new NodeFinder)->find($nodes, fn (Node $node): bool => self::isCheck($node)) as $call) {
            if ($call instanceof StaticCall || $call instanceof MethodCall || $call instanceof NullsafeMethodCall) {
                $abilities = [...$abilities, ...self::abilitiesOf($call)];
            }
        }

        /** @var array<int, String_> $strings */
        $strings = (new NodeFinder)->findInstanceOf($nodes, String_::class);

        foreach ($strings as $string) {
            if (preg_match('/^can:([^,]+)/', $string->value, $match) === 1) {
                $abilities[] = $match[1];
            }
        }

        return array_values(array_unique($abilities));
    }

    /**
     * @return array<int, string>
     */
    private static function abilitiesOf(StaticCall|MethodCall|NullsafeMethodCall $call): array
    {
        $first = $call->getArgs()[0] ?? null;

        if (! $first instanceof Arg) {
            return [];
        }

        if ($first->value instanceof String_) {
            return [$first->value->value];
        }

        if (! $first->value instanceof Array_) {
            return [];
        }

        $abilities = [];

        foreach ($first->value->items as $item) {
            if ($item->value instanceof String_) {
                $abilities[] = $item->value->value;
            }
        }

        return $abilities;
    }
}
