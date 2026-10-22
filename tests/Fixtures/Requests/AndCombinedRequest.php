<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

use Illuminate\Support\Facades\Gate;

class AndCombinedRequest
{
    public function authorize(): bool
    {
        return Gate::allows('widgets_store') && $this->user() !== null;
    }

    public function user(): object
    {
        return new \stdClass;
    }
}
