<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models;

use Illuminate\Database\Eloquent\Model;

abstract class UnguardedBase extends Model
{
    protected $guarded = [];
}
