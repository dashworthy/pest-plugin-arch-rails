<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

use Illuminate\Support\Facades\Gate;

trait AuthorizesWidgets
{
    public function authorize(): bool
    {
        return Gate::allows('widgets_store');
    }
}
