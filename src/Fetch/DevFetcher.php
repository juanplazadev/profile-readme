<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Fetch;

use JuanPlaza\Profile\Console\Output;
use JuanPlaza\Profile\Data\Article;
use JuanPlaza\Profile\Data\DevStats;
use JuanPlaza\Profile\Http\DevClient;

final readonly class DevFetcher
{
    public function __construct(
        private DevClient $client,
        private string $username,
        private Output $out,
    ) {}

    public function fetch(): DevResult
    {
        $arts = $this->newestFirst($this->client->publicArticles($this->username));
        $views = $followers = null;

        if ($this->client->hasKey()) {
            try {
                $mine = $this->client->myPublishedArticles();
                $views = array_sum(array_map(fn(array $a) => $a['page_views_count'] ?? 0, $mine));
                if ($mine) {
                    // the public list can be served stale from DEV's cache for a while after
                    // publishing; this authenticated list isn't, so prefer it for the latest posts
                    $arts = $this->newestFirst($mine);
                }
            } catch (\Throwable $ex) {   // one missing tile shouldn't fail the run
                $this->out->warn("DEV views unavailable: {$ex->getMessage()}");
            }
            try {
                $followers = count($this->client->followers());
            } catch (\Throwable $ex) {
                $this->out->warn("DEV followers unavailable: {$ex->getMessage()}");
            }
        }

        $stats = new DevStats(
            articles: count($arts),
            reactions: array_sum(array_map(fn(array $a) => $a['public_reactions_count'] ?? 0, $arts)),
            comments: array_sum(array_map(fn(array $a) => $a['comments_count'] ?? 0, $arts)),
            views: $views,
            followers: $followers,
        );
        return new DevResult($stats, Article::listFrom(array_slice($arts, 0, 5)));
    }

    /**
     * @param list<array<string, mixed>> $arts
     * @return list<array<string, mixed>>
     */
    private function newestFirst(array $arts): array
    {
        usort($arts, fn(array $a, array $b) => strcmp($b['published_at'], $a['published_at']));
        return $arts;
    }
}
