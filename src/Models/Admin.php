<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Models;

use Override;
use Illuminate\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Simtabi\Laranail\WaitlistKit\Database\Factories\AdminFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

/**
 * An administrator of the bundled sign-in (auth.mode = bundled), for an application with no users of its own.
 * Created with `php artisan laranail::waitlist-kit.admin`, never by a form. In host mode the application's users
 * sign in instead and this table stays empty.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 */
final class Admin extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable;
    use Authorizable;
    /** @use HasFactory<AdminFactory> */
    use HasFactory;

    use Notifiable;

    #[Override]
    protected $fillable = ['name', 'email', 'password'];

    #[Override]
    protected $hidden = ['password', 'remember_token'];

    protected static function newFactory(): AdminFactory
    {
        return AdminFactory::new();
    }

    protected function baseTable(): string
    {
        return 'admins';
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
