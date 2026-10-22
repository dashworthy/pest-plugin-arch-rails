<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models;

use Illuminate\Database\Eloquent\Model;

class RequiredArgsModel extends Model
{
    protected $fillable = ['name'];

    public function __construct(string $required)
    {
        parent::__construct();
    }
}
