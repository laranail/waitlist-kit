<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Validation;

use Simtabi\Laranail\WaitlistKit\Enums\BlockType;

/**
 * The fields each block type edits, in one place: the backend's form renders them and BlockRules validates them, so
 * the two cannot disagree. `text` is one line, `textarea` several, `lines` one paragraph per blank-line-separated
 * part, `pairs` one "first | second" item per line (FAQ questions and answers, feature titles and bodies).
 */
final class BlockFields
{
    /** @return array<string, array{kind: 'text'|'textarea'|'lines'|'pairs', max: int, keys?: array{string, string}}> */
    public static function for(BlockType $type): array
    {
        return match ($type) {
            BlockType::Hero        => ['title' => self::text(160), 'body' => self::textarea(600)],
            BlockType::Form        => ['label' => self::text(80), 'button' => self::text(60)],
            BlockType::Features    => ['title' => self::text(120), 'items' => self::pairs('title', 'body')],
            BlockType::Countdown   => ['label' => self::text(120)],
            BlockType::Faq         => ['title' => self::text(120), 'items' => self::pairs('question', 'answer')],
            BlockType::SocialProof => ['quote' => self::textarea(400), 'author' => self::text(120)],
            BlockType::Contact     => ['title' => self::text(120)],
            BlockType::Text        => ['heading' => self::text(160), 'paragraphs' => ['kind' => 'lines', 'max' => 8000]],
            BlockType::Footer      => ['text' => self::text(200)],
        };
    }

    /**
     * The form's values turned into the block's stored content for one locale.
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public static function toContent(BlockType $type, array $input): array
    {
        $content = [];
        foreach (self::for($type) as $name => $field) {
            $value = $input[$name] ?? null;
            $text = is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : '';
            if ($text === '') {
                continue;
            }

            $content[$name] = match ($field['kind']) {
                'lines' => array_values(array_filter(array_map(trim(...), preg_split('/\n\s*\n/', $text) ?: []), static fn (string $p): bool => $p !== '')),
                'pairs' => self::pairsFrom($text, $field['keys'] ?? ['title', 'body']),
                default => $text,
            };
        }

        return $content;
    }

    /**
     * The stored content turned back into the form's values.
     *
     * @param array<string, mixed> $content
     *
     * @return array<string, string>
     */
    public static function toInput(BlockType $type, array $content): array
    {
        $input = [];
        foreach (self::for($type) as $name => $field) {
            $value = $content[$name] ?? null;
            $input[$name] = match ($field['kind']) {
                'lines' => implode("\n\n", array_filter((array) $value, is_string(...))),
                'pairs' => implode("\n", array_map(
                    static fn (mixed $item): string => is_array($item)
                        ? implode(' | ', [self::scalar($item[($field['keys'] ?? ['title', 'body'])[0]] ?? ''), self::scalar($item[($field['keys'] ?? ['title', 'body'])[1]] ?? '')])
                        : '',
                    is_array($value) ? $value : [],
                )),
                default => is_string($value) ? $value : '',
            };
        }

        return $input;
    }

    /** @return array{kind: 'text', max: int} */
    private static function text(int $max): array
    {
        return ['kind' => 'text', 'max' => $max];
    }

    /** @return array{kind: 'textarea', max: int} */
    private static function textarea(int $max): array
    {
        return ['kind' => 'textarea', 'max' => $max];
    }

    /** @return array{kind: 'pairs', max: int, keys: array{string, string}} */
    private static function pairs(string $first, string $second): array
    {
        return ['kind' => 'pairs', 'max' => 8000, 'keys' => [$first, $second]];
    }

    /**
     * @param array{string, string} $keys
     *
     * @return list<array<string, string>>
     */
    private static function pairsFrom(string $text, array $keys): array
    {
        $items = [];
        foreach (explode("\n", $text) as $line) {
            [$first, $second] = array_pad(array_map(trim(...), explode('|', $line, 2)), 2, '');
            if ($first !== '') {
                $items[] = [$keys[0] => $first, $keys[1] => $second];
            }
        }

        return $items;
    }

    private static function scalar(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
