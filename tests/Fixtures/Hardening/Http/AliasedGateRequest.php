<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Http;

use Illuminate\Support\Facades\Gate as Permissions;

class AliasedGateRequest
{
    public function authorize(): bool
    {
        return Permissions::allows('widgets_store');
    }
}
