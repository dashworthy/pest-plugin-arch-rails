<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Controllers;

final class ValidateWithController
{
    public function update(ValidatingRequest $request): void
    {
        $this->validateWith([], $request);
    }

    private function validateWith(array $rules, ValidatingRequest $request): void {}
}
