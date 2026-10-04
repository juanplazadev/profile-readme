<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Console;

use JuanPlaza\Profile\Config\ProfileConfig;
use JuanPlaza\Profile\Data\Article;
use JuanPlaza\Profile\Data\CalendarDay;
use JuanPlaza\Profile\Data\JsonStore;
use JuanPlaza\Profile\Data\Stats;
use JuanPlaza\Profile\Fetch\FetchService;
use JuanPlaza\Profile\Readme\ReadmeWriter;
use JuanPlaza\Profile\Svg\Component\Icons;
use JuanPlaza\Profile\Svg\Font\FontSubsetter;
use JuanPlaza\Profile\Svg\Frame\Frames;
use JuanPlaza\Profile\Svg\Renderer;
use JuanPlaza\Profile\Svg\Slice\Slice;

/**
 * bin/profile fetch | render | readme | all
 *
 * Reads config/ and resources/ from the code root and writes everything into the target
 * (normally a checkout of the profile repo):
 *
 *   README.md
 *   profile/assets/**.svg
 *   profile/data/*.json
 */
final readonly class Application
{
    public const string ASSETS = 'profile/assets';
    public const string DATA = 'profile/data';

    private const string USAGE = <<<'TXT'
        Usage: profile <command>

          fetch    pull fresh GitHub/DEV data into profile/data/*.json
          render   draw the SVG slices into profile/assets/ from the saved data
          readme   write README.md from the config and saved data
          all      fetch, render, readme (the default)

        Environment (all optional): PROFILE_TARGET (output folder), PROFILE_TOKEN, GITHUB_TOKEN, DEV_API_KEY, FONT_DIR
        TXT;

    private JsonStore $store;

    public function __construct(
        private string $root,
        private string $target,
        private Output $out = new Output(),
    ) {
        $this->store = new JsonStore("$target/" . self::DATA);
    }

    /** @param list<string> $argv */
    public function run(array $argv): int
    {
        $command = $argv[1] ?? 'all';
        try {
            return match ($command) {
                'fetch' => $this->fetch(),
                'render' => $this->render(),
                'readme' => $this->readme(),
                'all' => $this->all(),
                'help', '--help', '-h' => $this->help(0),
                default => $this->help(2),
            };
        } catch (\Throwable $ex) {
            $this->out->error($ex->getMessage());
            return 1;
        }
    }

    /** Stops at the first step that fails. */
    private function all(): int
    {
        foreach ([$this->fetch(...), $this->render(...), $this->readme(...)] as $step) {
            if (($code = $step()) !== 0) {
                return $code;
            }
        }
        return 0;
    }

    private function fetch(): int
    {
        $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
        if (!new FetchService($this->config(), $this->store, $this->out)->run($today)) {
            $this->out->error('every source failed, nothing updated');
            return 1;
        }
        return 0;
    }

    private function render(): int
    {
        $count = $this->renderer()->write($this->slices(), new Frames(FontSubsetter::fromEnvironment()), "{$this->target}/" . self::ASSETS);
        $this->out->line("rendered $count SVGs into " . self::ASSETS . '/');
        return 0;
    }

    private function readme(): int
    {
        file_put_contents("{$this->target}/README.md", new ReadmeWriter('./' . self::ASSETS)->build($this->slices()));
        $this->out->line('README.md written');
        return 0;
    }

    private function help(int $code): int
    {
        ($code === 0 ? $this->out->line(...) : $this->out->error(...))(self::USAGE);
        return $code;
    }

    private function renderer(): Renderer
    {
        return new Renderer($this->config(), Icons::fromFile("{$this->root}/resources/icons.json"));
    }

    /** @return list<Slice> */
    private function slices(): array
    {
        $stats = $this->store->load('stats.json', null) ?? throw new \RuntimeException(self::DATA . '/stats.json is missing; run "fetch" first');
        return $this->renderer()->slices(
            Stats::fromArray($stats),
            CalendarDay::listFrom($this->store->load('calendar.json', [])),
            Article::listFrom($this->store->load('articles.json', [])),
        );
    }

    private function config(): ProfileConfig
    {
        $config = require "{$this->root}/config/profile.php";
        return $config instanceof ProfileConfig ? $config : throw new \UnexpectedValueException('config/profile.php must return a ProfileConfig');
    }
}
