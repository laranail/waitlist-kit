<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions\Admin;

use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Models\Block;
use Simtabi\Laranail\WaitlistKit\Enums\BlockType;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Validation\BlockFields;

/**
 * Adds a block to the end of a campaign's or a page's blocks, or saves one locale of an existing block. Saving one
 * locale leaves the others as they were.
 */
final readonly class SaveBlock
{
    /** @param  array<string, mixed>  $input */
    public function create(Campaign|Page $owner, BlockType $type, string $locale, array $input): Block
    {
        $relation = $owner->blocks();
        $last = $relation->max('position');

        return $relation->create([
            'type'     => $type->value,
            'position' => (is_numeric($last) ? (int) $last : -1) + 1,
            'content'  => [$locale => BlockFields::toContent($type, $input)],
            'visible'  => (bool) ($input['visible'] ?? true),
        ]);
    }

    /** @param  array<string, mixed>  $input */
    public function update(Block $block, string $locale, array $input): Block
    {
        $type = BlockType::from($block->type);
        $content = $block->content;
        $content[$locale] = BlockFields::toContent($type, $input);

        $block->update(['content' => $content, 'visible' => (bool) ($input['visible'] ?? $block->visible)]);

        return $block;
    }
}
