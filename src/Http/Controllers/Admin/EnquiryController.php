<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Illuminate\Contracts\View\Factory as ViewFactory;

/** Contact enquiries, newest first, and one in full. */
final readonly class EnquiryController
{
    public function __construct(private ViewFactory $views) {}

    public function index(): View
    {
        return $this->views->make('laranail/waitlist-kit::admin.enquiries.index', [
            'enquiries' => Enquiry::query()->with('campaign')->orderByDesc('created_at')->paginate(50),
        ]);
    }

    public function show(Enquiry $enquiry): View
    {
        return $this->views->make('laranail/waitlist-kit::admin.enquiries.show', ['enquiry' => $enquiry]);
    }
}
