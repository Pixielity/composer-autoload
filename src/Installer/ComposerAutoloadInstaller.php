<?php

declare(strict_types=1);

namespace Pixielity\ComposerAutoload\Installer;

use Composer\IO\IOInterface;
use Composer\Script\Event;

/**
 * Class ComposerAutoloadInstaller
 *
 * Handles automatic installation of ComposerAutoload module files
 */
class ComposerAutoloadInstaller
{
    /**
     * Install the ComposerAutoload module via Composer script
     */
    public static function install(Event $event): void
    {
        $io = $event->getIO();
        $composer = $event->getComposer();
        $vendorDir = $composer->getConfig()->get('vendor-dir');

        // Find the Laravel project root (go up from vendor directory)
        $projectRoot = dirname($vendorDir);

        $io->write('<info>Setting up ComposerAutoload module...</info>');

        $installer = new self;

        try {
            // Install bootstrap autoloader
            $installer->installBootstrapAutoloader($projectRoot, $io);

            // Update entry points
            $installer->updateEntryPoints($projectRoot, $io);

            // Install service provider
            $installer->installServiceProvider($projectRoot, $io);

            $io->write('<info>✅ ComposerAutoload module installed successfully!</info>');
        } catch (\Exception $e) {
            $io->writeError('<error>❌ Failed to install ComposerAutoload module: '.$e->getMessage().'</error>');
        }
    }

    /**
     * Standalone installation method (without Composer event)
     */
    public static function installStandalone(?string $projectRoot = null): void
    {
        if ($projectRoot === null) {
            $projectRoot = getcwd();
        }

        echo "🚀 Installing ComposerAutoload module...\n";

        $installer = new self;

        try {
            $mockIO = new class
            {
                public function write($message)
                {
                    echo strip_tags($message)."\n";
                }

                public function writeError($message)
                {
                    echo 'ERROR: '.strip_tags($message)."\n";
                }
            };

            // Install bootstrap autoloader
            $installer->installBootstrapAutoloader($projectRoot, $mockIO);

            // Update entry points
            $installer->updateEntryPoints($projectRoot, $mockIO);

            // Install service provider
            $installer->installServiceProvider($projectRoot, $mockIO);

            echo "\n✅ ComposerAutoload module installed successfully!\n";
        } catch (\Exception $e) {
            echo "\n❌ Failed to install ComposerAutoload module: ".$e->getMessage()."\n";

            throw $e;
        }
    }

    /**
     * Install the bootstrap autoloader file
     *
     * @param  IOInterface|object  $io
     */
    protected function installBootstrapAutoloader(string $projectRoot, $io): void
    {
        $bootstrapDir = $projectRoot.'/bootstrap';
        $autoloadFile = $bootstrapDir.'/autoload.php';

        if (! is_dir($bootstrapDir)) {
            mkdir($bootstrapDir, 0755, true);
        }

        // Check if bootstrap/autoload.php already exists and contains our code
        if (file_exists($autoloadFile)) {
            $content = file_get_contents($autoloadFile);
            if (strpos($content, 'ConfigurableDiscoveryManager') !== false) {
                $io->write('<comment>Bootstrap autoloader already exists and is configured.</comment>');

                return;
            }
        }

        $autoloadTemplate = $this->getBootstrapAutoloadTemplate();

        if (file_put_contents($autoloadFile, $autoloadTemplate)) {
            $io->write('<info>✅ Created bootstrap/autoload.php</info>');
        } else {
            throw new \RuntimeException('Failed to create bootstrap/autoload.php');
        }
    }

    /**
     * Update entry points to include custom autoloader
     *
     * @param  IOInterface|object  $io
     */
    protected function updateEntryPoints(string $projectRoot, $io): void
    {
        // Update bootstrap/app.php (Laravel 11 pattern)
        $this->updateBootstrapApp($projectRoot, $io);

        // Update direct entry points for maximum compatibility
        $this->updatePublicIndex($projectRoot, $io);
        $this->updateArtisan($projectRoot, $io);
    }

    /**
     * Update public/index.php to require custom autoloader
     *
     * @param  IOInterface|object  $io
     */
    protected function updatePublicIndex(string $projectRoot, $io): void
    {
        $indexFile = $projectRoot.'/public/index.php';

        if (! file_exists($indexFile)) {
            $io->write('<comment>public/index.php not found, skipping.</comment>');

            return;
        }

        $content = file_get_contents($indexFile);

        // Check if already updated
        if (strpos($content, "require_once __DIR__.'/../bootstrap/autoload.php';") !== false) {
            $io->write('<comment>public/index.php already configured.</comment>');

            return;
        }

        // Add autoloader require after the opening PHP tag and LARAVEL_START define
        $definePos = strpos($content, "define('LARAVEL_START', microtime(true));");
        if ($definePos !== false) {
            $lineEnd = strpos($content, "\n", $definePos);
            if ($lineEnd !== false) {
                $autoloadLine = "\n\n// Load custom autoloader\nrequire_once __DIR__.'/../bootstrap/autoload.php';\n";
                $updatedContent = substr_replace($content, $autoloadLine, $lineEnd + 1, 0);

                if (file_put_contents($indexFile, $updatedContent)) {
                    $io->write('<info>✅ Updated public/index.php</info>');
                } else {
                    $io->write('<comment>Failed to update public/index.php</comment>');
                }
            }
        } else {
            $io->write('<comment>Could not find insertion point in public/index.php</comment>');
        }
    }

    /**
     * Update artisan command to require custom autoloader
     *
     * @param  IOInterface|object  $io
     */
    protected function updateArtisan(string $projectRoot, $io): void
    {
        $artisanFile = $projectRoot.'/artisan';

        if (! file_exists($artisanFile)) {
            $io->write('<comment>artisan file not found, skipping.</comment>');

            return;
        }

        $content = file_get_contents($artisanFile);

        // Check if already updated
        if (strpos($content, "require_once __DIR__.'/bootstrap/autoload.php';") !== false) {
            $io->write('<comment>artisan already configured.</comment>');

            return;
        }

        // Add autoloader require after the opening PHP tag and LARAVEL_START define
        $definePos = strpos($content, "define('LARAVEL_START', microtime(true));");
        if ($definePos !== false) {
            $lineEnd = strpos($content, "\n", $definePos);
            if ($lineEnd !== false) {
                $autoloadLine = "\n\n// Load custom autoloader\nrequire_once __DIR__.'/bootstrap/autoload.php';\n";
                $updatedContent = substr_replace($content, $autoloadLine, $lineEnd + 1, 0);

                if (file_put_contents($artisanFile, $updatedContent)) {
                    $io->write('<info>✅ Updated artisan</info>');
                } else {
                    $io->write('<comment>Failed to update artisan</comment>');
                }
            }
        } else {
            $io->write('<comment>Could not find insertion point in artisan</comment>');
        }
    }

    /**
     * Update bootstrap/app.php to require custom autoloader
     *
     * @param  IOInterface|object  $io
     */
    protected function updateBootstrapApp(string $projectRoot, $io): void
    {
        $appFile = $projectRoot.'/bootstrap/app.php';

        if (! file_exists($appFile)) {
            $io->write('<comment>bootstrap/app.php not found, skipping.</comment>');

            return;
        }

        $content = file_get_contents($appFile);

        // Check if already updated
        if (strpos($content, 'require_once __DIR__.\'/autoload.php\';') !== false) {
            $io->write('<comment>bootstrap/app.php already configured.</comment>');

            return;
        }

        // Add autoloader require at the beginning after the opening PHP tag
        $phpOpenTag = '<?php';
        $autoloadLine = "\n\n// Load custom autoloader\nrequire_once __DIR__.'/autoload.php';\n";

        $updatedContent = str_replace(
            $phpOpenTag,
            $phpOpenTag.$autoloadLine,
            $content
        );

        if ($updatedContent !== $content && file_put_contents($appFile, $updatedContent)) {
            $io->write('<info>✅ Updated bootstrap/app.php</info>');
        } else {
            $io->write('<comment>No changes needed for bootstrap/app.php</comment>');
        }
    }

    /**
     * Install service provider registration
     *
     * @param  IOInterface|object  $io
     */
    protected function installServiceProvider(string $projectRoot, $io): void
    {
        $providersFile = $projectRoot.'/bootstrap/providers.php';

        if (! file_exists($providersFile)) {
            $io->write('<comment>bootstrap/providers.php not found, skipping service provider registration.</comment>');

            return;
        }

        $content = file_get_contents($providersFile);

        // Check if already registered
        if (strpos($content, 'ComposerAutoloadServiceProvider') !== false) {
            $io->write('<comment>Service provider already registered.</comment>');

            return;
        }

        // Add service provider to the array
        $providerLine = "    Pixielity\\ComposerAutoload\\Providers\\ComposerAutoloadServiceProvider::class,\n";

        $updatedContent = str_replace(
            "return [\n",
            "return [\n".$providerLine,
            $content
        );

        if ($updatedContent !== $content && file_put_contents($providersFile, $updatedContent)) {
            $io->write('<info>✅ Registered service provider in bootstrap/providers.php</info>');
        }
    }

    /**
     * Get bootstrap autoload template from stub file
     */
    protected function getBootstrapAutoloadTemplate(): string
    {
        $stubPath = __DIR__.'/../../stubs/autoload.stub';

        if (file_exists($stubPath)) {
            return file_get_contents($stubPath);
        }

        throw new \RuntimeException('Autoload stub file not found: '.$stubPath);
    }
}
