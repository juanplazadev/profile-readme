<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Http;

final readonly class GitHubClient
{
    public function __construct(
        private HttpClient $http,
        private string $token,
    ) {}

    /**
     * @param array<string, mixed> $variables
     * @return array<string, mixed> the "data" member
     */
    public function graphql(string $query, array $variables = []): array
    {
        $res = $this->http->json(
            'https://api.github.com/graphql',
            ['Authorization' => "bearer {$this->token}"],
            ['query' => $query, 'variables' => (object) $variables],
        );
        if (!empty($res['errors'])) {
            $messages = array_map(fn(array $e) => $e['message'] ?? '?', $res['errors']);
            throw new HttpException('GraphQL error: ' . implode('; ', $messages));
        }
        return $res['data'];
    }
}
