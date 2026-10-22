<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging;

use Illuminate\Mail\Mailables\Content;

final class AssignedContentMailable
{
    public function content(): Content
    {
        $content = new Content(markdown: 'mail.welcome');

        return $content;
    }
}
