<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http;

class UserCanRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('widgets_store');
    }

    public function user(): object
    {
        return new \stdClass;
    }
}
