<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models;

use Illuminate\Database\Eloquent\Model;

abstract class GuardedBase extends Model
{
    protected $guarded = ['id'];
}
