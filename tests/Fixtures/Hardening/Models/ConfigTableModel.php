<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigTableModel extends Model
{
    public function getTable(): string
    {
        return config('tables.config_table');
    }
}
