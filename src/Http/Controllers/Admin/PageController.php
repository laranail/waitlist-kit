<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Config\Repository;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Enums\BlockType;
use Simtabi\Laranail\WaitlistKit\Enums\PageStatus;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Simtabi\Laranail\WaitlistKit\Actions\Admin\UpdatePage;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\PageRequest;

/** Kit-managed pages (privacy, terms): their title per locale, their blocks, and publishing. */
final readonly class PageController
{
    public function __construct(private ViewFactory $views) {}

    public function index(): View
    {
        return $this->views->make('laranail/waitlist-kit::admin.pages.index', ['pages' => Page::query()->orderBy('slug')->get()]);
    }

    public function edit(Request $request, Page $page, Repository $config): View
    {
        $locale = $request->string('locale')->toString();
        $fallback = $config->get('app.locale');

        return $this->views->make('laranail/waitlist-kit::admin.pages.edit', [
            'page'       => $page,
            'locale'     => $locale !== '' ? $locale : (is_string($fallback) ? $fallback : 'en'),
            'statuses'   => PageStatus::cases(),
            'blocks'     => $page->blocks()->get(),
            'blockTypes' => [BlockType::Text],
        ]);
    }

    public function update(PageRequest $request, Page $page, UpdatePage $update): RedirectResponse
    {
        $locale = $request->string('locale')->toString();
        $update->handle($page, $locale, $request->string('title')->toString(), PageStatus::from($request->string('status')->toString()));

        return redirect()->route('laranail-waitlist-kit.admin.pages.edit', [$page, 'locale' => $locale])->with('status', __('laranail/waitlist-kit::admin.saved'));
    }
}
