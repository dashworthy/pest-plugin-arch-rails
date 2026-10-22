<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent;

use Illuminate\Database\Eloquent\Model;

class StarGuardedModel extends Model
{
    protected $guarded = ['*'];
}
