<?php

namespace Statamic\Console\Commands;

use Illuminate\Console\Command;

abstract class AbstractCommand extends Command
{
    public function checkLine($message)
    {
        $this->line("<info>[✓]</info> $message");
    }

    public function checkInfo($message)
    {
        $this->info("[✓] $message");
    }

    public function crossLine($message)
    {
        $this->line("<fg=red>[✗]</> $message");
    }
}
