# TryPost — Code Review Instructions

Laravel 13 + Inertia 3 (Vue 3 + TypeScript) + Tailwind 4 app. Flag violations of these project conventions. Prioritize correctness, security, and these rules over style nits the linters (Pint/ESLint) already catch. Cite `file:line`.

## Backend (PHP / Laravel)

- Validation MUST live in a `FormRequest` subclass under `app/Http/Requests/App/<Group>/` (or `Api/`), type-hinted in the controller action. Flag any inline `$request->validate([...])` in controllers.
- JSON API responses MUST use an Eloquent API Resource (`JsonResource`). Flag `response()->json([...])` that maps models inline.
- In Action/service classes, use `data_get($data, 'key', $default)` — flag direct array access like `$data['key']`.
- Use `Symfony\Component\HttpFoundation\Response` constants (e.g. `Response::HTTP_CREATED`), never magic numbers like `201`.
- Imports at the top via `use`; flag inline refs like `\DB::`, `\Str::`.
- Prefer double-quoted interpolation with curly braces: `"workspace.{$id}"`, not `'workspace.'.$id`.
- Schema changes go in NEW migrations — the app is in production. Flag edits to existing migration files.
- Never pass a disk name to `Storage::` or `->store()` — use the framework default.
- Third-party API hosts / OAuth URLs come from `config/trypost.php` (`platforms.<name>`). Flag hardcoded hosts like `https://api.x.com`.
- PHP 8: constructor property promotion, explicit return types + param type hints, `declare(strict_types=1)`, curly braces on all control structures.
- AI agent prompts live in Blade under `resources/views/prompts/`. Flag heredocs/long string prompts inside `instructions()`.

## Frontend (Vue / TypeScript)

- Arrow functions only — flag `function` declarations.
- Icons from `@tabler/icons-vue` (Icon-prefixed). Flag `lucide-vue-next`.
- Dates: use `@/dayjs` and `@/date` helpers — flag raw `new Date()`.
- Routes: use Wayfinder helpers (from `@/routes` / `@/actions`) — flag hardcoded URL strings like `href="/register"`.
- No HTML5 validation attributes (`required`, `minlength`, `pattern`, ...) — rely on backend validation.
- Vue components must have a single root element.

## Pagination

- Use `->paginate()` (never `cursorPaginate()`). Lists use Inertia scroll (`Inertia::scroll()` + `<InfiniteScroll>`) — flag page-number/link pagination.

## Tests (required)

- Every behavioral change needs a test (new or updated). Flag logic changes shipped without tests.
- Feature & Dusk tests MUST use named routes via `route('...')` — flag hardcoded URL strings. Dusk interacts via `@dusk` selectors, not CSS classes or text.

## Git

- No `Co-Authored-By` lines or AI attribution in commit messages or PR descriptions.
