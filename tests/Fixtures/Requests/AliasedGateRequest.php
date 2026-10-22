<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

use Illuminate\Support\Facades\Gate as Permissions;

class AliasedGateRequest
{
    public function authorize(): bool
    {
        return Permissions::allows('widgets_store');
    }
}
