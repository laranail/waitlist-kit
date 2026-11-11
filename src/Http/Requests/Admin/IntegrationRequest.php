<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

use Simtabi\Laranail\WaitlistKit\Validation\IntegrationRules;

final class IntegrationRequest extends AdminRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return IntegrationRules::rules($this->string('driver')->toString(), $this->route('integration') !== null);
    }
}
