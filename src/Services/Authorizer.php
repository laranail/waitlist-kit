<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Services;

use Illuminate\Contracts\Auth\Access\Gate;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Asks the application's Gate (and so the kit's policies, or the application's own) whether an actor may do
 * something. The system actor always may: it is the application acting for itself. Everyone else, a guest included,
 * is asked as themselves, never as whoever happens to be signed in to the current request.
 */
final readonly class Authorizer
{
    public function __construct(private Gate $gate) {}

    /** @param  mixed  $arguments  the policy's model (or class), or the ability's arguments */
    public function allows(Actor $actor, string $ability, mixed $arguments = []): bool
    {
        return $actor->isSystem() || $this->gate->forUser($actor->user)->allows($ability, $arguments);
    }

    /**
     * @param mixed $arguments the policy's model (or class), or the ability's arguments
     *
     * @throws AuthorizationException
     */
    public function authorize(Actor $actor, string $ability, mixed $arguments = []): void
    {
        if (! $actor->isSystem()) {
            $this->gate->forUser($actor->user)->authorize($ability, $arguments);
        }
    }
}
