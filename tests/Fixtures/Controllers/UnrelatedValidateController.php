<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

use Illuminate\Support\Facades\Auth;

final class UnrelatedValidateController
{
    public function __construct(private readonly object $coupons) {}

    public function update(object $coupon): void
    {
        $coupon->validate();
        $this->coupons->validate();
        Auth::guard()->validate([]);
        auth()->validate([]);
    }
}
