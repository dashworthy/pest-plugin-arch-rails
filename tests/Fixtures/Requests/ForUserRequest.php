<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests;

use Illuminate\Support\Facades\Gate;

class ForUserRequest
{
    public function authorize(): bool
    {
        return Gate::forUser(request()->user())->allows('widgets_store');
    }
}
