<?php

namespace Statamic\Console\Commands;

use Statamic\API\Pattern;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Helper\DescriptorHelper;

class VersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Displays the Statamic version.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line(sprintf('<info>Statamic</info> version <comment>%s</comment>', STATAMIC_VERSION));
    }
}
