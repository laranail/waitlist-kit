<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Builders;

use LogicException;
use DateTimeInterface;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Services\Authorizer;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;
use Simtabi\Laranail\WaitlistKit\Actions\CreateCampaign;
use Simtabi\Laranail\WaitlistKit\Support\CampaignLookup;
use Simtabi\Laranail\WaitlistKit\Builders\Concerns\ActsAs;
use Simtabi\Laranail\WaitlistKit\Validation\CampaignRules;
use Simtabi\Laranail\WaitlistKit\Actions\Admin\DeleteCampaign;
use Simtabi\Laranail\WaitlistKit\Actions\Admin\UpdateCampaign;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

/**
 * Creates, changes or deletes a campaign from code:
 *
 *     $kit->campaign()->named('Launch')->slug('launch')->type(CampaignType::ComingSoon)->locales('en', 'es')->live()->create();
 *     $kit->campaign('launch')->named('Launch list')->closed()->as($user)->save();
 *
 * A new campaign passes CampaignRules::create() and the install's campaign limit (CreateCampaign); a change passes
 * CampaignRules::update(). Acting as a user, each is checked against CampaignPolicy (create, update, delete).
 */
final class CampaignBuilder
{
    use ActsAs;

    private ?Campaign $campaign = null;

    private ?string $name = null;

    private ?string $slug = null;

    /** Kept as given until validated, so a bad value is a validation error, not a ValueError. */
    private ?string $type = null;

    /** @var list<string>|null */
    private ?array $locales = null;

    private ?string $status = null;

    /**
     * Set only when asked for, so save() can tell "clear it" (null) from "leave it".
     *
     * @var array<string, ?string>
     */
    private array $optional = [];

    public function __construct(
        private readonly CreateCampaign $create,
        private readonly UpdateCampaign $update,
        private readonly DeleteCampaign $delete,
        private readonly Authorizer $authorizer,
        private readonly ValidationFactory $validator,
    ) {}

    /** Work on an existing campaign (its model, id or slug) instead of a new one. */
    public function of(Campaign|string $campaign): self
    {
        $this->campaign = CampaignLookup::resolve($campaign);

        return $this;
    }

    public function named(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function slug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function type(CampaignType|string $type): self
    {
        $this->type = $type instanceof CampaignType ? $type->value : $type;

        return $this;
    }

    public function locales(string ...$locales): self
    {
        $this->locales = array_values($locales);

        return $this;
    }

    public function status(CampaignStatus|string $status): self
    {
        $this->status = $status instanceof CampaignStatus ? $status->value : $status;

        return $this;
    }

    public function draft(): self
    {
        return $this->status(CampaignStatus::Draft);
    }

    public function live(): self
    {
        return $this->status(CampaignStatus::Live);
    }

    public function closed(): self
    {
        return $this->status(CampaignStatus::Closed);
    }

    public function launched(): self
    {
        return $this->status(CampaignStatus::Launched);
    }

    public function host(?string $host): self
    {
        $this->optional['host'] = $host;

        return $this;
    }

    public function launchesAt(DateTimeInterface|string|null $at): self
    {
        $this->optional['launches_at'] = $at instanceof DateTimeInterface ? $at->format(DATE_ATOM) : $at;

        return $this;
    }

    /** A new campaign: a draft unless a status was given, then saved with that status. */
    public function create(): Campaign
    {
        if ($this->campaign instanceof Campaign) {
            throw new LogicException('This builder holds an existing campaign: call save().');
        }
        $this->authorizer->authorize($this->actor(), 'create', Campaign::class);

        $fields = [
            'name'    => $this->name ?? '',
            'slug'    => $this->slug ?? '',
            'type'    => $this->type ?? CampaignType::Waitlist->value,
            'locales' => $this->locales ?? ['en'],
        ];
        $this->validator->make($fields, CampaignRules::create())->validate();

        $campaign = $this->create->handle($fields['name'], $fields['slug'], CampaignType::from($fields['type']), $fields['locales'], $this->actor());

        // CreateCampaign makes a draft; a status, host or launch date given here is saved straight after.
        $rest = $this->changes(withIdentity: false);

        return $rest === [] ? $campaign : $this->of($campaign)->apply($rest);
    }

    /** Saves the changes to the existing campaign. */
    public function save(): Campaign
    {
        $campaign = $this->campaign ?? throw new LogicException('Name the campaign first: ->of($campaign), or call create().');
        $this->authorizer->authorize($this->actor(), 'update', $campaign);

        return $this->apply($this->changes(withIdentity: true));
    }

    public function delete(): void
    {
        $campaign = $this->campaign ?? throw new LogicException('Name the campaign first: ->of($campaign).');
        $this->authorizer->authorize($this->actor(), 'delete', $campaign);

        $this->delete->handle($campaign, $this->actor());
    }

    /** @param  array<string, mixed>  $changes */
    private function apply(array $changes): Campaign
    {
        $campaign = $this->campaign ?? throw new LogicException('No campaign to change.');

        $data = array_merge([
            'name'        => $campaign->name,
            'slug'        => $campaign->slug,
            'type'        => $campaign->type->value,
            'status'      => $campaign->status->value,
            'locales'     => $campaign->locales,
            'host'        => $campaign->host,
            'launches_at' => $campaign->launches_at?->format(DATE_ATOM),
        ], $changes);

        /** @var array{name: string, slug: string, type: string, status: string, locales: list<string>, host?: ?string, launches_at?: ?string} $valid */
        $valid = $this->validator->make($data, CampaignRules::update($campaign->id))->validate();

        return $this->update->handle($campaign, $valid, $this->actor());
    }

    /** @return array<string, mixed> what was asked for, as the update rules name it */
    private function changes(bool $withIdentity): array
    {
        $changes = array_filter([
            'name'    => $withIdentity ? $this->name : null,
            'slug'    => $withIdentity ? $this->slug : null,
            'type'    => $withIdentity ? $this->type : null,
            'locales' => $withIdentity ? $this->locales : null,
            'status'  => $this->status,
        ], static fn (mixed $value): bool => $value !== null);

        return array_merge($changes, $this->optional);
    }
}
