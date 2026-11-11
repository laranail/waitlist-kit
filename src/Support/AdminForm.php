<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Support;

/**
 * Names the backend forms that share one page, so their control ids stay unique and a validation error is shown only
 * in the form that was submitted. A page holds its own form (`f`), one form per block (`b-<block id>`) and the
 * add-block form (`add-block`); the block editor repeats the same field names in each.
 */
final class AdminForm
{
    public const string PAGE = 'f';

    public const string ADD_BLOCK = 'add-block';

    /** The prefix of one block's form. */
    public static function block(string $blockId): string
    {
        return 'b-' . $blockId;
    }

    /** The control id of $field in the form named $prefix; a dotted name becomes hyphens (settings.primary). */
    public static function id(string $prefix, string $field): string
    {
        return $prefix . '-' . str_replace('.', '-', $field);
    }

    /** The form that was submitted, from the old input of the request that failed validation. */
    public static function submitted(mixed $block, mixed $form): string
    {
        if (is_string($block) && $block !== '') {
            return self::block($block);
        }

        return $form === self::ADD_BLOCK ? self::ADD_BLOCK : self::PAGE;
    }
}
