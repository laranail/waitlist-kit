<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Commands;

use Override;
use LogicException;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\WaitlistKit\Support\SubscriberExport;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

/** Writes a campaign's subscribers as CSV to a file, or to standard output. */
final class ExportCommand extends Command
{
    use SupportsNamespacedNames;

    #[Override]
    protected $signature = 'laranail::waitlist-kit.export {campaign? : The campaign\'s slug (optional with --enquiries)} {--enquiries : Export the contact enquiries instead of the subscribers} {--output= : A file to write; standard output when omitted}';

    #[Override]
    protected $description = 'Export a campaign\'s subscribers, or the enquiries, as CSV, formula cells quoted.';

    public function handle(SubscriberExport $export): int
    {
        $argument = $this->argument('campaign');
        $slug = is_string($argument) ? $argument : '';

        $enquiries = (bool) $this->option('enquiries');
        $campaign = $slug === '' ? null : Campaign::query()->where('slug', $slug)->first();
        if ($campaign === null && ($slug !== '' || ! $enquiries)) {
            $this->services->display()->error(sprintf('No campaign has the slug "%s".', $slug));

            return self::FAILURE;
        }

        $path = $this->option('output');
        $out = is_string($path) && $path !== '' ? fopen($path, 'w') : fopen('php://stdout', 'w');
        if ($out === false) {
            $this->services->display()->error('The output could not be opened for writing.');

            return self::FAILURE;
        }

        $count = $enquiries ? $export->writeEnquiries($campaign, $out) : $export->write($campaign ?? throw new LogicException('A campaign is required.'), $out);
        fclose($out);

        if (is_string($path) && $path !== '') {
            $this->services->display()->success(sprintf('%d row(s) written to %s', $count, $path));
        }

        return self::SUCCESS;
    }
}
