<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review;

use Illuminate\Support\Facades\Gate;

class TernaryRequest
{
    public function authorize(): bool
    {
        return request()->has('admin') ? Gate::allows('widgets_admin') : Gate::allows('widgets_store');
    }
}
