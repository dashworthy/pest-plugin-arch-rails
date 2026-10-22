<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review;

use Illuminate\Support\Facades\Gate;

class GuardFalseRequest
{
    public function authorize(): bool
    {
        if (request()->has('locked')) {
            return false;
        }

        return Gate::allows('widgets_store');
    }
}
