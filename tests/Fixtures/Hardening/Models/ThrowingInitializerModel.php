<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class ThrowingInitializerModel extends Model
{
    use ThrowsOnInit;
}

trait ThrowsOnInit
{
    public function initializeThrowsOnInit(): void
    {
        throw new RuntimeException('boom');
    }
}
