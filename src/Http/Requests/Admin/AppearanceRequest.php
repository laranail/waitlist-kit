<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

use Override;
use Illuminate\Validation\Rule;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;
use Simtabi\Laranail\WaitlistKit\Support\ReturnPath;
use Simtabi\Laranail\WaitlistKit\Enums\AdminAppearance;

/** The backend's colour scheme for this browser: anyone who can open the backend may choose their own. */
final class AppearanceRequest extends AdminRequest
{
    #[Override]
    protected string $ability = Abilities::VIEW;

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'appearance' => ['required', Rule::enum(AdminAppearance::class)],
            'return'     => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function appearance(): AdminAppearance
    {
        return AdminAppearance::from($this->string('appearance')->toString());
    }

    /** Back to the screen the choice was made on, never to another site; the dashboard when none was sent. */
    public function returnPath(): string
    {
        $return = $this->string('return')->toString();

        return $return === '' ? route('laranail-waitlist-kit.admin.dashboard') : ReturnPath::from($return);
    }
}
