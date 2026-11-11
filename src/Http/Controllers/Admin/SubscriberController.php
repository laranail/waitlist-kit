<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Auth\Access\Gate;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Simtabi\Laranail\WaitlistKit\Actions\ForgetSubscriber;
use Simtabi\Laranail\WaitlistKit\Support\SubscriberExport;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\ForgetRequest;

/** A campaign's subscribers: search and filter, remove one, export the list (`export`), erase an address (`erase`). */
final readonly class SubscriberController
{
    public const int PER_PAGE = 50;

    public function __construct(private ViewFactory $views, private Gate $gate) {}

    public function index(Request $request, Campaign $campaign): View
    {
        $search = mb_strtolower(trim($request->string('q')->toString()));
        $status = SubscriberStatus::tryFrom($request->string('status')->toString());

        $subscribers = $campaign->subscribers()
            ->when($search !== '', static fn ($query) => $query->where('email', 'like', '%' . addcslashes($search, '%_\\') . '%'))
            ->when($status instanceof SubscriberStatus, static fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)->withQueryString();

        return $this->views->make('laranail/waitlist-kit::admin.subscribers.index', [
            'campaign'    => $campaign,
            'subscribers' => $subscribers,
            'search'      => $search,
            'status'      => $status,
            'statuses'    => SubscriberStatus::cases(),
        ]);
    }

    public function destroy(Campaign $campaign, Subscriber $subscriber): RedirectResponse
    {
        $this->gate->authorize(Abilities::MANAGE);
        abort_unless($subscriber->campaign_id === $campaign->id, 404);
        $subscriber->delete();

        return redirect()->route('laranail-waitlist-kit.admin.subscribers.index', $campaign)->with('status', __('laranail/waitlist-kit::admin.removed'));
    }

    public function export(Campaign $campaign, SubscriberExport $export): StreamedResponse
    {
        $this->gate->authorize(Abilities::EXPORT);

        return new StreamedResponse(static function () use ($campaign, $export): void {
            $out = fopen('php://output', 'w');
            if ($out !== false) {
                $export->write($campaign, $out);
                fclose($out);
            }
        }, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $campaign->slug . '-subscribers.csv"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    public function forget(ForgetRequest $request, ForgetSubscriber $forget): RedirectResponse
    {
        $erased = $forget->handle($request->string('email')->toString());

        return redirect()->back()->with('status', __('laranail/waitlist-kit::admin.erased', $erased));
    }
}
