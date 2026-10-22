<?php

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Eloquent;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class ThrowingConstructorModel extends Model
{
    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        throw new RuntimeException('boom');
    }
}
