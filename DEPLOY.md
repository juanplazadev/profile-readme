# Deploying the daily profile refresh

## How it works

Your profile README is the `README.md` of **juanplazadev/juanplazadev**, which is also the
juanplaza.dev site repo. The generator lives in its own repo (**juanplazadev/profile-readme**),
so none of its PHP gets near the site's Pint, PHPStan or Docker build. Only generated files land
in the site repo.

```
every day 12:17 New York
  └─ workflow "profile" in juanplazadev/juanplazadev   (runs-on: your VPS runner, juanplazadev-vps)
       ├─ checkout juanplazadev/juanplazadev  → site/
       ├─ checkout juanplazadev/profile-readme → generator/
       ├─ docker build generator              (build cache stays on the VPS)
       ├─ docker run … -v site:/target all     → fetch stats, draw SVGs, write README.md
       └─ commit README.md + profile/ "Refresh profile [skip ci]" → push to main
```

- **No host crontab.** The schedule is the `cron:` line in the workflow, and your existing runner
  picks the job up.
- **No CI or deploy noise.** The push uses `GITHUB_TOKEN`, and pushes made with it never trigger
  other workflows. `ci` and `deploy-prod` stay quiet, and `[skip ci]` is a second guard.
  Deploys only come from `production` anyway.
- **No new secrets.** The generator repo is public, so the workflow can check it out without a
  token.

---

## 1. Check it locally

```sh
cd ~/Documents/Projects/juanplazadev-github
docker compose run --rm test
GITHUB_TOKEN=$(gh auth token) docker compose run --rm profile
open out/README.md        # or preview it in your editor
```

## 2. Publish the generator repo

The workflow expects this exact name. If you pick another one, change `repository:` in
`deploy/site-repo/.github/workflows/profile.yml`.

```sh
cd ~/Documents/Projects/juanplazadev-github
git init -b main
git add .
git commit -m "Profile README generator: PHP 8.5 port in Docker"
gh repo create juanplazadev/profile-readme --public --source . --push
```

Its own `ci` workflow runs the tests on GitHub's runners. That's separate from the daily job.

## 3. Add the workflow and the first README to the site repo

Your clone at `~/Documents/Projects/juanplazadev` has uncommitted work, so do this in a separate
worktree. Your working tree stays untouched.

```sh
cd ~/Documents/Projects/juanplazadev          # the SITE repo, not juanplazadev-github
git remote get-url origin                     # must print …juanplazadev/juanplazadev.git, stop otherwise
git fetch origin
git worktree add ../juanplazadev-profile -b feat/profile-console origin/main
cd ../juanplazadev-profile

# the daily workflow
mkdir -p .github/workflows
cp ../juanplazadev-github/deploy/site-repo/.github/workflows/profile.yml .github/workflows/

# keep the SVG/JSON out of the site's Docker image
printf '\n# Generated profile README assets (juanplazadev/profile-readme)\n/profile\n' >> .dockerignore

# generate the real README.md + profile/ straight into this worktree (replaces the prose README)
cd ../juanplazadev-github
OUT_DIR=../juanplazadev-profile GITHUB_TOKEN=$(gh auth token) docker compose run --rm profile
cd ../juanplazadev-profile

git add .github/workflows/profile.yml .dockerignore README.md profile
git commit -m "Generated console profile README, refreshed daily on the VPS runner"
git push -u origin feat/profile-console
gh pr create --fill --base main
```

Merge the PR into `main`. Scheduled workflows only run from the default branch. Your profile shows
the console as soon as the merge lands.

> The old README ended with a pointer to `docs/DEVELOPMENT.md`. The generated README doesn't
> include it; that doc is still in the repo for anyone browsing.

Clean up the worktree afterwards:

```sh
cd ~/Documents/Projects/juanplazadev
git worktree remove ../juanplazadev-profile
```

## 4. Optional secrets

Without these, the job uses the built-in `GITHUB_TOKEN` and public data only. That's why stars
and followers can read 0.

```sh
# classic PAT, scopes: repo + read:user. Adds private contributions to commits/PRs/streaks.
gh secret set PROFILE_TOKEN -R juanplazadev/juanplazadev

# only matters once devUser is set in config/profile.php
gh secret set DEV_API_KEY -R juanplazadev/juanplazadev
```

## 5. First run on the server

Don't wait for 12:17. Trigger it now:

```sh
gh workflow run profile.yml -R juanplazadev/juanplazadev
gh run watch -R juanplazadev/juanplazadev
```

Check that:

- the job ran on **juanplazadev-vps** (shown in the run log header);
- the last step either pushed `Refresh profile [skip ci]` or printed `Nothing changed.`;
- no `ci` or `deploy-prod` run started (`gh run list -R juanplazadev/juanplazadev`).

From then on it runs daily by itself.

## 6. Day-to-day

| You want to… | Do this |
|---|---|
| change name, links, projects, stack | edit `config/profile.php` in **profile-readme**, push, then `gh workflow run profile.yml -R juanplazadev/juanplazadev` (or wait a day) |
| change the time | edit the `cron:` line in the site repo's `.github/workflows/profile.yml` |
| pause it | `gh workflow disable profile.yml -R juanplazadev/juanplazadev` |
| see past runs | `gh run list -R juanplazadev/juanplazadev --workflow profile.yml` |

Don't edit `README.md` or `profile/` in the site repo by hand. The next run overwrites them.

## Optional: run it by hand on the VPS

To try the exact production steps from a shell on the server, without pushing anything:

```sh
cd /tmp && rm -rf profile-try && mkdir profile-try && cd profile-try
git clone --depth 1 https://github.com/juanplazadev/profile-readme generator
git clone --depth 1 https://github.com/juanplazadev/juanplazadev site
docker build --target prod -t profile-readme:latest generator
docker run --rm --user "$(id -u):$(id -g)" -v "$PWD/site:/target" \
  -e GITHUB_TOKEN="<a token>" profile-readme:latest all
git -C site status --short          # README.md + profile/ changed; nothing is pushed
```

## Troubleshooting

- **`permission denied … docker.sock`**: the runner's user isn't in the `docker` group. It already
  runs `deploy-prod`, so this should be fine.
- **The build takes a minute again**: `deploy-prod` prunes images when the disk runs low. The next
  profile run rebuilds; nothing breaks.
- **The push is rejected**: `git pull --rebase` handles a `main` that moved during the run. If it
  still fails, a human commit touched `README.md` or `profile/`. Revert that commit and re-run.
- **The workflow is flagged as invalid because of `timezone:`**: remove that line and use a UTC
  cron instead, e.g. `"17 16 * * *"` (12:17 New York in summer).
- **The schedule stopped firing**: GitHub disables scheduled workflows after 60 days with no repo
  activity. That shouldn't happen here: the stats panel shows the sync date, so every daily run
  commits. If it does, re-enable it from the Actions tab.
