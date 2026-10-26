---
title: The Docker harness
order: 70
---

# The Docker harness

`docker/` composes this core and every provider (`docker/harness/plugins.php`) into one
installation, PHP 8.4, in the image `omnisense/omnifund-harness`. `docker/harness/setup.php`
writes the harness's `composer.json` and `phpunit.xml` into a volume, and the entrypoint
installs them when they change.

```sh
cd docker
docker compose run --rm omnifund test       # every package's tests, no network (the live group left out)
docker compose run --rm omnifund live       # the checks that reach Open Collective and Liberapay (no key), GitHub with OMNIFUND_GITHUB_TOKEN
docker compose run --rm omnifund bare       # the bare script: plain PHP, and what PHP loaded
docker compose run --rm omnifund phpstan    # PHPStan at the highest level, each package
docker compose run --rm -v "$PWD/scratch:/scratch" omnifund php /scratch/x.php   # a script of your own (autoloader: /harness/vendor/autoload.php)
docker compose down -v                      # the harness's volume gone
```

With no command, the entrypoint runs `test`; `composer`, `sh` and `php` run as they are; anything
else exits 2 with the list. The service uses Docker's default bridge (`network_mode: bridge`): no
network of its own is created.

Providers come from GitHub (branch 1.x) unless `OMNIFUND_PLUGINS` (in `docker/.env`) points at a
directory holding their checkouts - `../..` when this core sits in the workspace beside them, as
`docker/.env` sets it. `OMNIFUND_GITHUB_TOKEN`, from the environment or `.env`, is passed to the
container for the GitHub live check.

## Bare

`docker/harness/bin/bare` uses Omnifund in plain PHP - no bundle, no container, no framework: a
registry with the array provider, its profile (a weekly income turned monthly, a goal), two of its
sponsors, its tiers, a provider whose factory is not installed, an answer walked with the typed
readers. It prints what happened and every class and file PHP loaded, as JSON; run on
2026-10-26, without the `loaded` part:

```
"monthly": "52.00",
"sponsors": ["Ada", "ACME"],
"tiers": [["Backer", "5.00", "month"], ["Once", null, "once"]],
"capabilities": ["profile", "sponsors", "tiers"],
"missing": "No \"missing\" factory for the \"nowhere\" funding provider; installed: array.",
"read": [42, "2016-10-15", null]
```

`Tests/BareTest.php` runs it in a process of its own and checks that nothing outside the package
was loaded but Symfony's HTTP client contracts: no framework, no HTTP client, no application
class. Each provider's `BareTest` loads every one of its classes with its required packages alone.
