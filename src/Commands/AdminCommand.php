<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Commands;

use Override;
use Simtabi\Laranail\WaitlistKit\Models\Admin;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

/**
 * Creates a bundled-sign-in administrator, or resets one's password. The password is always asked for, hidden and
 * twice, never taken as an argument, so it never lands in shell history or a process list.
 */
final class AdminCommand extends Command
{
    use SupportsNamespacedNames;

    public const int MIN_PASSWORD = 12;

    #[Override]
    protected $signature = 'laranail::waitlist-kit.admin {email : The administrator\'s address} {--name= : Their name; the address when omitted}';

    #[Override]
    protected $description = 'Create a bundled-sign-in administrator, or reset one\'s password.';

    public function handle(): int
    {
        $argument = $this->argument('email');
        $email = mb_strtolower(trim(is_string($argument) ? $argument : ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->services->display()->error('That is not an email address.');

            return self::FAILURE;
        }

        $answer = $this->secret('Password (at least ' . self::MIN_PASSWORD . ' characters)');
        $password = is_string($answer) ? $answer : '';
        if (mb_strlen($password) < self::MIN_PASSWORD) {
            $this->services->display()->error('The password is too short.');

            return self::FAILURE;
        }

        if ($this->secret('The same password again') !== $password) {
            $this->services->display()->error('The passwords do not match.');

            return self::FAILURE;
        }

        $name = $this->option('name');
        $admin = Admin::query()->updateOrCreate(['email' => $email], [
            'name'     => is_string($name) && $name !== '' ? $name : $email,
            'password' => $password,
        ]);

        $this->services->display()->success(sprintf('%s %s.', $admin->wasRecentlyCreated ? 'Created' : 'Updated', $email));

        return self::SUCCESS;
    }
}
