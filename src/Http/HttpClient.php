<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Http;

final readonly class HttpClient
{
    public function __construct(
        private string $userAgent,
        private int $timeout = 30,
    ) {}

    /**
     * @param array<string, string> $headers
     * @param ?array<string, mixed> $body sent as JSON (and switches to POST) when given
     */
    public function json(string $url, array $headers = [], ?array $body = null): mixed
    {
        $headers = ['User-Agent' => $this->userAgent, 'Accept' => 'application/json', ...$headers];
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FAILONERROR => false,
        ];
        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
        }
        $opts[CURLOPT_HTTPHEADER] = array_map(fn($k, $v) => "$k: $v", array_keys($headers), $headers);
        curl_setopt_array($ch, $opts);

        $raw = curl_exec($ch);
        if ($raw === false) {
            throw new HttpException("$url: " . curl_error($ch));
        }
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($status >= 400) {
            throw new HttpException("$url: HTTP $status " . substr((string) $raw, 0, 200));
        }
        return json_decode((string) $raw, true, flags: JSON_THROW_ON_ERROR);
    }
}
