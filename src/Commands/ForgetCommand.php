<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Commands;

use Override;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\WaitlistKit\Actions\ForgetSubscriber;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

/** Erases one address from every campaign: an erasure request. */
final class ForgetCommand extends Command
{
    use SupportsNamespacedNames;

    #[Override]
    protected $signature = 'laranail::waitlist-kit.forget {email : The address to erase}';

    #[Override]
    protected $description = 'Erase an address, and its funnel events, from every campaign. Integrations keep their own copies.';

    public function handle(ForgetSubscriber $forget): int
    {
        $argument = $this->argument('email');
        $email = trim(is_string($argument) ? $argument : '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->services->display()->error('That is not an email address.');

            return self::FAILURE;
        }

        $erased = $forget->handle($email);
        $this->services->display()->success(sprintf('%d subscriber record(s) erased.', $erased));
        $this->services->display()->info('Integrations (an application endpoint, Brevo) keep their own copies: erase it there too.');

        return self::SUCCESS;
    }
}
