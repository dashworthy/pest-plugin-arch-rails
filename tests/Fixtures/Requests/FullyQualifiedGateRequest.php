<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

use Illuminate\Support\Facades\Gate;

class FullyQualifiedGateRequest
{
    public function authorize(): bool
    {
        return Gate::allows('widgets_store');
    }
}
