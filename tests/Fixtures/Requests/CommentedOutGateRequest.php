<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

class CommentedOutGateRequest
{
    public function authorize(): bool
    {
        // return Gate::allows('widgets_store');
        return true;
    }
}
