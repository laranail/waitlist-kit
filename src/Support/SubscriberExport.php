<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Support;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;

/**
 * A campaign's subscribers, or the enquiries, as CSV, oldest first. A cell starting with = + - or @ is prefixed with a quote, so a
 * spreadsheet shows what a visitor typed instead of running it as a formula.
 */
final class SubscriberExport
{
    public const array COLUMNS = ['id', 'email', 'status', 'locale', 'position', 'referral_code', 'referred_by_id', 'created_at'];

    public const array ENQUIRY_COLUMNS = ['id', 'name', 'email', 'company', 'topic', 'message', 'locale', 'created_at'];

    /** @param  resource  $out */
    public function write(Campaign $campaign, $out): int
    {
        return $this->rows(Subscriber::query()->where('campaign_id', $campaign->id), self::COLUMNS, $out);
    }

    /**
     * Enquiries, all of them or one campaign's.
     *
     * @param resource $out
     */
    public function writeEnquiries(?Campaign $campaign, $out): int
    {
        $query = Enquiry::query();
        if ($campaign instanceof Campaign) {
            $query->where('campaign_id', $campaign->id);
        }

        return $this->rows($query, self::ENQUIRY_COLUMNS, $out);
    }

    /**
     * @param Builder<covariant Model> $query
     * @param list<string> $columns
     * @param resource $out
     */
    private function rows(Builder $query, array $columns, $out): int
    {
        fputcsv($out, $columns, escape: '');

        $count = 0;
        $query->orderBy('id')
            ->each(function (Model $model) use ($out, $columns, &$count): void {
                $row = [];
                foreach ($columns as $column) {
                    $value = $model->getAttribute($column);
                    $row[] = $this->cell(match (true) {
                        $value instanceof BackedEnum        => (string) $value->value,
                        $value instanceof DateTimeInterface => $value->format(DATE_ATOM),
                        is_scalar($value)                   => (string) $value,
                        default                             => '',
                    });
                }
                fputcsv($out, $row, escape: '');
                $count++;
            });

        return $count;
    }

    private function cell(string $value): string
    {
        return $value !== '' && str_contains('=+-@', $value[0]) ? "'" . $value : $value;
    }
}
