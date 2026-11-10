# Coursify end-to-end tests

These are browser tests with [Playwright](https://playwright.dev/) against a running Moodle site with Coursify installed.

## Before a run

`fixture.php` creates two temporary accounts, a teacher and a student, with random passwords. It also creates a test course with five sections, groups, a locked section and a video link. After the run, everything is removed.

## Setup

```sh
cd course/format/coursify/tests/playwright
npm install
npx playwright install chromium
```

Create `.env` (it is git-ignored):

```
MOODLE_URL=http://localhost/moodle
PHP_BINARY=/usr/bin/php
```

## Run

```sh
npx playwright test
npx playwright test --headed
npx playwright show-report
```

To keep the test data after a run, set `COURSIFY_E2E_KEEP=1`.
