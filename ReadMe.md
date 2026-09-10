# Joomla 6.1.3 Release Scripts

This repo builds three independent "post family" extensions — **Academy**, **Blog**, and
**Codex** — each a clone of Joomla's `com_content` plus a matching set of modules and
plugins, packaged as installable Joomla extensions under `dist/`.

All the scripts that do this live in **`dist/build/`** — run them from there (or with a
full path); every command below assumes your working directory is `dist/build/`.

## Directory layout

Each product gets its own **lowercase** folder under `dist/` (matching its Joomla
element name, e.g. `com_academy`):

```
dist/<family>/                      (academy, blog, or codex)
├── com_<family>/       component source (unzipped) — admin/, site/, api/, media/, language/
├── plugins/            flat plugin source folders (actionlog, blocks, ..., mailqueue, branding)
├── modules/            flat module source folders (archive, categories, ..., tagssimilar)
├── build/              staging only — package manifests + the zips each package bundles
│   ├── pkg_<family>/            final installer package staging
│   ├── plugins/                 "plugins" sub-package staging (pkg_<family>_integrations)
│   └── modules/                 "modules" sub-package staging (pkg_<family>_modules)
└── dist/               every final .zip — the actual build output
    ├── com_<family>.zip, pkg_<family>.zip
    ├── plugins.zip, modules.zip
    ├── plg_<family>_*.zip (27), mod_<family>_*.zip (9)
    ├── plg_task_<family>_mailqueue.zip, plg_system_<family>branding.zip
    ├── plg_system_<family>loader.zip
    └── (stray backup zips, if present)
```

`dist/` (the top one, `H:\Joomla_6.1.3-Stable-Full_Package\dist`) also holds source for
two extensions shared across all three products, not owned by any single one, in a
single shared **`dist/plugins/<group>/<element>/`** tree — the same shape this repo's
own top-level `plugins/` folder uses, and the same shape Joomla installs plugins into:

| Source | Staging | Final output |
|---|---|---|
| `dist/plugins/content/video/`, `dist/plugins/editors-xtd/video/` | `dist/build/video-feature/` | `dist/dist/pkg_video_feature.zip` |
| `dist/plugins/user/genesisprofile/` | *(none — single plugin, zipped as-is)* | `dist/dist/plg_user_genesisprofile.zip` |

`dist/dist/` is deliberately only for these two shared packages — each product's own
`pkg_<family>.zip` etc. still land in that product's own `dist/<family>/dist/`, unchanged.
Likewise `dist/plugins/` is only for shared, cross-product plugins — each product's own
plugin source stays in its own flat `dist/<family>/plugins/<element>/`, unaffected.

There used to be a shared `plg_system_articlefamilies.zip` too — it's been retired in
favor of a `plg_system_<family>loader` plugin per product (see `build-family-installers.ps1`
below), so a customer who only buys one product doesn't get a plugin that mentions the
other two.

Extension identifiers (Joomla element/extension names, namespaces, database folder/element
values) never change when files move — only filesystem locations and package zip names do.
No script in `dist/build/` hardcodes an absolute path — every one resolves its own
location from `$PSScriptRoot` and computes the repo root and `dist/` from there. The
sole exception is `deploy-to-test-site.ps1`'s `-SiteRoot`, which has no default and must
be passed explicitly every time — there's no way to infer where the test site lives.

## Build pipeline

Run everything with one command:

```powershell
.\build-family-installers.ps1
```

This is the orchestrator. It runs the four build scripts below in order, then for each
family assembles the branding and plugin-group-loader plugins, folds them and the
mailqueue plugin into the `plugins` sub-package, and zips the final `pkg_<family>.zip`.

Order of operations:

```
build-family-installers.ps1
├── build-components.ps1          (1) component source, per family
├── build-family-modules.ps1      (2) modules + modules.zip, per family
├── build-integration-plugins.ps1 (3) content plugins (needs (1) for post-* patch scripts)
├── build-mail-task-plugins.ps1   (4) mailqueue task plugin
└── (inline) build branding, merge mailqueue+branding into plugins.zip, build pkg_<family>.zip
```

Pass `-SkipBuild` to `build-family-installers.ps1` to only (re)assemble the final
`pkg_<family>.zip` packages from whatever is already in `dist/<family>/dist`, without
re-running the four sub-builds.

### Script reference

`dist/build/` intentionally carries only the scripts needed to build the packages and
push them to the test site: the five build scripts, `verify-family-parity.ps1` (a required
dependency of `build-components.ps1`, not optional — see below), the two shared-extension
build scripts (`build-video-feature.ps1`, `build-genesisprofile.ps1`), and
`deploy-to-test-site.ps1`. The internal `extensions/post-*/apply-*.ps1` patch scripts
that `build-components.ps1` also depends on stay where they are, next to the extension
source they patch (documented last, below — don't delete them either).

#### `build-family-installers.ps1`
**The main entry point — run this one.** Orchestrates the whole pipeline for Academy,
Blog, and Codex. Builds the `plg_system_<family>branding` plugin (generated inline from
`extensions/post-branding/`) and the `plg_system_<family>loader` plugin (generated inline
from `extensions/post-family-loader/` — imports that family's own custom plugin group on
`onAfterInitialise`, so a site with only one product installed never references the
other two), copies the mailqueue, branding, and loader plugin zips into the `plugins`
sub-package staging folder, appends them to `pkg_<family>_integrations.xml`, zips that to
`dist/<family>/dist/plugins.zip`, then assembles `dist/<family>/build/pkg_<family>/`
(component zip + `plugins.zip` + `modules.zip` + manifest + install script + language
files) into the final `dist/<family>/dist/pkg_<family>.zip`.
```powershell
.\build-family-installers.ps1              # full build
.\build-family-installers.ps1 -SkipBuild   # only re-assemble the final packages
```

#### `build-components.ps1`
Generates the `com_academy` / `com_blog` / `com_codex` component source into
`dist/<family>/com_<family>/` by cloning `com_content` and rewriting it (renaming
tables/classes/language keys, adding the block editor, quote/tab/accordion/column
blocks, native comments/polls/engagement/email-campaign settings, etc.). At the end of
each family's run it invokes the four `apply-*.ps1` patch scripts under `extensions/`
(see below) to finish isolating categories, tags, the editor, and post excerpts from
Joomla's shared `com_content`/`com_categories`/`com_tags` tables.
```powershell
.\build-components.ps1
```
At the very end of its run, `build-components.ps1` automatically calls
`verify-family-parity.ps1` (its sibling in `dist/build/`) as a build-correctness gate —
see below.

#### `verify-family-parity.ps1`
Not run by hand — invoked automatically by `build-components.ps1` after all three
components are generated. Confirms every `COM_<FAMILY>_*` language key referenced in
each component's admin/site PHP is actually defined in that component's `.ini` files.
Throws (failing the whole build) if any family has unresolved keys.

#### `build-family-modules.ps1`
Builds the 9 modules per family (`archive`, `categories`, `category`, `latest`, `news`,
`popular`, `posts`, `tagspopular`, `tagssimilar`) into `dist/<family>/modules/<mode>/`,
zips each individually to `dist/<family>/dist/mod_<family>_<mode>.zip`, stages the
`pkg_<family>_modules` sub-package manifest + those zips under
`dist/<family>/build/modules/`, and zips that to `dist/<family>/dist/modules.zip`.
```powershell
.\build-family-modules.ps1
```

#### `build-integration-plugins.ps1`
Builds the 27 content-integration plugins per family (ported from Joomla's `content`,
`finder`, `editors-xtd`, `workflow`, and `actionlog` plugin groups, plus the custom
`video` and `blocks` plugins) into `dist/<family>/plugins/<element>/`, zips each to
`dist/<family>/dist/plg_<family>_<element>.zip`, and stages them with a manifest
scaffold under `dist/<family>/build/plugins/` (the mailqueue, branding, and loader
plugins are added to that manifest later, by `build-family-installers.ps1`).
```powershell
.\build-integration-plugins.ps1
```

#### `build-mail-task-plugins.ps1`
Builds the `plg_task_<family>_mailqueue` plugin (email campaign queue processor) into
`dist/<family>/plugins/mailqueue/` and zips it to
`dist/<family>/dist/plg_task_<family>_mailqueue.zip`.
```powershell
.\build-mail-task-plugins.ps1
```

#### `build-video-feature.ps1`
Builds the shared (not per-family) native video editor button + content plugin from
source at `dist/plugins/{content,editors-xtd}/video/` into staging at
`dist/build/video-feature/`, zipped to `dist/dist/pkg_video_feature.zip`. Independent of
the family pipeline above — run standalone if you only need to rebuild this package.
(Note: `build-integration-plugins.ps1` also reads from `dist/plugins/{content,editors-xtd}/video/`
directly, to build each family's own `plg_<family>_video` plugin — that's a separate,
unrelated use of the same source files.)
```powershell
.\build-video-feature.ps1
```

#### `build-genesisprofile.ps1`
Builds the shared (not per-family) user profile plugin — adds an avatar upload field
used by all three components' post-listing layouts — from source at
`dist/plugins/user/genesisprofile/`, zipped to `dist/dist/plg_user_genesisprofile.zip`.
It's a single plugin, not a package, so there's no sub-manifest assembly: the source
folder's own `genesisprofile.xml` is the plugin manifest and it's zipped as-is.
```powershell
.\build-genesisprofile.ps1
```

#### `deploy-to-test-site.ps1`
Pushes the built extension **source** (not the installer zips) directly into the local
WAMP test site, matching its established install-directory layout — a raw file mirror
(`robocopy /MIR`) per component/plugin/module folder, plus the associated language
files. Doesn't touch the site's database — it assumes the extensions are already
installed/registered there and you're pushing updated code over them. `-SiteRoot` has no
default and is required every time — the script won't guess where your test site lives.
```powershell
.\deploy-to-test-site.ps1 -SiteRoot 'C:\wamp64\www\Joomla'
```
Run `build-family-installers.ps1` first — this script deploys whatever is currently in
`dist/<family>/`, it doesn't build anything itself.

> **Note on the `plg_system_<family>loader` plugins:** because this script only mirrors
> files, it can't make Joomla aware of a plugin that has never been installed before —
> that requires an actual install (upload `pkg_<family>.zip` through Extensions ▸ Manage,
> or Discover it) so a `#__extensions` row gets created and enabled. A test site that
> still has the old shared `plg_system_articlefamilies` plugin installed from before this
> split will keep working unaffected until you do that.

#### `extensions/post-*/apply-*.ps1`
Internal patch scripts — **not meant to be run by hand**. `build-components.ps1` invokes
all four automatically at the end of each family's build, passing `-Target` (the
family's `com_<family>` source folder) and `-Family` (the lowercase family name):

| Script | Purpose |
|---|---|
| `post-categories/apply-categories.ps1` | Points the component at its own native `#__<family>_categories` table instead of Joomla's shared `#__categories`, and adjusts category fields/routes/models accordingly. |
| `post-editor/apply-editor.ps1` | Wires up the component's editor field/toolbar behavior. |
| `post-nav/apply-excerpt.ps1` | Wires up post excerpt/summary handling for listings. |
| `post-tags/apply-tags.ps1` | Points the component at its own native tagging instead of Joomla's shared `#__contentitem_tag_map`. |

## Typical workflows

**Full rebuild + redeploy to the test site:**
```powershell
.\build-family-installers.ps1
.\deploy-to-test-site.ps1 -SiteRoot 'C:\wamp64\www\Joomla'
```

**Just rebuild a shared (not per-family) package:**
```powershell
.\build-video-feature.ps1
.\build-genesisprofile.ps1
```
