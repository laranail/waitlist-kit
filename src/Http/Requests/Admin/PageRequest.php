<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Simtabi\Laranail\WaitlistKit\Enums\PageStatus;
use Simtabi\Laranail\WaitlistKit\Validation\Locale;

final class PageRequest extends AdminRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', new Locale],
            'title'  => ['required', 'string', 'max:160'],
            'status' => ['required', Rule::enum(PageStatus::class)],
        ];
    }
}
