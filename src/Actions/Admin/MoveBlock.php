<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions\Admin;

use Simtabi\Laranail\WaitlistKit\Models\Block;

/** Swaps a block with its neighbour above or below, renumbering its owner's blocks 0..n so positions stay dense. */
final readonly class MoveBlock
{
    public function handle(Block $block, bool $up): void
    {
        $block->getConnection()->transaction(static function () use ($block, $up): void {
            $siblings = Block::query()
                ->where('blockable_type', $block->blockable_type)
                ->where('blockable_id', $block->blockable_id)
                ->orderBy('position')->orderBy('id')
                ->get()->values()->all();

            $index = array_search($block->id, array_map(static fn (Block $b): string => $b->id, $siblings), true);
            $target = $index === false ? false : ($up ? $index - 1 : $index + 1);

            if ($index !== false && $target >= 0 && $target < count($siblings)) {
                [$siblings[$index], $siblings[$target]] = [$siblings[$target], $siblings[$index]];
            }

            foreach ($siblings as $position => $sibling) {
                if ($sibling->position !== $position) {
                    $sibling->update(['position' => $position]);
                }
            }
        });
    }
}
