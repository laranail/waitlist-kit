<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

use Simtabi\Laranail\WaitlistKit\Actions\InviteSubscribers;

/** "Invite the next N" from the subscribers screen. Needs `manage`. */
final class InviteRequest extends AdminRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['count' => ['required', 'integer', 'min:1', 'max:' . InviteSubscribers::MAX_BATCH]];
    }
}
