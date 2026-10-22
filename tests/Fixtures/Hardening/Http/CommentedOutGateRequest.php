<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http;

class CommentedOutGateRequest
{
    public function authorize(): bool
    {
        // return Gate::allows('widgets_store');
        return true;
    }
}
