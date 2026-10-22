<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging;

use Illuminate\Mail\Mailables\Content;

final class PositionalNullMarkdownMailable
{
    public function content(): Content
    {
        return new Content('mail.plain', null, null, null);
    }
}
