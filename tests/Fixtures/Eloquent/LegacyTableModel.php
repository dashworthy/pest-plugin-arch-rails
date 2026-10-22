<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class LegacyTableModel extends Model
{
    protected $table = 'tbl_legacy_widgets';
}
