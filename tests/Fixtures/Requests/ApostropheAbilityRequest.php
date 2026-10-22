<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

use Illuminate\Support\Facades\Gate;

class ApostropheAbilityRequest
{
    public function authorize(): bool
    {
        return Gate::allows("widget's_store");
    }
}
