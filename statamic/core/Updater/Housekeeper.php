<?php

namespace Statamic\Updater;

use Illuminate\Console\Command;

class Housekeeper
{
    /**
     * The update class files
     *
     * @var array
     */
    private $updates = [
        Updates\MigrateAssets::class,
        Updates\MigrateTaxonomies::class,
        Updates\MigrateTaxonomyFields::class,
    ];

    /**
     * The console, if ran from the CLI.
     *
     * @var Command|NullConsole
     */
    public $console;

    /**
     * Housekeeper constructor.
     */
    public function __construct()
    {
        $this->console = new NullConsole;
    }

    /**
     * Perform the housekeeping.
     *
     * @param $version
     * @param string $previousVersion
     */
    public function clean($version, $previousVersion = '2.0.0')
    {
        foreach ($this->updates as $class) {
            $update = app($class);

            $update->console($this->console);

            if (! $update->shouldUpdate($version, $previousVersion)) {
                continue;
            }

            $this->console->getOutput()->newLine(2);
            $this->console->getOutput()->section('Running update: ' . $class);

            $update->update();
        }

        // Fire an event for devs etc.
        event('system.updated');
    }
}
