# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project adheres to Semantic Versioning.

## [3.1.0] - 2026-10-24

### Added
- A `cache/` folder for runtime cache files such as the file rate limiter's data. Its contents are git-ignored, so a fresh install no longer fails the first rate-limited request.
- The `resend` mailer entry in `shared/config/mailer.php` and `RESEND_APIKEY` in `.env.example`.
- A `save_remote_images()` helper that downloads the demo images in parallel; `php qt demo` uses it.
- An API smoke test in CI.
- `logging.message.limit` and `mailer.transport_log_limit` in the config: the number of log messages and transport log lines kept in memory, both `1000` by default (framework #593).

### Changed
- `shared/config/lang.php` uses the 3.1 Lang format: `default` adapter (`file`, `deepl` or `google_translate`), `default_locale` and a section per remote adapter.
- `save_remote_image()` uses the fluent `httpRequest()` helper.
- CI runs `composer rector:check` after PHPStan.
- The DTOs and services use `readonly` properties, and null values are cast to strings where they reach string functions (`trim()`, `strtotime()`, `preg_replace()`), as the Rector PHP 8.1 level set proposes.
- **Breaking:** the minimum PHP version is 8.1 (`composer.json`, README, PHPStan `phpVersion` and the Rector level set), and CI runs on PHP 8.1, 8.2 and 8.3, following the framework (quantum-php/framework#595).

### Removed
- The `Hook`, `Loader` and `libraries/` leftovers: the `hooks/` and `libraries/` folders, `shared/config/hooks.php` and the hooks and loader entries in the dependencies config.
- The unused Travis CI configuration (`.travis.yml`).

## [3.0.3] - 2026-07-10

### Changed
- Raised the project runtime baseline to PHP 8.0 and updated CI to exercise PHP 8.0, 8.1, and 8.2.
- Bumped the PHPStan parser baseline to PHP 8.0.
- Aligned the downstream framework dependency to stable `quantum/framework` `3.0.3`.

## [3.0.0] - 2026-05-28

### Changed
- Upgraded downstream framework dependency from `quantum/framework` `dev-master` to stable `3.0.0`.
- Updated `.env.example` default `APP_VERSION` from `2.9.9` to `3.0.0`.

### Notes
- This release aligns the downstream project with upstream `quantum/framework` `3.0.0`.
