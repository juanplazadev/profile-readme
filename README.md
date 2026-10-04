# profile-readme

Generates the cyberpunk-console GitHub profile README for
[github.com/juanplazadev](https://github.com/juanplazadev). It fetches GitHub (and optionally DEV)
stats, draws about 15 animated SVG slices with an embedded, subset JetBrains Mono, and writes a
`README.md` that stacks them into one continuous console frame.

Object-oriented PHP 8.5 in Docker. A port of
[georgekobaidze/georgekobaidze](https://github.com/georgekobaidze/georgekobaidze)'s Python
generator; the original design is his (MIT). The port was written with
[Claude Opus 5.5](https://www.anthropic.com/claude) via Claude Code.

## What it writes

Everything goes into one target folder: `./out` locally, a checkout of the profile repo in
production.

```
README.md              generated in full from config + data, so don't edit it by hand
profile/assets/**.svg  the slices
profile/data/*.json    last fetched stats (kept so a failing API never blanks a tile)
```

## Making it yours

All personal content lives in [`config/profile.php`](config/profile.php): name, bio lines,
links, project cards, the stack and usernames. Set `devUser` to add the DEV Community tiles and a
"latest articles" section.

## Running locally

```sh
docker compose run --rm test                                     # phpunit
GITHUB_TOKEN=$(gh auth token) docker compose run --rm profile    # fetch + render + readme into ./out
docker compose run --rm profile render                           # redraw from saved data
OUT_DIR=../some-checkout docker compose run --rm profile readme  # write somewhere else
```

Environment variables, all optional (see `.env.example`):

- `GITHUB_TOKEN`: public stats.
- `PROFILE_TOKEN`: a classic PAT with `repo` and `read:user`. Adds private contributions.
- `DEV_API_KEY`: adds DEV views and followers.

## Layout

| Path | What |
|---|---|
| `src/Fetch`, `src/Http` | GitHub GraphQL and DEV API clients; each source fails independently |
| `src/Svg/Slice` | one class per image (`HeaderSlice`, `StatsSlice`, `CitySlice`, …) |
| `src/Svg/Frame` | the shared console frame, half slices (cards) and segments (link buttons) |
| `src/Svg/Font` | glyph subsetting via `hb-subset` + `woff2_compress` (installed in the image) |
| `src/Readme` | builds `README.md` from the slice list |

Output is deterministic: unchanged data gives byte-identical files, so the daily job only commits
when something actually changed.

Deployment: the daily refresh runs on the VPS runner from the site repo's workflow, a copy of
[`deploy/site-repo/.github/workflows/profile.yml`](deploy/site-repo/.github/workflows/profile.yml).
