<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Tests;

use JuanPlaza\Profile\Config\ProfileConfig;
use JuanPlaza\Profile\Data\Article;
use JuanPlaza\Profile\Data\CalendarDay;
use JuanPlaza\Profile\Data\Stats;
use JuanPlaza\Profile\Readme\ReadmeWriter;
use JuanPlaza\Profile\Svg\Component\Icons;
use JuanPlaza\Profile\Svg\Font\FontSubsetter;
use JuanPlaza\Profile\Svg\Frame\Frames;
use JuanPlaza\Profile\Svg\Renderer;
use PHPUnit\Framework\TestCase;

/** Renders every slice from the real config with synthetic data. Needs the image's font tools. */
final class RenderSmokeTest extends TestCase
{
    private const string ROOT = __DIR__ . '/..';

    protected function setUp(): void
    {
        if (!is_dir(getenv('FONT_DIR') ?: '/opt/fonts')) {
            $this->markTestSkipped('Font tools not available; run inside the Docker image');
        }
    }

    public function testEverySliceIsValidSvgOnTheGrid(): void
    {
        /** @var ProfileConfig $config */
        $config = require self::ROOT . '/config/profile.php';
        // exercise the DEV row, writing section and hackathon row too
        $config = new ProfileConfig($config->githubUser, 'someone', $config->header, $config->links, $config->projects, $config->stack, hackathonWins: 2);

        $stats = Stats::fromArray([
            'updated' => '2026-10-03', 'year' => 2026, 'created_at' => '2018-06-27T14:05:03Z',
            'stars' => 12, 'forks' => 3, 'followers' => 40, 'prs' => 145, 'prs_merged' => 140,
            'streak_current' => 6, 'streak_longest' => 24, 'contributions_year' => 1009, 'contributions_all' => 1649,
            'languages' => ['PHP' => 500, 'TypeScript' => 400, 'JavaScript' => 100, 'Python' => 50, 'CSS' => 20, 'Shell' => 5],
            'dev' => ['articles' => 4, 'reactions' => 100, 'comments' => 20, 'views' => null, 'followers' => 50],
        ]);
        $calendar = [];
        $d = new \DateTimeImmutable('2025-09-28');
        for ($i = 0; $i < 372; $i++, $d = $d->modify('+1 day')) {
            $calendar[] = new CalendarDay($d, ($i * 7919) % 13 < 4 ? 0 : ($i * 31) % 40);
        }
        $articles = [new Article('2026-10-01T00:00:00Z', str_repeat('A very long article title ', 4), 'https://dev.to/x', 135, 67)];

        $renderer = new Renderer($config, Icons::fromFile(self::ROOT . '/resources/icons.json'));
        $frames = new Frames(FontSubsetter::fromEnvironment());
        $slices = $renderer->slices($stats, $calendar, $articles);

        $paths = array_map(fn($s) => $s->path(), $slices);
        $this->assertContains('contribution-city.svg', $paths);
        $this->assertContains('writing/post-1.svg', $paths);
        $this->assertSame('header.svg', $paths[0]);
        $this->assertSame('footer.svg', array_last($paths));

        foreach ($slices as $slice) {
            $svg = $slice->render($frames);
            $doc = new \DOMDocument();
            $this->assertTrue(@$doc->loadXML($svg), "{$slice->path()} is not well-formed XML");
            $root = $doc->documentElement;
            $this->assertSame(0, (int) $root->getAttribute('height') % 40, "{$slice->path()} height is off the grid");
            $this->assertStringContainsString('data:font/woff2;base64,', $svg);
            $this->assertSame($svg, $slice->render($frames), "{$slice->path()} must render deterministically");
        }
    }

    public function testReadmeOnlyPointsAtRenderedFilesAndPrunesStaleOnes(): void
    {
        $config = require self::ROOT . '/config/profile.php';
        $stats = Stats::fromArray(['updated' => '2026-10-03', 'year' => 2026, 'created_at' => '2018-06-27T14:05:03Z']);
        $renderer = new Renderer($config, Icons::fromFile(self::ROOT . '/resources/icons.json'));
        $slices = $renderer->slices($stats, [], []);

        $out = sys_get_temp_dir() . '/profile-' . bin2hex(random_bytes(4));
        mkdir("$out/links", 0o755, true);
        file_put_contents("$out/links/old.svg", '<svg/>');
        $renderer->write($slices, new Frames(FontSubsetter::fromEnvironment()), $out);
        $this->assertFileDoesNotExist("$out/links/old.svg");

        preg_match_all('/<img src="\.\/profile\/assets\/([^"]+)"/', new ReadmeWriter('./profile/assets')->build($slices), $m);
        $this->assertCount(count($slices), $m[1]);
        foreach ($m[1] as $path) {
            $this->assertFileExists("$out/$path");
        }
        $this->assertNotContains('contribution-city.svg', $m[1], 'no calendar, no city');
    }
}
