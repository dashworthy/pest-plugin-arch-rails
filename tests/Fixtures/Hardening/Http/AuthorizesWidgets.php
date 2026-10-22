<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http;

use Illuminate\Support\Facades\Gate;

trait AuthorizesWidgets
{
    public function authorize(): bool
    {
        return Gate::allows('widgets_store');
    }
}
