<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Mail;

use Illuminate\Mail\Mailables\Content;

final class UnrelatedMarkdownArgMailable
{
    public function content(): Content
    {
        return new Content(view: 'mail.plain', with: ['label' => $this->label(markdown: 'mail.welcome')]);
    }

    private function label(string $markdown): string
    {
        return $markdown;
    }
}
