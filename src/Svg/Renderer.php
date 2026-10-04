<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg;

use JuanPlaza\Profile\Config\ProfileConfig;
use JuanPlaza\Profile\Data\Article;
use JuanPlaza\Profile\Data\CalendarDay;
use JuanPlaza\Profile\Data\Stats;
use JuanPlaza\Profile\Svg\Component\Icons;
use JuanPlaza\Profile\Svg\Frame\Frames;
use JuanPlaza\Profile\Svg\Frame\Side;
use JuanPlaza\Profile\Svg\Slice\ArticleRowSlice;
use JuanPlaza\Profile\Svg\Slice\CitySlice;
use JuanPlaza\Profile\Svg\Slice\FooterSlice;
use JuanPlaza\Profile\Svg\Slice\HeaderSlice;
use JuanPlaza\Profile\Svg\Slice\LinkButtonSlice;
use JuanPlaza\Profile\Svg\Slice\ProjectCardSlice;
use JuanPlaza\Profile\Svg\Slice\SectionHeadSlice;
use JuanPlaza\Profile\Svg\Slice\Slice;
use JuanPlaza\Profile\Svg\Slice\StackSlice;
use JuanPlaza\Profile\Svg\Slice\StatsSlice;
use JuanPlaza\Profile\Svg\Slice\WritingMoreSlice;

/** Turns config + data into the README's SVG slices, top to bottom. */
final readonly class Renderer
{
    public function __construct(
        private ProfileConfig $config,
        private Icons $icons,
    ) {}

    /**
     * @param list<CalendarDay> $calendar empty skips the contribution city
     * @param list<Article>     $articles
     * @return list<Slice>
     */
    public function slices(Stats $stats, array $calendar, array $articles): array
    {
        $c = $this->config;
        $section = 0;
        $counter = function () use (&$section): string {
            return sprintf('// %02d', ++$section);
        };

        $slices = [new HeaderSlice($c->header)];

        $slices[] = new SectionHeadSlice('links.svg', 'links', $counter(), "ping {$c->githubUser} --all-channels", 'Links', 'Where to find me');
        foreach ($c->links as $i => $link) {
            $slices[] = new LinkButtonSlice($link, $i, count($c->links), $this->icons);
        }

        $slices[] = new StatsSlice($stats, $c->githubUser, $c->hasDev() ? $c->devUser : null, $counter());

        if ($calendar !== []) {
            $slices[] = new CitySlice($calendar, $stats->updated, $counter());
        }

        $slices[] = new SectionHeadSlice('projects.svg', 'projects', $counter(), 'ls -l ~/projects', 'Projects', 'Projects', promptDelay: .2);
        foreach ($c->projects as $i => $p) {
            $slices[] = new ProjectCardSlice($p, $i % 2 === 0 ? Side::Left : Side::Right, .3 + $i * .12, $stats->repoStars[$p->slug] ?? $p->stars);
        }

        $slices[] = new StackSlice($c->stack, $counter());

        if ($c->hasDev() && $articles !== []) {
            $slices[] = new SectionHeadSlice('writing.svg', 'writing', $counter(), 'tail -n 5 ~/dev.to/posts.log', 'Writing', 'Latest articles on DEV Community', comment: 'auto-updated');
            foreach (array_slice($articles, 0, 5) as $i => $article) {
                $slices[] = new ArticleRowSlice($article, $i);
            }
            $slices[] = new WritingMoreSlice('https://dev.to/' . rawurlencode((string) $c->devUser));
        }

        $slices[] = new FooterSlice($c->githubUser);
        return $slices;
    }

    /**
     * Writes every slice into $outDir and removes SVGs left over from earlier runs (a dropped
     * project or link), since the folder belongs to the generator.
     *
     * @param list<Slice> $slices
     * @return int number of files written
     */
    public function write(array $slices, Frames $frames, string $outDir): int
    {
        $written = [];
        foreach ($slices as $slice) {
            $path = "$outDir/{$slice->path()}";
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0o755, true);
            }
            file_put_contents($path, $slice->render($frames));
            $written[realpath($path)] = true;
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($outDir, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->getExtension() === 'svg' && !isset($written[$file->getRealPath()])) {
                unlink($file->getPathname());
            }
        }
        return count($written);
    }
}
