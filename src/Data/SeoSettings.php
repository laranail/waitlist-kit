<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Data;

use InvalidArgumentException;

/**
 * How a campaign's page presents itself to search engines and link previews, from `campaigns.settings.seo`: a title
 * and description per language, one share image, and whether it may be indexed at all (off by default: a page is
 * only indexed when someone decides it should be). Built only valid.
 */
final readonly class SeoSettings
{
    /**
     * @param array<string, string> $titles locale => title
     * @param array<string, string> $descriptions locale => description
     */
    public function __construct(
        public array $titles = [],
        public array $descriptions = [],
        public ?string $image = null,
        public bool $index = false,
    ) {
        if ($image !== null && ! self::isHttps($image)) {
            throw new InvalidArgumentException('A share image is an absolute https:// address.');
        }
    }

    /**
     * From stored settings. Anything of the wrong shape is dropped rather than failing a public page.
     *
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $strings = static function (mixed $map): array {
            $clean = [];
            foreach (is_array($map) ? $map : [] as $locale => $text) {
                if (is_string($locale) && is_string($text) && trim($text) !== '') {
                    $clean[$locale] = trim($text);
                }
            }

            return $clean;
        };
        $image = $stored['image'] ?? null;

        return new self(
            titles: $strings($stored['title'] ?? []),
            descriptions: $strings($stored['description'] ?? []),
            image: is_string($image) && self::isHttps($image) ? $image : null,
            index: ($stored['index'] ?? false) === true,
        );
    }

    public static function isHttps(string $url): bool
    {
        return str_starts_with($url, 'https://') && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /** The title in $locale, else in $fallback, else $default. */
    public function title(string $locale, string $fallback, string $default): string
    {
        return $this->titles[$locale] ?? $this->titles[$fallback] ?? $default;
    }

    public function description(string $locale, string $fallback): ?string
    {
        return $this->descriptions[$locale] ?? $this->descriptions[$fallback] ?? null;
    }

    /** @return array{title: array<string, string>, description: array<string, string>, image: ?string, index: bool} */
    public function toArray(): array
    {
        return ['title' => $this->titles, 'description' => $this->descriptions, 'image' => $this->image, 'index' => $this->index];
    }
}
