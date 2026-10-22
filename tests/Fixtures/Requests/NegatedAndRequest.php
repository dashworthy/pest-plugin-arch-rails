<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

use Illuminate\Support\Facades\Gate;

class NegatedAndRequest
{
    public function authorize(): bool
    {
        return ! (Gate::allows('widgets_store') && request()->has('x'));
    }
}
