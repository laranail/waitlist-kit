<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;

/** A backend form: allowed only to someone holding the ability it names (manage, unless it says otherwise). */
abstract class AdminRequest extends FormRequest
{
    protected string $ability = Abilities::MANAGE;

    /** Through the Gate, not `$user->can()`: an application's user model need not use Laravel's Authorizable trait. */
    public function authorize(Gate $gate): bool
    {
        return $gate->allows($this->ability);
    }
}
