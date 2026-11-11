<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Models\Block;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Actions\Admin\MoveBlock;
use Simtabi\Laranail\WaitlistKit\Actions\Admin\SaveBlock;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\BlockRequest;

/** A campaign's or a page's blocks: add, save one locale, move, remove. Every change returns to the owner's editor. */
final readonly class BlockController
{
    public function __construct(private Gate $gate) {}

    public function storeForCampaign(BlockRequest $request, Campaign $campaign, SaveBlock $save): RedirectResponse
    {
        $save->create($campaign, $request->blockType(), $request->string('locale')->toString(), $request->validated());

        return $this->back($campaign, $request->string('locale')->toString());
    }

    public function storeForPage(BlockRequest $request, Page $page, SaveBlock $save): RedirectResponse
    {
        $save->create($page, $request->blockType(), $request->string('locale')->toString(), $request->validated());

        return $this->back($page, $request->string('locale')->toString());
    }

    public function update(BlockRequest $request, Block $block, SaveBlock $save): RedirectResponse
    {
        $save->update($block, $request->string('locale')->toString(), $request->validated());

        return $this->back($block->blockable, $request->string('locale')->toString());
    }

    public function move(Request $request, Block $block, MoveBlock $move): RedirectResponse
    {
        $this->gate->authorize(Abilities::MANAGE);
        $move->handle($block, $request->input('direction') === 'up');

        return $this->back($block->blockable, $request->string('locale')->toString());
    }

    public function destroy(Request $request, Block $block): RedirectResponse
    {
        $this->gate->authorize(Abilities::MANAGE);
        $owner = $block->blockable;
        $block->delete();

        return $this->back($owner, $request->string('locale')->toString());
    }

    private function back(?Model $owner, string $locale): RedirectResponse
    {
        $route = $owner instanceof Page ? 'laranail-waitlist-kit.admin.pages.edit' : 'laranail-waitlist-kit.admin.campaigns.edit';

        return redirect()->route($route, [$owner, 'locale' => $locale])->withFragment('blocks')->with('status', __('laranail/waitlist-kit::admin.saved'));
    }
}
