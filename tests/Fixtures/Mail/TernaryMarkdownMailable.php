<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Mail;

use Illuminate\Mail\Mailables\Content;

final class TernaryMarkdownMailable
{
    public bool $plain = false;

    public function content(): Content
    {
        return $this->plain
            ? new Content(view: 'mail.plain')
            : new Content(markdown: 'mail.welcome');
    }
}
