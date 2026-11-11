<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Simtabi\Laranail\WaitlistKit\Models\Admin;
use Simtabi\Laranail\WaitlistKit\Enums\AuthMode;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;

/**
 * The first administrator of a `bundled` install, so a fresh install can sign in to its backend. Runs from the
 * baseline seeder.
 *
 * It acts only in `bundled` sign-in, only while there is no administrator at all, and only for the address in
 * `seeding.admin_email`. The password is generated here, printed once to the seeding command's output, and never
 * written anywhere else: change it with `laranail::waitlist-kit.admin`. Idempotent, and safe in production.
 */
final class WaitlistKitAdminSeeder extends Seeder
{
    /** Long enough to clear AdminCommand's minimum by a wide margin. */
    public const int PASSWORD_LENGTH = 24;

    public function run(KitConfig $config): void
    {
        $email = $config->seedAdminEmail();
        if ($config->authMode() !== AuthMode::Bundled || $email === null || Admin::query()->exists()) {
            return;
        }

        $password = Str::password(self::PASSWORD_LENGTH);
        Admin::query()->create(['name' => $email, 'email' => $email, 'password' => $password]);

        $this->command?->getOutput()->writeln(sprintf(
            '  <info>Waitlist backend administrator %s created.</info> Password, shown once: <comment>%s</comment>' . PHP_EOL
            . '  Change it with: php artisan laranail::waitlist-kit.admin %s',
            $email,
            $password,
            $email,
        ));
    }
}
