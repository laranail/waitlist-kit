<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers;

use Illuminate\Contracts\View\View;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeContextFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A kit-managed page (the privacy notice, the terms), in the default theme. A draft is a 404: legal text is served
 * only once published.
 */
final readonly class PageController
{
    public function __construct(private ViewFactory $views, private Application $app, private ThemeContextFactory $themes) {}

    public function show(Page $page): View
    {
        if (! $page->isPublished()) {
            throw new NotFoundHttpException;
        }

        $theme = $this->themes->for();

        return $this->views->make($theme->view('page'), [
            'theme'  => $theme,
            'page'   => $page,
            'locale' => $this->app->getLocale(),
            'blocks' => $page->blocks()->where('visible', true)->get(),
        ]);
    }
}
