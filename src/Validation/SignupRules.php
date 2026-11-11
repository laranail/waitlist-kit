<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Validation;

/**
 * What a sign-up may carry. The address is the only required field; everything else is optional and bounded, so a
 * post cannot grow a subscriber's row without limit.
 */
final class SignupRules
{
    public const array UTM = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        $rules = [
            'email'  => ['required', 'string', 'max:254', 'email:rfc'],
            'locale' => ['nullable', 'string', new Locale],
            'ref'    => ['nullable', 'string', 'max:32', 'alpha_num'],
        ];

        foreach (self::UTM as $key) {
            $rules[$key] = ['nullable', 'string', 'max:100'];
        }

        return $rules;
    }
}
