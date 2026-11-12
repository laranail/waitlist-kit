<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Support;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Who is doing something: a signed-in user, the system (the application's own code, a command, a job), or a guest.
 *
 * Passed to actions and builders (`->as($user)`), checked by the Authorizer, and carried by every event. The system
 * actor is never checked: it is the application acting on its own behalf. A guest is checked, and holds no ability
 * unless the application's Gate gives one to a null user.
 */
final readonly class Actor
{
    private function __construct(
        public ?Authenticatable $user,
        private bool $system,
    ) {}

    /** The user, or a guest when there is none. */
    public static function from(?Authenticatable $user): self
    {
        return new self($user, system: false);
    }

    public static function system(): self
    {
        return new self(null, system: true);
    }

    public static function guest(): self
    {
        return new self(null, system: false);
    }

    public function isSystem(): bool
    {
        return $this->system;
    }

    public function isGuest(): bool
    {
        return ! $this->system && ! $this->user instanceof Authenticatable;
    }

    /** `system`, `guest`, or `user`: for logs and event listeners. */
    public function kind(): string
    {
        return match (true) {
            $this->system                            => 'system',
            ! $this->user instanceof Authenticatable => 'guest',
            default                                  => 'user',
        };
    }

    /** The user's identifier, or null for the system and guests. */
    public function id(): int|string|null
    {
        $id = $this->user?->getAuthIdentifier();

        return is_int($id) || is_string($id) ? $id : null;
    }
}
