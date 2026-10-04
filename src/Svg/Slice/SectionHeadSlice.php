<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Slice;

use JuanPlaza\Profile\Svg\Component\Heading;
use JuanPlaza\Profile\Svg\Frame\Frames;

/** A "~/name // NN" heading with its "$ command" prompt (links, projects, writing heads). */
final readonly class SectionHeadSlice implements Slice
{
    public function __construct(
        private string $file,
        private string $name,
        private string $counter,
        private string $command,
        private string $title,
        private string $desc,
        private string $comment = '',
        private float $promptDelay = .15,
    ) {}

    public function path(): string
    {
        return $this->file;
    }

    public function alt(): string
    {
        return $this->title === $this->desc ? $this->title : "{$this->title}: {$this->desc}";
    }

    public function href(): ?string
    {
        return null;
    }

    public function width(): float
    {
        return 100;
    }

    public function render(Frames $frames): string
    {
        $body = Heading::render(44, $this->name, $this->counter) . "\n" . Heading::prompt($this->command, $this->comment, $this->promptDelay);
        return $frames->full(120, $body, $this->title, $this->desc, text: '');
    }
}
