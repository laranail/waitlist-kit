<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Support;

use BackedEnum;
use DateTimeInterface;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;

/**
 * A campaign's subscribers as CSV, oldest first. A cell starting with = + - or @ is prefixed with a quote, so a
 * spreadsheet shows what a visitor typed instead of running it as a formula.
 */
final class SubscriberExport
{
    public const array COLUMNS = ['id', 'email', 'status', 'locale', 'position', 'referral_code', 'referred_by_id', 'created_at'];

    /** @param  resource  $out */
    public function write(Campaign $campaign, $out): int
    {
        fputcsv($out, self::COLUMNS, escape: '');

        $count = 0;
        Subscriber::query()->where('campaign_id', $campaign->id)->orderBy('id')
            ->each(function (Subscriber $subscriber) use ($out, &$count): void {
                $row = [];
                foreach (self::COLUMNS as $column) {
                    $value = $subscriber->getAttribute($column);
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
