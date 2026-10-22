<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

use Illuminate\Support\Facades\Gate;

class DeniesGateRequest
{
    public function authorize(): bool
    {
        return ! Gate::denies('widgets_store');
    }
}
