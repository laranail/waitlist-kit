<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Policies;

use Illuminate\Contracts\Auth\Access\Gate;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;

/**
 * What a user may do with pages, in terms of the kit's four abilities (view, manage, export, erase), so an
 * application that redefines an ability changes every policy that uses it. Registered only when the application has
 * no policy of its own for the model.
 */
final readonly class PagePolicy
{
    public function __construct(private Gate $gate) {}

    public function viewAny(mixed $user): bool
    {
        return $this->gate->forUser($user)->allows(Abilities::VIEW);
    }

    public function view(mixed $user): bool
    {
        return $this->gate->forUser($user)->allows(Abilities::VIEW);
    }

    public function create(mixed $user): bool
    {
        return $this->gate->forUser($user)->allows(Abilities::MANAGE);
    }

    public function update(mixed $user): bool
    {
        return $this->gate->forUser($user)->allows(Abilities::MANAGE);
    }

    public function delete(mixed $user): bool
    {
        return $this->gate->forUser($user)->allows(Abilities::MANAGE);
    }
}
