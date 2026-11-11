<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

/** A campaign's page and search settings, for one language at a time. Needs `manage`. */
final class SeoRequest extends AdminRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'locale'      => ['required', 'string', 'max:16'],
            'title'       => ['nullable', 'string', 'max:70'],
            'description' => ['nullable', 'string', 'max:200'],
            'image'       => ['nullable', 'string', 'max:2000', 'url:https'],
            'index'       => ['required', 'boolean'],
            'launch_url'  => ['nullable', 'string', 'max:2000', 'url:https'],
        ];
    }
}
