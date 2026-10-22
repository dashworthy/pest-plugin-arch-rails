<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent;

use Illuminate\Database\Eloquent\Model;

class TraitUnguardedModel extends Model
{
    use UnguardsOnInit;
}
