<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Console;

final class Output
{
    public function line(string $msg): void
    {
        fwrite(STDOUT, $msg . "\n");
    }

    public function warn(string $msg): void
    {
        // "::warning::" makes the message show up as an annotation on the Actions run page
        fwrite(STDERR, (getenv('GITHUB_ACTIONS') ? "::warning::" : 'warning: ') . $msg . "\n");
    }

    public function error(string $msg): void
    {
        fwrite(STDERR, (getenv('GITHUB_ACTIONS') ? "::error::" : 'error: ') . $msg . "\n");
    }
}
