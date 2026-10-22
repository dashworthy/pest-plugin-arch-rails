<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging;

use Illuminate\Mail\Mailables\Content;

abstract class AbstractContentMailable
{
    abstract public function content(): Content;
}
