<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Requests\Review;

use Illuminate\Support\Facades\Gate;

class ConstantRequest
{
    private const string ABILITY = 'widgets_store';

    public function authorize(): bool
    {
        return Gate::allows(self::ABILITY);
    }
}
