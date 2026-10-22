<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review;

use Illuminate\Support\Facades\Gate;

class NegatedAndRequest
{
    public function authorize(): bool
    {
        return ! (Gate::allows('widgets_store') && request()->has('x'));
    }
}
