<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.23
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/framework (LARAVEL) - v12
- laravel/octane (OCTANE) - v2
- laravel/prompts (PROMPTS) - v0
- tightenco/ziggy (ZIGGY) - v2
- larastan/larastan (LARASTAN) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/vue3 (INERTIA) - v2
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `pnpm run build`, `pnpm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== herd rules ===

## Laravel Herd

- The application is served by Laravel Herd and will be available at: `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate URLs for the user to ensure valid URLs.
- You must not run any commands to make the site available via HTTP(S). It is always available through Laravel Herd.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

## Inertia

- Inertia.js components should be placed in the `resources/js/pages` directory unless specified differently in the JS bundler (`vite.config.js`).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use the `search-docs` tool for accurate guidance on all things Inertia.

<code-snippet name="Inertia Render Example" lang="php">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>

=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 and v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Deferred props.
- Infinite scrolling using merging props and `WhenVisible`.
- Lazy loading data on scroll.
- Polling.
- Prefetching.

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing/animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use the `search-docs` tool with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use the `search-docs` tool with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use the `search-docs` tool with a query of `form component resetting` for guidance.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `pnpm run build` or ask the user to run `pnpm run dev` or `composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version-specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest
### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest {name}`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests that have a lot of duplicated data. This is often the case when testing validation rules, so consider this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>

=== inertia-vue/core rules ===

## Inertia + Vue

- Vue components must have a single root element.
- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="vue">

    import { Link } from '@inertiajs/vue3'
    <Link href="/">Home</Link>

</code-snippet>

=== inertia-vue/v2/forms rules ===

## Inertia v2 + Vue Forms

<code-snippet name="`<Form>` Component Example" lang="vue">

<Form
    action="/users"
    method="post"
    #default="{
        errors,
        hasErrors,
        processing,
        progress,
        wasSuccessful,
        recentlySuccessful,
        setError,
        clearErrors,
        resetAndClearErrors,
        defaults,
        isDirty,
        reset,
        submit,
  }"
>
    <input type="text" name="name" />

    <div v-if="errors.name">
        {{ errors.name }}
    </div>

    <button type="submit" :disabled="processing">
        {{ processing ? 'Creating...' : 'Create User' }}
    </button>

    <div v-if="wasSuccessful">User created successfully!</div>
</Form>

</code-snippet>

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v4 rules ===

## Tailwind CSS 4

- Always use Tailwind CSS v4; do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.

<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>

### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option; use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
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
- Package migrations are published, not run from the package. Each package keeps undated stubs in `packages/<name>/database/migrations/<name>/create_*_table.php.stub` and lists them in order with `->hasMigrations(['<name>/create_..._table', ...])`.
- Publishing (`php artisan vendor:publish --tag=<name>-migrations`) copies them into the app's `database/migrations/<name>/` with the publish date, in the listed order. The published files are committed; each package gets its own subfolder so they don't mix in `database/migrations/`.
- Don't use `->runsMigrations()` with stubs: it calls `loadMigrationsFrom()` on the `.php.stub` path, and Laravel's migrator ignores paths that don't end in `.php`, so nothing runs.
- Instead, the package's service provider loads the published folder in `bootingPackage()`: `$this->loadMigrationsFrom(database_path('migrations/<name>'));`. Plain `php artisan migrate`, `migrate:fresh` and `RefreshDatabase` then pick them up with no `--path` and no test changes.
- Don't pass `--path` to `migrate`/`migrate:fresh` to reach a subfolder: it replaces every other registered path, including other packages' `loadMigrationsFrom()` paths.
- Order matters for foreign keys: the migrator sorts by file name, so it's the published timestamps (from the `hasMigrations()` order) that run a parent table before its children.
- Moving a package from dated migrations to stubs gives the migration a new name, so an existing database still has the old name in its `migrations` table and will try to create the table again. Renaming that row is a data step to confirm separately.
- Reference implementations: `packages/notes`, `packages/health`.

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

## Keeping This File Current
- After each change that teaches you something about the project (a decision, a removed feature, a convention, a gotcha), update the relevant section here in the same turn, without being asked.
