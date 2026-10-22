<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

use Illuminate\Support\Facades\Gate;

class MultilineGateRequest
{
    public function authorize(): bool
    {
        return Gate::allows(/* store */ 'widgets_store');
    }
}
