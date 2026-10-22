<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent;

use Illuminate\Database\Eloquent\Model;

class OverriddenTableMethodModel extends Model
{
    public function getTable(): string
    {
        return 'legacy_things';
    }
}
