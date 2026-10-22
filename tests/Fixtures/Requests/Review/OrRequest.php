<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review;

use Illuminate\Support\Facades\Gate;

class OrRequest
{
    public function authorize(): bool
    {
        return Gate::allows('widgets_store') || Gate::allows('widgets_admin');
    }
}
