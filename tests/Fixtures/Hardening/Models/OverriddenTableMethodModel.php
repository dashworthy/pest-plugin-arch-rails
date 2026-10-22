<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models;

use Illuminate\Database\Eloquent\Model;

class OverriddenTableMethodModel extends Model
{
    public function getTable(): string
    {
        return 'legacy_things';
    }
}
