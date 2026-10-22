<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

use Illuminate\Support\Facades\Gate;

class OrTrueTernaryRequest
{
    public function authorize(): bool
    {
        return request()->has('x') ? true : Gate::allows('widgets_store');
    }
}
