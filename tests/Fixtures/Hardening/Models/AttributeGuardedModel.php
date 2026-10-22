<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

#[Guarded(['id'])]
class AttributeGuardedModel extends Model {}
