<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Data;

/** Reads and writes the data/*.json files. */
final readonly class JsonStore
{
    public function __construct(private string $dir) {}

    /** Decoded file contents, or $default when the file is missing or broken. */
    public function load(string $name, mixed $default): mixed
    {
        $path = $this->path($name);
        if (!is_file($path)) {
            return $default;
        }
        try {
            return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $default;
        }
    }

    public function save(string $name, mixed $data): void
    {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0o755, true);
        }
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        file_put_contents($this->path($name), $json . "\n");
    }

    public function exists(string $name): bool
    {
        return is_file($this->path($name));
    }

    private function path(string $name): string
    {
        return $this->dir . '/' . $name;
    }
}
