<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Auth\Access\Gate;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Integration;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Simtabi\Laranail\WaitlistKit\Integrations\Drivers;
use Simtabi\Laranail\WaitlistKit\Actions\Admin\SaveIntegration;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\IntegrationRequest;

/** Integrations: list, add, edit (a stored secret is never shown back), remove. */
final readonly class IntegrationController
{
    public function __construct(private ViewFactory $views, private Gate $gate) {}

    public function index(): View
    {
        return $this->views->make('laranail/waitlist-kit::admin.integrations.index', [
            'integrations' => Integration::query()->with('integrable')->orderBy('driver')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function store(IntegrationRequest $request, SaveIntegration $save): RedirectResponse
    {
        $save->handle(null, $request->validated());

        return redirect()->route('laranail-waitlist-kit.admin.integrations.index')->with('status', __('laranail/waitlist-kit::admin.saved'));
    }

    public function edit(Integration $integration): View
    {
        return $this->form($integration);
    }

    public function update(IntegrationRequest $request, Integration $integration, SaveIntegration $save): RedirectResponse
    {
        $save->handle($integration, $request->validated());

        return redirect()->route('laranail-waitlist-kit.admin.integrations.index')->with('status', __('laranail/waitlist-kit::admin.saved'));
    }

    public function destroy(Integration $integration): RedirectResponse
    {
        $this->gate->authorize(Abilities::MANAGE);
        $integration->delete();

        return redirect()->route('laranail-waitlist-kit.admin.integrations.index')->with('status', __('laranail/waitlist-kit::admin.deleted'));
    }

    private function form(?Integration $integration): View
    {
        return $this->views->make('laranail/waitlist-kit::admin.integrations.form', [
            'integration' => $integration,
            'drivers'     => Drivers::names(),
            'campaigns'   => Campaign::query()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }
}
