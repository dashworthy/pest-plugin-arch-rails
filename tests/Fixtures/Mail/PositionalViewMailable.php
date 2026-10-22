<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Mail;

use Illuminate\Mail\Mailables\Content;

final class PositionalViewMailable
{
    public function content(): Content
    {
        return new Content('mail.plain');
    }
}
