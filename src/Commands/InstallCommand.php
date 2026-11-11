<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Commands;

use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\InteractsWithConsoleWriter;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\InteractsWithConsoleServices;
use Simtabi\Laranail\Package\Tools\Commands\InstallCommand as PackageToolsInstallCommand;

/**
 * Publishes the config, optionally runs the migrations, then runs the doctor, so "installed" means "checked".
 */
final class InstallCommand extends PackageToolsInstallCommand
{
    use InteractsWithConsoleServices;
    use InteractsWithConsoleWriter;

    public const string SIGNATURE = 'laranail::waitlist-kit.install
        {--migrate : Run the kit\'s migrations after publishing the config}
        {--force : Overwrite a config file that was already published}';

    public const string DESCRIPTION = 'Publish the waitlist-kit config and theme assets, optionally migrate, and check the install.';

    public function __construct(Package $package)
    {
        parent::__construct($package, self::SIGNATURE, hidden: false);

        $this->description = self::DESCRIPTION;
        $this->setDescription(self::DESCRIPTION);

        $this->bootConsoleSupport();
    }

    public function handle(): int
    {
        $this->callSilently('vendor:publish', array_filter([
            '--tag'   => 'laranail::waitlist-kit-config',
            '--force' => (bool) $this->option('force'),
        ]));
        $this->services->display()->success('Published config/laranail/waitlist-kit.php');

        // Always forced: the theme assets are the kit's own files, rebuilt with every release.
        $this->callSilently('vendor:publish', ['--tag' => 'laranail::waitlist-kit-assets', '--force' => true]);
        $this->services->display()->success('Published the theme assets to public/vendor/waitlist-kit');

        if ((bool) $this->option('migrate')) {
            $exit = $this->call('migrate', ['--force' => true]);
            if ($exit !== self::SUCCESS) {
                return $exit;
            }
        }

        return $this->call('laranail::waitlist-kit.doctor');
    }
}
