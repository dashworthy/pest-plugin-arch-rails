<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Messaging;

use Illuminate\Mail\Mailables\Content;

trait BuildsMarkdownContent
{
    public function content(): Content
    {
        return new Content(markdown: 'mail.welcome');
    }
}
