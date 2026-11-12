<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Enums\BlockType;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Themes\ActiveTheme;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;
use Simtabi\Laranail\WaitlistKit\Support\FunnelReport;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeRegistry;
use Simtabi\Laranail\WaitlistKit\Actions\CreateCampaign;
use Simtabi\Laranail\WaitlistKit\Actions\Admin\DeleteCampaign;
use Simtabi\Laranail\WaitlistKit\Actions\Admin\UpdateCampaign;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\SeoRequest;
use Simtabi\Laranail\WaitlistKit\Actions\Admin\UpdateCampaignSeo;
use Simtabi\Laranail\WaitlistKit\Actions\Admin\UpdateCampaignTheme;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\ConversionRequest;
use Simtabi\Laranail\WaitlistKit\Actions\Admin\UpdateCampaignConversion;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\CampaignThemeRequest;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\StoreCampaignRequest;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\DeleteCampaignRequest;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\UpdateCampaignRequest;

/** Campaigns: list, create, edit (details, theme, blocks) and delete. */
final readonly class CampaignController
{
    public function __construct(private ViewFactory $views, private KitConfig $config) {}

    public function index(): View
    {
        return $this->views->make('laranail/waitlist-kit::admin.campaigns.index', [
            'campaigns' => Campaign::query()->withCount('subscribers')->orderBy('name')->get(),
            'limit'     => $this->config->campaignLimit(),
        ]);
    }

    public function create(): View
    {
        return $this->views->make('laranail/waitlist-kit::admin.campaigns.create', ['types' => CampaignType::cases()]);
    }

    public function store(StoreCampaignRequest $request, CreateCampaign $create): RedirectResponse
    {
        /** @var array{name: string, slug: string, type: string, locales: list<string>} $data */
        $data = $request->validated();
        $campaign = $create->handle($data['name'], $data['slug'], CampaignType::from($data['type']), $data['locales'], Actor::from($request->user()));

        return redirect()->route('laranail-waitlist-kit.admin.campaigns.edit', $campaign)->with('status', __('laranail/waitlist-kit::admin.saved'));
    }

    public function edit(Request $request, Campaign $campaign, ThemeRegistry $themes, ActiveTheme $active): View
    {
        $locale = $request->string('locale')->toString();

        return $this->views->make('laranail/waitlist-kit::admin.campaigns.edit', [
            'campaign'   => $campaign,
            'locale'     => in_array($locale, $campaign->locales, true) ? $locale : ($campaign->locales[0] ?? 'en'),
            'types'      => CampaignType::cases(),
            'statuses'   => CampaignStatus::cases(),
            'themes'     => $themes->all(),
            'theme'      => $active->for($campaign),
            'blocks'     => $campaign->blocks()->get(),
            'blockTypes' => BlockType::cases(),
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign, UpdateCampaign $update): RedirectResponse
    {
        /** @var array{name: string, slug: string, type: string, status: string, locales: list<string>, host?: ?string, launches_at?: ?string} $data */
        $data = $request->validated();
        $update->handle($campaign, $data, Actor::from($request->user()));

        return redirect()->route('laranail-waitlist-kit.admin.campaigns.edit', $campaign)->with('status', __('laranail/waitlist-kit::admin.saved'));
    }

    public function theme(CampaignThemeRequest $request, Campaign $campaign, UpdateCampaignTheme $update): RedirectResponse
    {
        $input = $request->input('settings');
        $settings = [];
        foreach (is_array($input) ? $input : [] as $key => $value) {
            $settings[(string) $key] = $value;
        }
        $update->handle($campaign, $request->string('theme')->toString(), $settings);

        return redirect()->route('laranail-waitlist-kit.admin.campaigns.edit', $campaign)->withFragment('theme')->with('status', __('laranail/waitlist-kit::admin.saved'));
    }

    /** The campaign's funnel over the last FunnelReport::DEFAULT_DAYS days. */
    public function funnel(Campaign $campaign, FunnelReport $report): View
    {
        return $this->views->make('laranail/waitlist-kit::admin.campaigns.funnel', ['campaign' => $campaign, 'funnel' => $report->for($campaign)]);
    }

    public function seo(SeoRequest $request, Campaign $campaign, UpdateCampaignSeo $update): RedirectResponse
    {
        $locale = $campaign->localeFor($request->string('locale')->toString());
        $text = static fn (string $key): ?string => $request->filled($key) ? $request->string($key)->toString() : null;
        $update->handle($campaign, $locale, $text('title'), $text('description'), $text('image'), $request->boolean('index'), $text('launch_url'));

        return redirect()->route('laranail-waitlist-kit.admin.campaigns.edit', ['campaign' => $campaign, 'locale' => $locale])->withFragment('seo')->with('status', __('laranail/waitlist-kit::admin.saved'));
    }

    public function conversion(ConversionRequest $request, Campaign $campaign, UpdateCampaignConversion $update): RedirectResponse
    {
        $update->handle($campaign, $request->toData());

        return redirect()->route('laranail-waitlist-kit.admin.campaigns.edit', $campaign)->withFragment('conversion')->with('status', __('laranail/waitlist-kit::admin.saved'));
    }

    public function destroy(DeleteCampaignRequest $request, Campaign $campaign, DeleteCampaign $delete): RedirectResponse
    {
        $delete->handle($campaign, Actor::from($request->user()));

        return redirect()->route('laranail-waitlist-kit.admin.campaigns.index')->with('status', __('laranail/waitlist-kit::admin.deleted'));
    }
}
