<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Config;

/** Everything personal about the profile. config/profile.php returns one of these. */
final readonly class ProfileConfig
{
    /**
     * @param list<Link>          $links    one button each; 880 must divide evenly by the count (4, 5, 8, 10…)
     * @param list<Project>       $projects drawn two per row, so keep the count even
     * @param list<StackCategory> $stack
     */
    public function __construct(
        public string $githubUser,
        public ?string $devUser,
        public Header $header,
        public array $links,
        public array $projects,
        public array $stack,
        public ?int $hackathonWins = null,
        public string $userAgent = 'profile-readme-updater',
    ) {
        if ($links === [] || 880 % count($links) !== 0) {
            throw new \InvalidArgumentException('880 must divide evenly by the number of links, got ' . count($links));
        }
        if (count($projects) % 2 !== 0) {
            throw new \InvalidArgumentException('Projects are drawn two per row; configure an even number');
        }
    }

    public function hasDev(): bool
    {
        return $this->devUser !== null && $this->devUser !== '';
    }
}
