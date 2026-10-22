<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent;

use Illuminate\Database\Eloquent\Model;

class TraitFillableModel extends Model
{
    use FillsOnInit;

    protected $guarded = [];
}
