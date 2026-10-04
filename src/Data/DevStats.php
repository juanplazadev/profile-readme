<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Data;

/** DEV Community totals. views and followers need DEV_API_KEY, so they may be unknown. */
final readonly class DevStats
{
    public function __construct(
        public int $articles,
        public int $reactions,
        public int $comments,
        public ?int $views = null,
        public ?int $followers = null,
    ) {}

    /** @param array<string, ?int> $a */
    public static function fromArray(array $a): self
    {
        return new self($a['articles'] ?? 0, $a['reactions'] ?? 0, $a['comments'] ?? 0, $a['views'] ?? null, $a['followers'] ?? null);
    }

    /** Keep previously known values rather than dropping a tile when the key-only calls fail. */
    public function withFallback(?self $previous): self
    {
        return $previous === null ? $this : clone($this, [
            'views' => $this->views ?? $previous->views,
            'followers' => $this->followers ?? $previous->followers,
        ]);
    }

    /** @return array<string, ?int> label => value, in display order */
    public function toArray(): array
    {
        return [
            'articles' => $this->articles,
            'reactions' => $this->reactions,
            'comments' => $this->comments,
            'views' => $this->views,
            'followers' => $this->followers,
        ];
    }
}
