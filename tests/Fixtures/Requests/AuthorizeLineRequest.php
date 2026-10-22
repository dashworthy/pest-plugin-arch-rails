<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

class AuthorizeLineRequest
{
    public function authorizeSomething(): bool
    {
        return true;
    }

    public function authorize(): bool
    {
        return true;
    }
}
