<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review;

use Illuminate\Support\Facades\Gate;

class ClosureReturnRequest
{
    public function authorize(): bool
    {
        $skip = fn (): bool => true;
        $other = function (): bool {
            return true;
        };

        return Gate::allows('widgets_store');
    }
}
