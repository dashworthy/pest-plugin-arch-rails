<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http;

use Illuminate\Support\Facades\Gate;

class AuthorizeThenTrueRequest
{
    public function authorize(): bool
    {
        Gate::authorize('widgets_store');

        return true;
    }

    public function user(): object
    {
        return new \stdClass;
    }
}
