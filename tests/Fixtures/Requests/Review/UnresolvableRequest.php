<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review;

use Illuminate\Support\Facades\Gate;
use Missing\Permission;

class UnresolvableRequest
{
    public function authorize(): bool
    {
        return Gate::allows(Permission::Store->value);
    }
}
