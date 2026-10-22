<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Mail;

use Illuminate\Mail\Mailables\Content;

final class PlainViewMailable
{
    public function content(): Content
    {
        return new Content(view: 'mail.welcome');
    }
}
