<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent;

use Illuminate\Database\Eloquent\Model;

abstract class UnguardedBase extends Model
{
    protected $guarded = [];
}
