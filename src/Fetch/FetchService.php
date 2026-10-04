<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Fetch;

use JuanPlaza\Profile\Config\ProfileConfig;
use JuanPlaza\Profile\Console\Output;
use JuanPlaza\Profile\Data\Article;
use JuanPlaza\Profile\Data\CalendarDay;
use JuanPlaza\Profile\Data\JsonStore;
use JuanPlaza\Profile\Data\Stats;
use JuanPlaza\Profile\Http\DevClient;
use JuanPlaza\Profile\Http\GitHubClient;
use JuanPlaza\Profile\Http\HttpClient;

/**
 * Refreshes data/stats.json, data/articles.json and data/calendar.json.
 *
 * Each source is fetched independently. If one fails, its previous values are kept,
 * so a flaky API never blanks out part of the profile.
 */
final readonly class FetchService
{
    public function __construct(
        private ProfileConfig $config,
        private JsonStore $store,
        private Output $out,
    ) {}

    /** @return bool false when every source failed and nothing was updated */
    public function run(\DateTimeImmutable $today): bool
    {
        $stats = $this->store->load('stats.json', []);
        $articles = $this->store->load('articles.json', []);
        $http = new HttpClient($this->config->userAgent);
        $ok = false;

        $profileToken = getenv('PROFILE_TOKEN') ?: null;
        $token = $profileToken ?? (getenv('GITHUB_TOKEN') ?: null);
        if ($token !== null) {
            try {
                $gh = new GitHubFetcher(new GitHubClient($http, $token), $this->config->githubUser)->fetch($today);
                $this->store->save('calendar.json', array_map(fn(CalendarDay $d) => $d->toArray(), $gh->calendar));
                $stats = [...$stats, ...$gh->stats];
                $ok = true;
                $this->out->line('github: ok' . ($profileToken ? ' (with private contributions)' : ' (public only)'));
            } catch (\Throwable $ex) {
                $this->out->warn("GitHub fetch failed, keeping previous stats: {$ex->getMessage()}");
            }
        } else {
            $this->out->warn('No PROFILE_TOKEN or GITHUB_TOKEN set, skipping GitHub');
        }

        if ($this->config->hasDev()) {
            $apiKey = getenv('DEV_API_KEY') ?: null;
            try {
                $dev = new DevFetcher(new DevClient($http, $apiKey), $this->config->devUser, $this->out)->fetch();
                $previous = isset($stats['dev']) ? Stats::fromArray([...$this->defaults($today), ...$stats])->dev : null;
                $stats['dev'] = $dev->stats->withFallback($previous)->toArray();
                if ($dev->latest) {
                    $articles = array_map(fn(Article $a) => $a->toArray(), $dev->latest);
                }
                $ok = true;
                $this->out->line('dev: ok' . ($apiKey ? '' : ' (public only, no DEV_API_KEY)'));
            } catch (\Throwable $ex) {
                $this->out->warn("DEV fetch failed, keeping previous values: {$ex->getMessage()}");
            }
        } else {
            unset($stats['dev']);
            $articles = [];
        }

        if (!$ok) {
            return false;
        }

        $stats['hackathon_wins'] = $this->config->hackathonWins;
        $stats['updated'] = $today->format('Y-m-d');
        $this->store->save('stats.json', Stats::fromArray([...$this->defaults($today), ...$stats])->toArray());
        $this->store->save('articles.json', $articles);
        return true;
    }

    /** @return array<string, mixed> */
    private function defaults(\DateTimeImmutable $today): array
    {
        return Stats::empty($today)->toArray();
    }
}
