# Trustindex Homework – Company Review Mini-Application

A Symfony 7.4-based review-aggregator mini-module: users can publicly write reviews about companies, reviews are listed on the homepage, and aggregated per-company stats are also available (`/companies`), sorted by average rating descending.

## Stack

- PHP 8.2+, Symfony 7.4
- Doctrine ORM + Doctrine Migrations
- Symfony Forms + Validator
- Twig (unified `base.html.twig` layout)
- Tailwind CSS v4 (`symfonycasts/tailwind-bundle`) — custom design system, see `CLAUDE.md`
- Stimulus (AssetMapper, no npm/Node build step)
- PHPUnit

## Features

- **Submit a review** (`/reviews/new`) — Symfony Form + Validator, with a company name search/autocomplete field (pick an existing company, or a new one is created from the typed name if there's no match)
- **Review listing** (`/`) — star rating, truncated text, date, paginated
- **Review detail page** (`/reviews/{id}`)
- **Company stats** (`/companies`) — review count and average rating per company, sorted by average rating descending
- **Search by company name** (2.5, bonus) — live, debounced search on the `/companies` page: results update as you type, with no page reload — implemented via a small Stimulus controller (`company_search_controller.js`) that fetches the same `/companies` route with an `X-Requested-With` header and swaps in the server-rendered results fragment (`CompanyController::index()`, `templates/company/_results.html.twig`). Kept deliberately simple by reusing one route for both the full page and the fragment, rather than introducing a dedicated endpoint — if this pattern needed to support more than one page, or needed its own independent contract/tests, splitting it into a dedicated route (e.g. `GET /companies/results`) would be the natural next step
- **Spam/quality filter** (2.6, extra) — the differentiator from the assignment's "add something extra" requirement. A heuristic check (`ReviewSpamDetector`) runs on every new review submission: banned words, excessive capitalization, links in the text, and repeated submissions from the same email to the same company within a short time window. A flagged review is still saved (not silently rejected — a false positive shouldn't lose a real review, and a real spammer shouldn't get feedback on what tripped the filter), but it's excluded from the public listing and the average-rating calculation until moderated. "Until moderated" is aspirational at this scale — there's no admin UI or workflow to actually review and unflag a review; that's a natural next step, but out of scope for this size of app

## Setup

```bash
composer install
```

### Database

Copy `.env` to `.env.local` and set the local DB credentials (matching `compose.yaml`'s MySQL container):

```bash
cp .env .env.local
```

```dotenv
# by default
DATABASE_URL="mysql://root:app@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"
```

`.env.local` is gitignored — it's meant for local overrides and is never committed.

Start MySQL via Docker:

```bash
docker compose up -d
```

Create the database and run migrations:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Frontend assets (Tailwind)

```bash
php bin/console tailwind:build
# during developement:
php bin/console tailwind:build --watch
```

### Running the app

```bash
symfony serve
# or
php -S localhost:8000 -t public
```

Changing `APP_ENV` may require running these two commands, so assets load properly:

```bash
php bin/console cache:clear --env=prod
php bin/console asset-map:compile --env=prod
```

## Tests

The test environment uses a separate, SQLite-based database (`.env.test`), so running the tests doesn't require the MySQL container. Before running the tests for the first time, create the test schema:

```bash
php bin/console doctrine:schema:create --env=test
```

If you change the entity mappings later during development (new field, new entity, etc.), update the test schema to match:

```bash
php bin/console doctrine:schema:update --env=test --force
```

Running the tests:

```bash
php bin/phpunit
```

## Code style

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff   # check
vendor/bin/php-cs-fixer fix                    # fix
```

## Architecture notes

- The assignment expects the company-stats aggregation method to live in `ReviewRepository`. Since the solution — for a more realistic data model than the one implied by the spec — includes a separate `Company` entity and `CompanyRepository`, this method (`findAllWithReviewStats()`) lives in `CompanyRepository` instead.
- The `Review` entity's validation constraints (`#[Assert\*]`) are currently not the only validation path — the `/reviews/new` form validates a separate DTO (`CreateReviewRequest`) instead, because resolving the `company` field (existing company or creating a new one) can only happen *after* validation. The constraints left on the entity document what a valid state looks like, and also protect any future non-form entry point (e.g. an API).
- The separate `Company` entity introduces a design question that wouldn't exist if `Review` just had a plain `companyName` string field: what happens when someone types a company name that doesn't exist yet? `CompanyResolver::findOrCreateByName()` exists specifically to preserve the behavior a single-entity design would have had for free — a case-insensitive match reuses the existing `Company`, and no match transparently creates a new one. From the review-submission form's point of view, typing any company name always works, exactly as if `company` were still just a string on `Review`.

## Work log

| Task | Time |
|---|---|
| Project setup — Symfony skeleton, webapp packages, Docker (MySQL) | ~0.5 h |
| **1 Data model** — `Review`/`Company` entities, Doctrine attribute mapping, validation constraints, first migration | ~0.5 h |
| **2.1 New review submission** — `ReviewType` form, validation rules, flash message, flash toast component | ~1 h |
| **2.2 Review listing** — homepage, pagination, page selector | ~0.5 h |
| **2.3 Review detail page** — route + controller action, star-rating component (with partial fill) | ~0.5 h |
| **3 Tech requirements** — Tailwind design system, unified `base.html.twig` layout | ~0.5 h |
| **2.4 Company stats** — `/companies` page, `findAllWithReviewStats()`, sorted by average rating descending | ~0.5 h |
| **2.1 (cont.)** — company name search/autocomplete on the review form, find-or-create logic (`CompanyResolver`) | ~0.5 h |
| **2.5 Search by company name (bonus)** — live, debounced search on `/companies` | ~0.5 h |
| **2.6 Extra** — spam/quality filter (`ReviewSpamDetector`), moderation `flagged` field | ~0.5 h |
| **4 Testing** — PHPUnit functional + unit tests (average calculation and sorting logic covered), introduced `php-cs-fixer`, final review pass | ~1.0 h |

**Total: ~6.5 hours** (over the estimated 4–6 hours — due to the bonus features (search, extra spam filter) and broader test coverage).
