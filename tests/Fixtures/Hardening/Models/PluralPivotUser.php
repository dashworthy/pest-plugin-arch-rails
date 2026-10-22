<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PluralPivotUser extends Pivot
{
    protected $table = 'plural_pivot_users';
}
