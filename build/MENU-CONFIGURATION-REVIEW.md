# Admin menu configuration review

Reviewed 30 October 2026. Scope: Blog, Codex and Academy; Archive is the organization baseline.

## Result

Shared menu metadata was synchronized across all three source trees. No pre-existing semantic drift was found after normalizing family identifiers. Existing parameter names, defaults, option values, requirements and conditional visibility were preserved for every retained field. Categories' unused controls were removed following explicit user approval.

Changes:

- Extended Archive's existing `globaldefaultlist` field usage to inherited menu lists. Joomla now displays the saved component value, or its declared settings-form default when available, in the Use Global option. The underlying storage and option values remain unchanged.
- Ordered styled Category and Tags tabs around Slider, Posts, Compact Posts and Automated Truncation. Category ordering, pagination and post presentation controls now share the Posts tab. Category header/subcategory and Tags directory controls remain separate.
- Moved Tags access-preview and toolbar controls alongside its post presentation controls; normalized Slider, Posts and Compact Posts labels.
- Corrected My Posts' untranslated tab label and named the legacy Category List's post display tab Posts.
- Removed 51 unused controls from each Categories menu form. Retained the root category request, four layout controls, six directory controls and post navigation. The category directory renderer did not consume the removed single-category, post-list, compact-post, list-table, pagination or feed controls. No stored menu parameters were deleted.
- Fixed Category styled-listing parameter precedence: blank post settings inherit, explicit menu settings win, and Use Post resolves per-post values with component fallback. Legacy List keeps its distinct explicit per-post precedence, while blank values inherit. Styled Category intro output now follows Show Intro Text.
- Fixed an undefined optional image-alt property in all nine shared/Archive/Tags featured-image layout copies. Category fallback images retain empty alt text without PHP warnings.

## Menu-by-menu findings

Each row applies to all three families.

| Menu | Purpose and intentional differences | Verification |
| --- | --- | --- |
| Archive | Archived posts, month/year filters and sorting. Layout/count choices intentionally remain component-managed. | Menu construction, defaults, option bindings, filters, pagination, 15 item/layout combinations, seven slider styles, five compact modes, display controls and post navigation. |
| Author | A post listing filtered by authors; legacy single-author URLs remain supported. Its current inclusion filter can select multiple authors. | Menu construction, defaults, bindings, include/exclude filters, legacy routing, post display, pagination, sliders, compact posts and navigation. |
| Authors | An author directory, with its own directory layout and page size, plus optional slider/compact posts. | Menu construction, defaults, bindings, published/access-filtered author counts, directory output, pagination and compact selections. |
| Categories | A category directory rooted at a selected category. Its depth, empty-category, description and count controls differ from Archive. | Menu construction, defaults, bindings, approved editor cleanup, directory/tree output, nesting, styles, empty states and routing. |
| Category | Posts within a category or configured category filters, with a category header/subcategories. Legacy List remains distinct from the styled Category Posts layouts. | All seven metadata variants; layout aliases, filters/order/limits, category routing, subcategories, explicit menu precedence and rendered intro visibility. |
| Featured | A published-post listing with featured inclusion behavior; feed integration remains distinct. | Menu construction, defaults, bindings, component-managed choices, slider/excerpts, SEF IDs and navigation routing. |
| Form | Frontend creation/editing: category preset and submit/cancel redirects. No Archive listing tabs are appropriate. | All five menu fields render; option bindings and category preset state; submission access and real saves rolled back after confirming Pending Review. Redirect consumers were inspected in the controller. |
| My Posts | The signed-in user's management table, rather than a public post listing. | Menu binding, real view ownership/non-trashed filtering and toolbar Hide/Show behavior; untranslated tab fixed. |
| Single Post | One selected post; default and wiki layouts remain distinct. No slider, compact-list or truncation tabs were added. | Both metadata forms, bindings/defaults, single-post rating visibility and navigation inheritance/rendering. |
| Tags | Directory when no tag is selected; post listing for a selected tag. Directory controls and prefixed post-list controls intentionally coexist. | Menu construction, defaults/bindings, directory/exclusions, selected-tag scope, pagination, layout/slider/compact rendering and access previews. |

## Validation evidence

- 51 XML layout forms checked across the three families, including alternate Category and Single Post layouts.
- 2,997 explicit option bindings and 8,514 inherited default/saved-option label cases passed.
- 3,492 retained field contracts matched the pre-change audit; only the 153 approved Categories controls were removed.
- 27 PHP regression scripts passed against the deployed test site. No warnings or fatal errors were present in the final runtime log.
- Authenticated browser checks opened the 33 existing audit menu editors, covering all requested menu types in every family. The Academy Category Posts mismatch below was identified rather than silently changed. Final Categories, Category List and My Posts tabs were rechecked after deployment; Tags Posts received a visual check.
- All family components, modules, plugins, full installers and shared plugin packages were rebuilt with `build-family-installers.ps1`.
- Deployment completed for all families and shared plugins using `deploy-to-test-site.ps1` with explicit repository and site roots. Explicit `-DistRoot` avoided Windows PowerShell's empty `$PSScriptRoot` during default parameter evaluation.
- SHA-256 comparisons verified 2,102 deployed files against source. Byte comparisons verified 2,099 packaged source entries, including nested module/plugin archives and component archives inside full installers.
- `git diff --check` passed.

Regression additions: `test-menu-configuration.php`, `test-category-menu-settings.php`, and `test-dashboard-menu-settings.php`. The existing listing-menu-controls check now applies post-list controls to post-list menu types; the new Categories contract check requires its directory-only controls.

Local detailed logs: `build/settings-audit/menu-form-validation.log`, `menu-runtime.log`, `menu-build.log`, and `menu-deploy.log` (generated/ignored).

## Reference and overlap assessment

The local EasyBlog installation was inspected at `C:/wamp64/www/easyblog`. Its blogger default/listings, category default/listings, Archive, composer/dashboard and tag metadata support keeping directory, listing and authoring purposes distinct. Its component-specific field types and parameter names were not copied into these components.

Keep these separate:

- Author versus Authors: posts versus author directory.
- Category versus Categories: posts versus category directory.
- Slider ratings, primary post ratings and compact ratings: separate rendered regions.
- Post navigation versus item navigation: component toolbar versus previous/next post links.
- Read More visibility versus truncation/read-more-boundary controls: link presentation versus excerpt generation.
- Category named layout files and Single Post wiki/default: compatibility and template override entry points with distinct layouts; shared metadata alone does not justify deleting them.

## Proposals still requiring approval

1. **Repair Academy menu item 9971, “Category — category_posts”.** Its saved link is `index.php?option=com_academy&view=featured`, so it currently duplicates a Featured route rather than exercising Category Posts. Changing it to Category Posts would change which page/settings it uses. Confirm whether to restore the Category Posts route and which category/filter should be used. No link was changed.
2. **Retire the legacy Category author selectors after a compatibility decision.** `list_author_filtering_type`/`list_author` overlap with the newer `listing_authors`/`listing_exclude_authors` controls. The current custom Category view uses the newer filters, while these older selectors remain exposed. Removing their editor fields would simplify the UI; supporting old stored selections instead would require an explicit precedence/migration rule. They were retained.
3. **Clarify the Author menu title.** “Single Author” predates its multi-author inclusion filter. Renaming it to “Author Posts” would describe current behavior more accurately, without merging it with Authors. The existing title was retained.

The approved Categories cleanup is complete and is not an outstanding proposal. No menu types were deleted, merged or consolidated.

## Limits and remaining checks

- Browser review inspected existing saved menu values without saving over them. Form binding tests cover explicit zero values; this was not an exhaustive save/reopen browser round trip for every field.
- Form submit/cancel destination logic was source-reviewed, but a full interactive redirect matrix was not executed. Submission save/access behavior and category presets were runtime-tested.
- Single Post wiki and additional Category layout metadata were constructed and checked; not every layout/option combination received a browser visual inspection.
- Category's legacy List retains table-oriented controls inherited from an older implementation. Its lightweight default renderer does not consume every table control. Styled Category display tests specifically cover Hide/Show precedence and intro visibility, rather than asserting that every legacy list control works.
- No exhaustive browser test of disabled voting-feature options, feed output, language associations or every slider timing combination was performed. Joomla can omit options whose required feature is disabled; the label test respects those omissions.

All changes are local source edits and rebuilt local packages, deployed to `C:/wamp64/www/Joomla`. Site configuration and saved user/menu data were not intentionally modified by the implementation.
