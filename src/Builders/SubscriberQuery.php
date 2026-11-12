<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Services\Authorizer;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Simtabi\Laranail\WaitlistKit\Builders\Concerns\ActsAs;

/**
 * A campaign's subscribers, filtered fluently:
 *
 *     $kit->subscribers('launch')->confirmed()->search('example.com')->byPosition()->paginate(50);
 *
 * Acting as a user, every read is checked against SubscriberPolicy::viewAny. Searching escapes LIKE wildcards, so `%`
 * finds a literal percent sign and nothing else.
 */
final class SubscriberQuery
{
    use ActsAs;

    /** @var Builder<Subscriber> */
    private Builder $query;

    private bool $ordered = false;

    public function __construct(private readonly Authorizer $authorizer, Campaign $campaign)
    {
        $this->query = Subscriber::query()->where('campaign_id', $campaign->id);
    }

    public function status(SubscriberStatus|string ...$statuses): self
    {
        $values = array_map(static fn (SubscriberStatus|string $status): string => $status instanceof SubscriberStatus ? $status->value : $status, $statuses);
        $this->query->whereIn('status', $values);

        return $this;
    }

    public function pending(): self
    {
        return $this->status(SubscriberStatus::Pending);
    }

    public function confirmed(): self
    {
        return $this->status(SubscriberStatus::Confirmed);
    }

    public function invited(): self
    {
        return $this->status(SubscriberStatus::Invited);
    }

    public function converted(): self
    {
        return $this->status(SubscriberStatus::Converted);
    }

    /** Addresses containing the text, case-insensitively. */
    public function search(string $text): self
    {
        $text = mb_strtolower(trim($text));
        if ($text !== '') {
            $this->query->where('email', 'like', '%' . addcslashes($text, '%_\\') . '%');
        }

        return $this;
    }

    public function referredBy(Subscriber $referrer): self
    {
        $this->query->where('referred_by_id', $referrer->id);

        return $this;
    }

    public function latest(): self
    {
        return $this->order('created_at', 'desc');
    }

    public function oldest(): self
    {
        return $this->order('created_at', 'asc');
    }

    /** Place on the list first, then sign-up order for those without one yet. */
    public function byPosition(): self
    {
        $this->query->orderByRaw('position is null')->orderBy('position');

        return $this->order('created_at', 'asc');
    }

    /** @return Collection<int, Subscriber> */
    public function get(): Collection
    {
        return $this->ready()->get();
    }

    /** @return LengthAwarePaginator<int, Subscriber> */
    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        return $this->ready()->paginate(max(1, min($perPage, 200)));
    }

    public function count(): int
    {
        $this->authorizer->authorize($this->actor(), 'viewAny', Subscriber::class);

        return $this->query->count();
    }

    public function first(): ?Subscriber
    {
        return $this->ready()->first();
    }

    /** @return Builder<Subscriber> */
    private function ready(): Builder
    {
        $this->authorizer->authorize($this->actor(), 'viewAny', Subscriber::class);

        return $this->ordered ? $this->query : $this->query->orderByDesc('created_at')->orderByDesc('id');
    }

    /** @param  'asc'|'desc'  $direction */
    private function order(string $column, string $direction): self
    {
        $this->query->orderBy($column, $direction)->orderBy('id', $direction);
        $this->ordered = true;

        return $this;
    }
}
