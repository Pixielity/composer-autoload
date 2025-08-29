<?php

namespace Pixielity\ComposerAutoload\Commands;

use Illuminate\Console\Command;
use Pixielity\ComposerAutoload\Services\AutoloadGenerator;

class GenerateAutoloadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autoload:generate 
                           {--force : Force regeneration even if up to date}
                           {--publish : Publish to bootstrap directory after generation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate custom autoload file for modules';

    protected AutoloadGenerator $generator;

    /**
     * Create a new command instance.
     */
    public function __construct(AutoloadGenerator $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating custom autoload file...');

        try {
            // Check if regeneration is needed
            if (! $this->option('force') && $this->generator->isUpToDate()) {
                $this->info('Autoload file is already up to date.');

                return Command::SUCCESS;
            }

            // Generate the autoload file
            $this->generator->generate();

            $this->info('Autoload file generated successfully at: '.$this->generator->getOutputPath());

            // Publish to bootstrap directory if requested
            if ($this->option('publish')) {
                $this->publishToBootstrap();
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to generate autoload file: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Publish the autoload file to bootstrap directory.
     */
    protected function publishToBootstrap(): void
    {
        $sourcePath = $this->generator->getOutputPath();
        $targetPath = base_path('bootstrap/autoload.php');

        if (copy($sourcePath, $targetPath)) {
            $this->info("Autoload file published to: {$targetPath}");
        } else {
            $this->error("Failed to publish autoload file to: {$targetPath}");
        }
    }
}
