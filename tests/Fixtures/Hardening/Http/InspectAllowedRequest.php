<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http;

use Illuminate\Support\Facades\Gate;

class InspectAllowedRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('widgets_store')->allowed();
    }

    public function user(): object
    {
        return new \stdClass;
    }
}
