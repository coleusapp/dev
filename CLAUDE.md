<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `pnpm run build` or ask the user to run `pnpm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project uses the streamlined Laravel 11+ structure: register middleware, exceptions, and routing in `bootstrap/app.php` and service providers in `bootstrap/providers.php`. There is no `app/Http/Kernel.php` or `app/Console/Kernel.php`, and commands in `app/Console/Commands/` auto-register.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== laravel-octane/core rules ===

# Laravel Octane

This application uses Laravel Octane, a long-running PHP server. The application bootstraps once and handles many requests within the same process.

- Never store request-specific state in singletons or static properties, because it can leak across requests.
- Use `config('octane.server')` to detect the active driver (`swoole`, `roadrunner`, or `frankenphp`).
- Prefer scoped bindings (`$this->app->scoped()`) over singletons for per-request services.

When working on Octane-specific features (concurrency, shared tables, memory, driver configuration, testing), invoke `octane-development` for detailed rules.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>

## Solution Quality
- I'm not looking for a cheap solution. Do not implement quick/shortcut fixes that will need to be revisited later because the first pass was too minimal — build it properly the first time, even if it takes more work now.
- The goal is to not have to ask me questions, and to make the code understandable to you and you only — the end goal is to throw you away at the end. Code, structure, and naming should stand on their own without requiring back-and-forth to explain them.

## Change Size
- Keep every change commit-bit-sized. Never let a fix sprawl into a large multi-file/multi-concern sweep.
- Prefer the narrowest fix that solves the stated problem; don't also "complete" adjacent unfinished/unrelated scaffolding in the same pass unless asked.
- Treat data backfills/migrations as a separate, explicit step to confirm before running, even against a local dev database.
- If a fix is clearly growing beyond one commit's worth of diff, stop and check in before continuing.

## Concurrent Edits
- The user may be editing the same file at the same time as you, in their own editor, adding or removing things.
- Before editing a file you already read earlier in the conversation, re-read it first if there's any chance it changed. Never blindly Write/overwrite a file based on stale content held in context.
- If a file changed on disk since you last read it, treat that as the user's real, deliberate work — not something to revert or ignore in favor of what you remember.

## Coding Style
Follow Spatie's guidelines for PHP, Laravel and JavaScript: https://spatie.be/guidelines (pages `/guidelines/php`, `/guidelines/laravel`, `/guidelines/javascript`). Where the user has said otherwise, the user wins; those exceptions are marked **(user)**.

PHP / Laravel:
- Do it the way Laravel documents it; deviating needs a reason. PSR-1/2/12. No `final`, no `readonly`.
- Types: `?string`, not `string|null`. `: void` when nothing is returned. Typed properties, never a `@var` docblock for the type. Constructor promotion when every property can be promoted: one parameter per line, trailing comma, `) {}`.
- Enum cases and class constants are PascalCase (`case Diamonds;`, `const SessionTokenHeader`). Enums have no prefix/suffix.
- Docblocks only when the signature can't say it (iterable shapes like `array<int, Note>`, `array{a: X}`, `Collection<int,Note>`); one line when possible (`/** @return ... */`); Spatie's "if one param needs a docblock, document all of them" loses to Pint's `no_superfluous_phpdoc_tags`, which strips tags that repeat the signature — document only what the signature can't say. Imported short class names in docblocks. Descriptions only when they add context, as full sentences.
- One `use` per trait, each on its own line.
- String interpolation (`"Hi, I am {$name}."`), not concatenation or `sprintf`.
- Method chains: once a chain breaks lines, every `->` goes on its own line; never mix.
- Ternaries: each part on its own line unless very short.
- `if`: always braces; unhappy path first with an early return, happy path last; avoid `else`; prefer separate early-return `if`s over compound `&&` conditions.
- Comments: avoid them by extracting a well-named function; `// ` with a space.
- Whitespace: blank line between statements (except runs of similar one-liners, like `$table->...` in a migration); no blank line right after `{` or before `}`.
- Config files kebab-case, keys snake_case; `env()` only in config; service credentials in `config/services.php`.
- Artisan command names kebab-case; `handle()` ends with feedback (`$this->comment(...)`), per-item output plus a summary.
- Routes: URLs kebab-case without a leading `/`; tuple syntax `[Controller::class, 'index']`; names camelCase; HTTP verb first (`Route::get(...)->name(...)`); parameters camelCase. API resources plural, kebab-case, shallow nesting.
- Controllers: only the CRUD methods (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`); other actions get their own controller (`PinController@store/destroy`, like Spatie's `FavoritePostsController`). Spatie names resource controllers in the plural (`PostsController`); this repo uses the singular (`NoteController`) — keep matching the existing names.
- Validation rules as arrays (`['required', 'email']`), never pipe strings. Custom rule names snake_case.
- Views camelCase. Blade: 4 spaces, `@if($condition)` with no space.
- Authorization abilities camelCase CRUD words (`view` instead of `show`).
- Translations with `__()`, not `@lang`.
- Class names: Jobs = the action (`CreateUser`), Events by tense (`ApprovingLoan`/`LoanApproved`), Listeners `...Listener`, Commands `...Command`, Mailables `...Mail`, Resources plural + `Resource`.
- Tests: helper classes in the same test file when possible. (This repo uses Pest, so Spatie's `/** @test */` doesn't apply.)
- **(user)** Negation is `!$value`, no space (Spatie writes `! $value`). `pint.json` turns off the Laravel preset's `not_operator_with_successor_space`, so Pint strips the space. Never `!!$value` (Pint rewrites it to `(bool) $value`).
- **(user)** Never compare with `=== null` / `!== null` to ask whether something is set; use truthiness (`!$note->{$column}`, `(bool) $this->pinned_at`, `->when($filters->q, ...)`). Use `?->` when calling into a value that may be null (`$this->created_at?->toIso8601String()`).
- **(user)** Prefer Laravel's idioms over hand-rolled conditions: `??=`, and Eloquent skipping a `save()` when nothing is dirty, instead of guard clauses like `if (! $a === ! $b) return;`. Name methods for the action (`pin()`/`unpin()`), not generic toggles (`mark($note, $column, $on)`).

JavaScript / TypeScript:
- Prettier decides formatting; 4-space indent, single quotes. Spatie uses `printWidth: 120`; this repo's `.prettierrc` uses 150 — the repo file wins.
- `const` by default, `let` only when reassigned, never `var`.
- No abbreviated names, except in one-line arrow callbacks where the context is obvious.
- `===` always; cast first if types may differ.
- Named functions use the `function` keyword; short one-liners and higher-order helpers may be arrows; anonymous callbacks are arrows (`function` only when `this` is needed). Keep functions pure, limit `this`. Object methods use shorthand (`handleClick(event) {}`).
- Prefer object/array destructuring over indexing (`const [hours, minutes] = time.split(':')`), and for options-style parameters.

## No Redundant Comments
- Don't write comments (inline, PHPDoc/JSDoc, file or block headers) that restate what the code, its names or its types already say. This applies to new code and to code you touch.
- Only comment a non-obvious *why*: a workaround, a constraint from outside the code, a deliberate trade-off. If nothing like that exists, write no comment.
- PHPDoc is still fine where it adds type information PHP can't express (array shapes, generics, `@mixin`, `@property`) — as tags only, without a prose line restating the method name. Laravel boilerplate like "Run the migrations." goes too.
- Tool directives stay (`// @vitest-environment jsdom`, `/** @uses ... */`), and so does the `//` body in spatie Data constructors (the convention in every package).

## Scope of Replies
- Do the one task in the prompt. Nothing else.
- Do not give opinions, suggestions, analysis, or "next steps" unless explicitly asked for them.
- Do not mention things that weren't asked about, even if noticed while working (e.g. unrelated bugs, inconsistencies). Stay silent on them unless asked.
- After making a change, don't restate/explain what was changed if it adds no value (the user can read the diff). Keep end-of-turn text minimal or omit it.

## Package Migrations
- Package migrations are never published; each package runs its own from vendor. They are plain dated `.php` files, flat in `packages/<name>/database/migrations/` (`2026_11_07_053052_create_notes_table.php`), loaded in `bootingPackage()` with `$this->loadMigrationsFrom($this->package->basePath('/../database/migrations'));`. No `hasMigrations()`, `discoversMigrations()` or `runsMigrations()`.
- Why: package-tools gives a published copy a fresh `<now>_` timestamp unless a same-named file already sits in the target folder. The production container (`coleus/coleus` image, deployed by the separate `coleus` repo's `bin/pull`) recreates its filesystem on every deploy, so each publish produced new names and `migrate` failed with "table already exists". `runsMigrations()` with publishing also created the same table twice by design.
- A migration's file name is its identity in the `migrations` table: never rename one that has run anywhere. The fixed names match production: users and health `2025_12_02_0244xx`, apps and settings `2026_09_06_*`; notes, frame and music had never run on production when they were fixed.
- The local dev database still records the users migrations as `2025_09_14_225444`–`225446`; those rows need renaming to the `2025_12_02_024402`–`024404` names (a data step to confirm separately).
- The `coleus` repo publishes only config, assets and views, by tag, through its `bin/publish` (used by its `Dockerfile` and `bin/pull`). A new package with config/assets/views must be added to its package list there. Never go back to `vendor:publish --all`.
- New migrations get the current timestamp; order matters for foreign keys, since the migrator sorts every loaded path by file name.
- Don't pass `--path` to `migrate`/`migrate:fresh`: it replaces every other registered path, including the packages' `loadMigrationsFrom()` paths.

## Support Package (`packages/support`)
`coleus/support` (Composer, `Coleus\Support\`) / `@coleus/support` (pnpm workspace) is the shared base every app package (calendar, frame, health, music, notes, users) builds on. It has no routes, pages, migrations or config of its own; `SupportServiceProvider` only names the package. It holds things that are generic across packages — anything specific to one package stays in that package.

Frontend (imported as `@coleus/support/...`, alias defined in `base.config.js`):
- `base.config.js` — the shared Vite config each package's `vite.config.ts` merges (`mergeConfig(baseConfig, { base: '/vendor/<name>/', ... })`): Tailwind, laravel-vite-plugin (`resources/js/app.ts`, hot file in the app's `public/hot`), Vue, and Nuxt UI with the `Ui` component prefix, Inertia mode and the shared theme overrides. Build output goes to each package's `resources/dist`. A change here affects every package's build.
- `formkit.config.ts` / `formkit.theme.ts` — shared FormKit Pro plugin and theme, imported by packages' own `formkit.config.ts`.
- `layouts/AppLayout.vue` (dashboard shell: sidebar, navigation links, team/user menus) and `layouts/SimpleLayout.vue` (centred guest page).
- `components/` — generic UI: `card/Header`, `form/Form`, `table/*` (header, pagination, meta info, edit/delete actions), `ui/table/columns/*` (badge/icon columns), `ui/datepicker`, `ui/modal/ConfirmModal`, `TeamMenu`, `UserMenu`, `Calendar`, `Time`, `Weather`.
- `lib/inertia.ts` — `ToastType` and `onSuccessToast`/`onErrorToast` visit callbacks.
- `types/resource.ts` — `Resource<T>`, `Collection<T>` (with `Meta`) and `Request<T>` types matching Laravel API resources.

Backend:
- `ModelWithDefaults` / `PivotWithDefaults` — base models: everything fillable except the primary key, and the table name prefixed from the config key in `static::$tablePrefix` (e.g. `'notes.table_prefix'`).
- `Services\Service` — base CRUD service (`index`, `defaultQuery`, `store`, `update`, `destroy`, and `restore`/`forceDelete`/`emptyTrash` for soft-deleting models) over a `$model` and a spatie/laravel-data `$data` class; a `save()` method on the subclass overrides store/update. `Services\Concerns\CanBeOption` adds `options()` via `$optionResource`.
- `Resources\EnumResource` + `Contracts\HasLabel` + `Concerns\NullableResourceCollection` — `{label, value}` option lists, optionally with a leading "-- Select One --" null option.
- `Http\Middleware\HandleInertiaRequests` — base Inertia middleware sharing `name`, `config.timezone`, `auth.user` and `ziggy`. Each package extends it only to set its own root view (`protected $rootView = '<name>::app';`).
- `Console\Commands\MakeControllerCommand` — `make:coleus-controller {name?} {package?}`; not registered by any provider, so it isn't available in `artisan`.
- `PestHelpers.php` (autoloaded) — `registerColeusPestHelpers()`, called in `tests/Pest.php`, defines `assertSuccessfulGet/Post/Put/Delete`.

## Notes Package (`packages/notes`)
Scope and decisions the user has made; don't reintroduce what's listed as removed.
- **One table, one editor.** `notes` (`body`, `pinned_at`, timestamps, soft deletes) and a Tiptap editor. Canvas, Bases, archive, bookmarks and Recents were removed on purpose. The list views are `NoteView::Notes` and `NoteView::Trash` only.
- **The body is HTML, and may be null** (an empty note; `ConvertEmptyStringsToNull` sends `''` as null). The column, `NoteData`, `NoteText` and the TS types all allow null.
- **HTML details.** The editor loads `body` as HTML (`content: props.modelValue`) and emits `editor.getHTML()`; the server stores it as-is, with no sanitizing (the user's choice) and no Markdown anywhere. Old Markdown notes aren't converted — the user doesn't care about them. Tiptap's trailing node means the HTML often ends in `<p></p>`.
- **Title and snippet are derived on the server** by `Support\NoteText` from the text of the `p, h1–h6, pre` blocks (PHP 8.4 `Dom\HTMLDocument`): title = first block with text, snippet = the rest. Never stored; never computed on the client.
- **Search is simple.** `NoteService::search()` (private) is one `whereLike('body', "%{$terms}%")` — the whole search as one phrase. No word splitting, search class, model scope, FULLTEXT index, stopword or wildcard handling — the user removed all of that. It runs on the raw HTML body.
- **The server owns all ordering.** No client-side sorting; after an autosave the page reloads the list with `router.reload({ only: ['notes'] })`.
- **Autosave** (`composables/useAutosave.ts`): `save` is a debounced `useHttp().put()` (Inertia v3), triggered from the editor's `onUpdate` via `@update:model-value`. There's no request queue, no navigation guard, no keepalive/tab-close save and no syncing of server-side body changes into an open editor — the user removed each of these. `NoteEditor` is keyed by note id, so switching notes remounts it.
- **Bulk deletes must keep `UserScope`.** Emptying the trash is the base `Service::emptyTrash()`. `Note::query()->onlyTrashed()->forceDelete()` deletes through `getQuery()`, which skips global scopes and wipes every user's trash. Use `->toBase()->delete()`, which applies them. `HasUser` has no delete hook, so a single query loses nothing over deleting model by model.
- **No authorization for now.** `Gate::authorize` calls, `NotePolicy` and their tests were removed; the user will design authorization later. Ownership is still enforced by `UserScope` (foreign notes 404). Restore and force-delete no longer check that the note is in the trash.
- **No settings.** The Settings page, `NotePreferences`, the `Settings` facade, the sort menu (`SortController`/`SortRequest`) and the `NoteSort` enum were removed; every former preference is hard-wired to its default: list ordered pinned first, then last edited (`NoteService::defaultQuery()`), relative dates and snippets always shown, autosave delay 750 ms, text direction `auto`, readable width on.
- **Things have places (the health pattern).** Form Requests hold only `authorize`, `rules` and `messages`. Controllers pass `$request->validated()` to the service (`Notes::note()`) and return a response or resource — no logic. The service shapes the payload with a Data class (`NoteListData::from($payload)`) and does the work (`list()`, `filters()`, `listQuery()`, `search()`). Never add Form Request helpers like `noteData()`; if input needs adjusting, spatie's `prepareForPipeline()` on the Data class is the place. A controller may type-hint a second Form Request (`store(NoteRequest $request, NoteListRequest $list)`) to get the list filters validated.
- **Every page prop is an API resource** (`NoteResource`, `NoteDetailResource`, `NoteCountsResource`, `NoteFiltersResource`), never a raw array or Data object, so each arrives wrapped as `prop.data` (TS: `Resource<T>`). Build them with `::make()` / `::collection()`, never `new`.
- **Inertia v3** across the app (`inertiajs/inertia-laravel` ^3, `@inertiajs/vue3` ^3). Prefer Inertia APIs (`useHttp`, `router`) over raw `fetch`.
- **Shared UI lives in support** (`@coleus/support/components/...`), e.g. `components/ui/modal/ConfirmModal.vue`; notes-specific UI stays in `resources/js/components/notes`.
- **After a frontend change**, rebuild and publish: `cd packages/notes && npx vite build`, then `php artisan vendor:publish --tag=notes-assets --force`.

## Frame Package (`packages/frame`)
- **All logic lives in services**, reached through the `Frames` facade (`Coleus\Frame\Frames`): `Frames::frame()` (`FrameService`: CRUD on the base `Service` with `FrameData`, kiosk token create/regenerate returning a `Support\KioskToken`, `destroy()` also deleting the frame's images, `fetchable()` for the console), `Frames::kiosk()` (`KioskService`: token generate/hash, token lookup, artwork rotation), `Frames::artwork()` (`ArtworkService`: queue, fetch, store, prune, image folder). Controllers, the job, the command and models hold no logic; the `Frame` model is casts and relations only (no model events).
- **Services are singletons** (bound in `FrameServiceProvider::packageRegistered()`), so they must stay stateless (Octane).
- **Artworks come from `ArtworkSource`s** (`Contracts\ArtworkSource`: `key()`, `search()` → `Collection<ArtworkData>`, `image()` → bytes), listed in `config('frame.sources')` and each bound as a singleton. `ArtworkService` reads that config on every fetch (not tags, so tests can narrow it with `config([...])`) and asks each source. Sources: `ArtInstituteOfChicagoSource` (`aic`), `MetropolitanMuseumOfArtSource` (`met`), `ClevelandMuseumOfArtSource` (`cleveland`); nothing outside a source may be source-specific.
- **Each frame picks its sources** (`frames.sources`, a JSON list of source keys, at least one, validated against the configured keys). The edit page gets `sources` options (`{value, label}` from `ArtworkSource::name()`) and shows a `UiCheckboxGroup`, all checked for a new frame. A fetch asks only the frame's sources; a key no longer configured is ignored.
- **An artwork is identified by `source` + `source_id`** (unique per frame); `source` is the source's `key()`, which must never change. Images live at `frame/{frame id}/{key}/{source_id}.jpg`.
- **The Met** retired `/v1/search` on 2026-10-01; use `/v1.1/search` (`offset` + `limit` ≤ 10,000, max 500 per page). Its search ignores `isPublicDomain`, and only returns ids, so each result needs `/v1/objects/{id}` and is filtered on `isPublicDomain` + `primaryImage`. It has no mid-size image (only ~600 px or the original, which can be many megapixels). Its image CDN answers 406 to `Accept: application/json`, so `image()` overrides the API client's header with `accept('image/*')`.
- **`ImageFitter` refuses images too large to decode** in the remaining memory (`BytesPerDecodedPixel`), because running out of memory is a fatal error that would kill the whole fetch instead of skipping one image.
- **No authorization for now.** `FramePolicy`, its `Gate::policy()` registration and the `Gate::authorize` calls in `FrameController` were removed; the user will design authorization later. Ownership is still enforced by `UserScope` (foreign frames 404).
- **Fetching needs a queue worker.** `frame:fetch` (and its every-six-hours schedule) only queues `FetchFrameArtworks` on the `database` queue. Herd serves the app but runs no worker or scheduler, so jobs sit in `jobs` until `php artisan queue:work` (or `composer run dev`) runs. The scheduled fetch also needs `php artisan schedule:work`.
- The job is `ShouldBeUnique` per frame for 900 s, so re-running `frame:fetch` while a fetch for that frame is still queued silently adds nothing.

## Keeping This File Current
- After each change that teaches you something about the project (a decision, a removed feature, a convention, a gotcha), update the relevant section here in the same turn, without being asked.
