# Joomla settings audit — 23 September 2026

Academy, Blog, and Codex were audited against the local Joomla test site. Confirmed defects were fixed in all three source families, installers rebuilt, and the complete families plus shared plugins deployed to `C:\wamp64\www\Joomla`.

This is a coverage report, **not certification that every possible setting combination works**. Render checks, behavior assertions, browser checks, and untested integrations are distinguished below. Layout restructuring and removal of questionable controls remain a subsequent step.

## Confirmed defects fixed

| Area | Defect | Result |
|---|---|---|
| Categories directory | Parent selection, depth, empty-category visibility, and descriptions were not consistently applied. | Uses the existing subcategory tree helper with directory-specific settings; published/access/language filters and scheduled-post counts are retained. |
| Categories directory | Link-list output ignored row/column and masonry settings. | Both link lists and image grids use the selected arrangement and column count. |
| Categories directory | Show Page Heading was ignored. | The heading follows the setting and uses a translatable fallback. |
| Category Posts | Ordering controls were ignored by the lightweight query. | Category order, post order, and ordering date now control that query, including ratings and featured ordering. |
| Card metadata | Ratings, hits, and sharing controls needed consistent handling. | Hits/sharing respect their flags. Follow-up correction on 24 September: card rating summaries use `engagement_ratings`, independently of the legacy `show_vote` control; native comment counts remain visible independently of the submission provider, restoring the previous card appearance. |
| Share links | Subdirectory installations produced duplicated `/Joomla/` paths. | Sharing uses Joomla's absolute route generation. |
| Vote controls | Fields and queries checked the core `content/vote` plugin instead of the product's vote plugin. | Academy, Blog, and Codex check their own plugin groups. |
| Vote rendering | Lightweight post rows lacked rating properties, producing PHP warnings. | The vote plugin obtains missing rating data; its template safely handles absent values. |
| Read More | Some category truncation options accessed a missing `alternative_readmore` property. | Missing alternative text falls back without warnings. |
| Menu types | Joomla excluded the underscore-named `category_posts` layout from automatic menu-type discovery, leaving the required type label blank. | Explicit view metadata registers Category Posts and Category List. Existing layout names are retained, without adding a duplicate style choice. |

## Coverage and results

- **33 test menu items:** 11 menu types per family, under Main Menu > Extension Tests. See [MENUS.md](MENUS.md).
- **251 distinct setting names** inventoried across **3,840 field occurrences**. See [SETTINGS.md](SETTINGS.md). Static references alone do not establish working behavior.
- **5,373 parameter-value render cases** completed without fatal errors. These exercise enumerated options and representative numeric boundaries, one setting at a time. Component controls use a representative listing, so a successful render is not proof of effect on every view.
- **18 cases initially emitted Read More warnings.** All 18 passed targeted rechecks without warnings after the fix (`matrix-rechecks.json`). The original matrix retains the original observations.
- **216 behavior assertions passed** (`effects.json`): category descriptions/depth/empty visibility, heading visibility, six category post styles, ordering, credits, compact title/image/rating/link controls, pagination and summary, directory arrangements, publication dates, sharing flags/URLs, and authenticated form/My Posts rendering.
- **147 boolean output comparisons** completed without warnings: 105 changed output; 42 require context-specific interpretation. An unchanged result is not automatically a defect.
- **3,837 Administrator fields** rendered through Joomla's Form API without construction errors (`forms.json`). This does not replace browser validation of conditional controls.
- **33/33 menu types** recognized by Joomla's own reverse lookup (`menu-types.json`). The fixture setup also omits explicit `layout=default`, as Joomla's menu picker does.
- **21 existing regression runs passed**, covering truncation, aliases, categories, empty rendering, routing, featured slider, listing options, mocked neural-network integration, credits, subcategory styles, view subtemplates, and votes.
- **46 changed PHP files** passed syntax checks; `git diff --check` passed.
- **392 package/deployment checks, 3,643 file comparisons, zero mismatches.** This includes component, module, plugin, nested full-installer ZIP contents, shared plugins, and installed source files.
- Family source drift was reviewed. Remaining differences in multi-family references are intentional; changed corresponding source files match after family-name substitution.

## Browser checks

The authenticated Administrator UI successfully saved and reopened Academy Card/Masonry/3-column settings, Blog List settings, and Codex Standard settings on the audit menus. The selected styles persisted.

Show Powered By was toggled, saved, and reopened through each product's component settings UI, then restored to its original enabled state. No API key was entered or revealed.

Frontend browser checks verified directory navigation, an empty category's no-posts message, pagination changing the visible posts, a compact link opening its post, loaded images, and corrected sharing URLs. A normal-width screenshot was inspected. A requested narrow viewport did not take effect in the browser, so mobile-width visual testing is **not** recorded as passed.

## Cleanup candidates for the next step

| Setting | Finding | Recommendation |
|---|---|---|
| `summary_limit` | Archive metadata exposes it, but no active frontend PHP consumer was found. | Consolidate with automated truncation or implement an explicit archive override. |
| `urls_position` | Exposed in configuration; no active frontend consumer was found. | Remove unless an accompanying post-URL feature is intended. |
| `show_cat_tags` | Exposed but no active category tag output consumes it. | Define real category-tag behavior or remove the control. Category default tags currently serve post-assignment behavior. |
| `info_block_show_title` | Used by the legacy info-block layout, but no effect in the tested default post details output. | Restrict it to layouts that support it or unify the metadata rendering contract. |
| Conditional controls | Wiki details, parent-category links, tags, language flags, unauthorized-content display, and read-more controls require specific layouts/data. | Improve inline help and conditional visibility rather than classifying all unchanged comparisons as broken. |
| Hit recording | A request-side effect, not an HTML toggle. | Keep separate from Show Hits; test storage behavior explicitly before changing it. |

These controls were not removed as part of the audit. They are recorded for the planned cleanup/layout phase.

## Limits and outstanding validation

- Exhaustive Cartesian combinations of all settings were not run. Text values, arbitrary custom layouts, template overrides, and custom provider/model plugins have an unbounded input space.
- Live AI/image generation, provider billing/quota behavior, outbound email, autoposting, and third-party comment integrations were not exercised. Neural-network tests use mocks; no paid requests or external messages were sent.
- Guest and existing authenticated rendering were covered, but a complete multi-role ACL matrix and multilingual association workflow were not performed.
- The audit does not establish behavior for every custom template, theme, browser, viewport, or installed third-party plugin.
- Settings with no literal source reference may be consumed dynamically or by Joomla itself. Consult the source and relevant context before deleting them.

## Reproduction and artifacts

Local evidence is in this directory: `inventory.json`, `matrix.json`, `matrix-rechecks.json`, `effects.json`, `forms.json`, `menu-types.json`, `behavior-scan.json`, `regressions-final.json`, `lint.json`, and `verification.json`. Generated JSON/log files are ignored by Git. [CHANGES.md](CHANGES.md) lists every changed or added extension source file.

Run `setup.php` only against this authorized local test site to create/reuse fixture content. It leaves existing user content intact. `extra-fixtures.php` adds archived, unpublished, and future-dated fixture posts. The audit leaves 33 test menus and their Settings Audit categories/posts available for further testing.

Run `effects.py`, `run-matrix.py`, `forms.php`, `menu-types.php`, and `verify.py` after building/deploying. PHP scripts use the local PHP 8.3 runtime and Joomla installation; Python scripts use the locally installed Python. `render.php` changes request parameters in memory rather than saving each test value.

## Follow-up regression correction — 24 September 2026

The audit incorrectly coupled read-only card summaries to voting-widget and comment-provider settings, hiding existing stars and comment counts. This is corrected in all three families without enabling comment submission or changing saved configuration. `build/test-card-summaries.php` passed all 18 provider/rating combinations against deployed output. Installers were rebuilt and all 3,643 package/deployment comparisons passed. The older full effects runner cannot currently complete because some saved audit menu IDs no longer exist; its historical results above describe the original audit run.
