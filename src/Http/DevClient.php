<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Http;

final readonly class DevClient
{
    private const int PER_PAGE = 1000;

    public function __construct(
        private HttpClient $http,
        private ?string $apiKey = null,
    ) {}

    public function hasKey(): bool
    {
        return $this->apiKey !== null && $this->apiKey !== '';
    }

    /** @return list<array<string, mixed>> */
    public function publicArticles(string $username): array
    {
        return $this->paged('https://dev.to/api/articles?username=' . rawurlencode($username));
    }

    /** @return list<array<string, mixed>> */
    public function myPublishedArticles(): array
    {
        return $this->paged('https://dev.to/api/articles/me/published', $this->authHeaders());
    }

    /** @return list<array<string, mixed>> */
    public function followers(): array
    {
        return $this->paged('https://dev.to/api/followers/users', $this->authHeaders());
    }

    /** @return array<string, string> */
    private function authHeaders(): array
    {
        return ['api-key' => (string) $this->apiKey, 'Accept' => 'application/vnd.forem.api-v1+json'];
    }

    /**
     * @param array<string, string> $headers
     * @return list<array<string, mixed>>
     */
    private function paged(string $url, array $headers = []): array
    {
        $out = [];
        $sep = str_contains($url, '?') ? '&' : '?';
        for ($page = 1; ; $page++) {
            $batch = $this->http->json("{$url}{$sep}per_page=" . self::PER_PAGE . "&page=$page", $headers);
            if (!$batch) {
                return $out;
            }
            array_push($out, ...$batch);
            if (count($batch) < self::PER_PAGE) {
                return $out;
            }
        }
    }
}
