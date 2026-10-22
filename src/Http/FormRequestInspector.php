<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Http;

use Dashworthy\PestPluginArchRails\Core\Inspector;
use Dashworthy\PestPluginArchRails\Core\MethodSource;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPUnit\Architecture\Elements\ObjectDescription;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

/**
 * Decides whether every action on a controller validates through a form request rather than inline.
 *
 * An action fails when it takes a plain Illuminate\Http\Request, or validates in its own body through
 * $request->validate() or validateWithBag() on a request-typed parameter, $this or request(), $this->validateWith(),
 * Request::validate(), Validator::make(), Validator::validate() or validator(). Actions that take no request
 * at all, such as show(Widget $widget), pass: there is nothing for them to validate.
 */
final readonly class FormRequestInspector implements Inspector
{
    private const string PLAIN_REQUEST = 'Illuminate\\Http\\Request';

    private const string FORM_REQUEST = 'Illuminate\\Foundation\\Http\\FormRequest';

    private const string REQUEST_FACADE = Request::class;

    /**
     * Calls on a request or controller that validate inline.
     *
     * @var list<string>
     */
    private const array VALIDATING_METHODS = ['validate', 'validatewithbag'];

    public function __invoke(ObjectDescription $object): ?string
    {
        foreach (ControllerActions::of($object->reflectionClass) as $method) {
            $failure = $this->inspectAction($method);

            if ($failure !== null) {
                return $failure;
            }
        }

        return null;
    }

    private function inspectAction(ReflectionMethod $method): ?string
    {
        $action = $method->getName();

        foreach ($method->getParameters() as $parameter) {
            if ($this->acceptsPlainRequest($parameter->getType())) {
                return sprintf(
                    'Expecting %s() to validate through a form request, but it takes a plain %s $%s. Type-hint a FormRequest subclass instead.',
                    $action,
                    self::PLAIN_REQUEST,
                    $parameter->getName(),
                );
            }
        }

        $call = $this->inlineValidation($method);

        if ($call !== null) {
            return sprintf(
                'Expecting %s() to validate through a form request, but it validates inline with %s. Move the rules into a FormRequest subclass.',
                $action,
                $call,
            );
        }

        return null;
    }

    /**
     * Whether the type names Illuminate\Http\Request itself. Any subclass, a form request included, is accepted, and
     * the name is compared without loading the class, so the check needs no HTTP package installed.
     */
    private function acceptsPlainRequest(?ReflectionType $type): bool
    {
        $types = match (true) {
            $type instanceof ReflectionNamedType => [$type],
            $type instanceof ReflectionUnionType => $type->getTypes(),
            default => [],
        };

        return array_any($types, fn (ReflectionNamedType|ReflectionIntersectionType $named): bool => $named instanceof ReflectionNamedType && ltrim($named->getName(), '\\') === self::PLAIN_REQUEST);
    }

    /**
     * The inline validation call the action makes, described for the failure message, or null when it makes none.
     */
    private function inlineValidation(ReflectionMethod $method): ?string
    {
        $source = MethodSource::find($method->getDeclaringClass(), $method->getName());

        if (! $source instanceof MethodSource) {
            return null;
        }

        $requestVariables = $this->requestVariables($method);

        foreach ([...$source->nodesOfType(MethodCall::class), ...$source->nodesOfType(NullsafeMethodCall::class)] as $call) {
            if (! $call->name instanceof Identifier) {
                continue;
            }

            $name = $call->name->toLowerString();

            if (in_array($name, self::VALIDATING_METHODS, true) && $this->isRequestLike($call->var, $requestVariables)) {
                return '->'.$call->name->toString().'()';
            }

            if ($name === 'validatewith' && $this->isThis($call->var)) {
                return '->'.$call->name->toString().'()';
            }
        }

        foreach ($source->nodesOfType(StaticCall::class) as $call) {
            if (! $call->class instanceof Name || ! $call->name instanceof Identifier) {
                continue;
            }

            $class = $call->class->toString();
            $name = $call->name->toLowerString();

            if (in_array($class, [Validator::class, 'Validator'], true) && in_array($name, ['make', 'validate'], true)) {
                return 'Validator::'.$call->name->toString().'()';
            }

            if (in_array($class, [self::REQUEST_FACADE, 'Request'], true) && in_array($name, self::VALIDATING_METHODS, true)) {
                return 'Request::'.$call->name->toString().'()';
            }
        }

        foreach ($source->nodesOfType(FuncCall::class) as $call) {
            if ($call->name instanceof Name && $call->name->toLowerString() === 'validator') {
                return 'validator()';
            }
        }

        return null;
    }

    /**
     * Names of the action's parameters typed as a request: Illuminate\Http\Request or any subclass, form requests
     * included.
     *
     * @return list<string>
     */
    private function requestVariables(ReflectionMethod $method): array
    {
        $names = [];

        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            $types = match (true) {
                $type instanceof ReflectionNamedType => [$type],
                $type instanceof ReflectionUnionType => $type->getTypes(),
                default => [],
            };

            if (array_any($types, fn (ReflectionNamedType|ReflectionIntersectionType $named): bool => $named instanceof ReflectionNamedType
                && ! $named->isBuiltin()
                && (ClassAncestry::extends($named->getName(), self::PLAIN_REQUEST) || ClassAncestry::extends($named->getName(), self::FORM_REQUEST)))) {
                $names[] = $parameter->getName();
            }
        }

        return $names;
    }

    /**
     * Whether a validate() receiver is the request or the controller: a request-typed parameter, $this or request().
     *
     * @param  list<string>  $requestVariables
     */
    private function isRequestLike(Expr $receiver, array $requestVariables): bool
    {
        if ($receiver instanceof Variable && is_string($receiver->name)) {
            return $receiver->name === 'this' || in_array($receiver->name, $requestVariables, true);
        }

        return $receiver instanceof FuncCall
            && $receiver->name instanceof Name
            && $receiver->name->toLowerString() === 'request'
            && $receiver->getArgs() === [];
    }

    private function isThis(Expr $receiver): bool
    {
        return $receiver instanceof Variable && $receiver->name === 'this';
    }
}
