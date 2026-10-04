<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Data;

final readonly class Article
{
    public function __construct(
        public string $publishedAt,
        public string $title,
        public string $url,
        public int $reactions,
        public int $comments,
    ) {}

    public function date(): string
    {
        return substr($this->publishedAt, 0, 10);
    }

    /** @param array<string, mixed> $a a DEV API article or a saved articles.json entry */
    public static function fromArray(array $a): self
    {
        return new self(
            $a['published_at'],
            $a['title'],
            $a['url'],
            $a['reactions'] ?? $a['public_reactions_count'] ?? 0,
            $a['comments'] ?? $a['comments_count'] ?? 0,
        );
    }

    /** @return array<string, string|int> */
    public function toArray(): array
    {
        return [
            'published_at' => $this->publishedAt,
            'title' => $this->title,
            'url' => $this->url,
            'reactions' => $this->reactions,
            'comments' => $this->comments,
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<self>
     */
    public static function listFrom(array $rows): array
    {
        return array_map(self::fromArray(...), $rows);
    }
}
