<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review;

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
