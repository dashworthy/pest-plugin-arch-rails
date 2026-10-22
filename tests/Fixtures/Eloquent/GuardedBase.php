<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent;

use Illuminate\Database\Eloquent\Model;

abstract class GuardedBase extends Model
{
    protected $guarded = ['id'];
}
