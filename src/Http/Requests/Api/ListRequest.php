<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;

/**
 * The query string of the API's lists: page size, filters, order and a report window. All optional; the endpoint's
 * policy, not this request, decides who may list.
 */
final class ListRequest extends FormRequest
{
    public const int MAX_PER_PAGE = 200;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
            'status'   => ['nullable', Rule::enum(SubscriberStatus::class)],
            'q'        => ['nullable', 'string', 'max:254'],
            'order'    => ['nullable', Rule::in(['latest', 'oldest', 'position'])],
            'days'     => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function perPage(): int
    {
        return $this->filled('per_page') ? $this->integer('per_page') : 25;
    }
}
