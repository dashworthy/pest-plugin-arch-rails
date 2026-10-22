<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models;

use Illuminate\Database\Eloquent\Model;

class StarGuardedModel extends Model
{
    protected $guarded = ['*'];
}
