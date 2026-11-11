<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Enums\PageKind;
use Simtabi\Laranail\WaitlistKit\Enums\PageStatus;

/** `<x-laranail-waitlist-kit::legal-links />`: a list of the published legal pages, unstyled, for any footer. */
final class LegalLinks extends Component
{
    public function render(): View
    {
        return view('laranail/waitlist-kit::components.legal-links', [
            'pages' => Page::query()->where('kind', PageKind::Legal)->where('status', PageStatus::Published)->orderBy('slug')->get(),
        ]);
    }
}
