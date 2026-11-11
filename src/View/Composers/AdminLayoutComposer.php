<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\View\Composers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Routing\UrlGenerator;
use Simtabi\Laranail\WaitlistKit\Themes\KitThemes;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeAssets;

/** Gives the backend's layout its stylesheet and script, its navigation with the current section, and who is signed in. */
final readonly class AdminLayoutComposer
{
    public const string STYLESHEET = 'resources/assets/css/admin.css';

    public function __construct(
        private ThemeAssets $assets,
        private Request $request,
        private UrlGenerator $urls,
        private Auth $auth,
        private KitConfig $config,
    ) {}

    public function compose(View $view): void
    {
        // Each section, its link, and the screens it covers (subscribers belong to campaigns).
        $sections = [
            'dashboard'    => ['dashboard', ['dashboard']],
            'campaigns'    => ['campaigns.index', ['campaigns.*', 'subscribers.*']],
            'enquiries'    => ['enquiries.index', ['enquiries.*']],
            'pages'        => ['pages.index', ['pages.*']],
            'integrations' => ['integrations.index', ['integrations.*']],
            'settings'     => ['settings.show', ['settings.*']],
        ];

        $nav = [];
        foreach ($sections as $key => [$route, $covers]) {
            $nav[] = [
                'label'   => __('laranail/waitlist-kit::admin.nav.' . $key),
                'url'     => $this->urls->route('laranail-waitlist-kit.admin.' . $route),
                'current' => $this->request->routeIs(...array_map(static fn (string $p): string => 'laranail-waitlist-kit.admin.' . $p, $covers)),
            ];
        }

        $user = $this->auth->guard($this->config->adminGuard())->user();

        $view->with([
            'adminAssets' => $this->assets->tagsFor(KitThemes::BUILD_DIRECTORY, [self::STYLESHEET, KitThemes::SCRIPT]),
            'nonce'       => $this->assets->nonce(),
            'nav'         => $nav,
            'who'         => is_object($user) && (property_exists($user, 'email') && $user->email !== null) && is_string($user->email) ? $user->email : null,
            'bundled'     => $this->config->adminGuard() === KitConfig::BUNDLED_GUARD,
        ]);
    }
}
