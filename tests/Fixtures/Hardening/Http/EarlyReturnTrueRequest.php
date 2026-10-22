<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http;

use Illuminate\Support\Facades\Gate;

class EarlyReturnTrueRequest
{
    public function authorize(): bool
    {
        if ($this->user() === null) {
            return true;
        }

        return Gate::allows('widgets_store');
    }

    public function user(): object
    {
        return new \stdClass;
    }
}
