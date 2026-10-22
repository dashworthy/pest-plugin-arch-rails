<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Mail;

final class ContentSubclassMailable
{
    public function content(): MarkdownContent
    {
        return new MarkdownContent(markdown: 'mail.welcome');
    }
}
