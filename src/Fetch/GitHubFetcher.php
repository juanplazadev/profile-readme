<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Fetch;

use JuanPlaza\Profile\Data\CalendarDay;
use JuanPlaza\Profile\Http\GitHubClient;

final readonly class GitHubFetcher
{
    private const string PROFILE_QUERY = <<<'GQL'
        query($login: String!) {
          user(login: $login) {
            createdAt
            followers { totalCount }
            pullRequests { totalCount }
            merged: pullRequests(states: MERGED) { totalCount }
            contributionsCollection { contributionYears }
            repositories(ownerAffiliations: OWNER, isFork: false, privacy: PUBLIC, first: 100) {
              nodes {
                name
                stargazerCount
                forkCount
                languages(first: 20) { edges { size node { name } } }
              }
            }
          }
        }
        GQL;

    public function __construct(
        private GitHubClient $client,
        private string $login,
        private StreakCalculator $streaks = new StreakCalculator(),
    ) {}

    public function fetch(\DateTimeImmutable $today): GitHubResult
    {
        $u = $this->client->graphql(self::PROFILE_QUERY, ['login' => $this->login])['user']
            ?? throw new \RuntimeException("GitHub user {$this->login} not found");
        $years = $u['contributionsCollection']['contributionYears'];
        sort($years);
        $ydata = $years ? $this->client->graphql($this->yearsQuery($years), ['login' => $this->login])['user'] : [];

        $days = [];
        $commitsAll = $contribsAll = 0;
        foreach ($years as $y) {
            $c = $ydata["y$y"];
            $commitsAll += $c['totalCommitContributions'];
            $contribsAll += $c['contributionCalendar']['totalContributions'];
            foreach ($c['contributionCalendar']['weeks'] as $w) {
                foreach ($w['contributionDays'] as $day) {
                    $days[$day['date']] = $day['contributionCount'];
                }
            }
        }
        [$current, $longest] = $this->streaks->calculate($days, $today);

        $repos = $u['repositories']['nodes'];
        $langs = [];
        foreach ($repos as $r) {
            foreach ($r['languages']['edges'] as $edge) {
                $langs[$edge['node']['name']] = ($langs[$edge['node']['name']] ?? 0) + $edge['size'];
            }
        }
        arsort($langs);

        $year = (int) $today->format('Y');
        $cur = $ydata["y$year"] ?? [];
        return new GitHubResult([
            'created_at' => $u['createdAt'],
            'followers' => $u['followers']['totalCount'],
            'prs' => $u['pullRequests']['totalCount'],
            'prs_merged' => $u['merged']['totalCount'],
            'stars' => array_sum(array_column($repos, 'stargazerCount')),
            'forks' => array_sum(array_column($repos, 'forkCount')),
            'repo_stars' => array_column($repos, 'stargazerCount', 'name'),
            'languages' => $langs,
            'year' => $year,
            'commits_year' => $cur['totalCommitContributions'] ?? 0,
            'commits_all' => $commitsAll,
            'contributions_year' => $cur['contributionCalendar']['totalContributions'] ?? 0,
            'contributions_all' => $contribsAll,
            'streak_current' => $current,
            'streak_longest' => $longest,
        ], $this->calendar($days, $today));
    }

    /**
     * Last 53 weeks, starting on a Sunday like GitHub's own graph (feeds the contribution city).
     *
     * @param array<string, int> $days
     * @return list<CalendarDay>
     */
    private function calendar(array $days, \DateTimeImmutable $today): array
    {
        $start = $today->modify('-52 weeks');
        $start = $start->modify('-' . (int) $start->format('w') . ' days');
        $out = [];
        for ($d = $start; $d <= $today; $d = $d->modify('+1 day')) {
            $out[] = new CalendarDay($d, $days[$d->format('Y-m-d')] ?? 0);
        }
        return $out;
    }

    /** @param list<int> $years */
    private function yearsQuery(array $years): string
    {
        $parts = array_map(fn(int $y) => <<<GQL

                y$y: contributionsCollection(from: "$y-01-01T00:00:00Z", to: "$y-12-31T23:59:59Z") {
                  totalCommitContributions
                  contributionCalendar { totalContributions weeks { contributionDays { date contributionCount } } }
                }
            GQL, $years);
        return "query(\$login: String!) {\n  user(login: \$login) {" . implode('', $parts) . "\n  }\n}";
    }
}
