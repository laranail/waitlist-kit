<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Validation;

use Illuminate\Validation\Rule;
use Simtabi\Laranail\WaitlistKit\Enums\BlockType;

/** What a block's form may carry: its type, its locale, and each of that type's fields within its length. */
final class BlockRules
{
    /** @return array<string, list<mixed>> */
    public static function rules(?BlockType $type): array
    {
        $rules = [
            'type'    => ['required', Rule::enum(BlockType::class)],
            'locale'  => ['required', 'string', new Locale],
            'visible' => ['sometimes', 'boolean'],
        ];

        foreach ($type instanceof BlockType ? BlockFields::for($type) : [] as $name => $field) {
            $rules[$name] = ['nullable', 'string', 'max:' . $field['max']];
        }

        return $rules;
    }
}
