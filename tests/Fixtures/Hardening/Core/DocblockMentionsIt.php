<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchRails\Tests\Fixtures\Hardening\Core;

/**
 * Never a subclass of anything; the docblock word must not win the line.
 */
final class DocblockMentionsIt
{
    public string $marker = 'needle-here';
}
