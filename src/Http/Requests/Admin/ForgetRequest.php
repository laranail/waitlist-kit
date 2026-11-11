<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

use Override;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;

final class ForgetRequest extends AdminRequest
{
    #[Override]
    protected string $ability = Abilities::ERASE;

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:254']];
    }
}
