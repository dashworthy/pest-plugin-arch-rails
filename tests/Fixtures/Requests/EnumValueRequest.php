<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

use Illuminate\Support\Facades\Gate;

class EnumValueRequest
{
    public function authorize(): bool
    {
        return Gate::allows(ReviewAbility::Update->value);
    }
}
