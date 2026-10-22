<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

use Illuminate\Support\Facades\Gate;

class AuthorizeAfterReturnRequest
{
    public function authorize(): bool
    {
        if (request()->has('bypass')) {
            return true;
        }

        Gate::authorize('widgets_store');

        return true;
    }
}
