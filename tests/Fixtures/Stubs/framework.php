<?php

declare(strict_types=1);

/*
 * Stand-ins for framework classes this package does not install, loaded only by the tests that need them.
 */

namespace Illuminate\Routing {
    if (! class_exists(Controller::class)) {
        abstract class Controller
        {
            public function callAction(string $method, array $parameters): mixed
            {
                return $this->{$method}(...array_values($parameters));
            }
        }
    }
}

namespace Illuminate\Foundation\Auth\Access {
    if (! trait_exists(AuthorizesRequests::class)) {
        trait AuthorizesRequests
        {
            public function authorize(mixed $ability, mixed $arguments = []): mixed
            {
                return null;
            }
        }
    }
}

namespace Illuminate\Foundation\Validation {
    if (! trait_exists(ValidatesRequests::class)) {
        trait ValidatesRequests
        {
            public function validate(object $request, array $rules): array
            {
                return [];
            }

            public function validateWithBag(string $errorBag, object $request, array $rules): array
            {
                return [];
            }
        }
    }
}
