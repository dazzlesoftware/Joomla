# Joomla 6.1.3 Release Scripts

This repo holds three independent, hand-maintained "post family" extensions —
**Academy**, **Blog**, and **Codex** — each a clone of Joomla's `com_content` plus a
matching set of modules and plugins, packaged as installable Joomla extensions.

The component/plugin/module source under `academy/`, `blog/`, and `codex/` (and the two
shared extensions under `plugins/`) is the real source of truth — edit it directly. This
repo used to *generate* that source by cloning and rewriting a stock Joomla core tree on
every build; that generation step has been retired (see "History" below) — the scripts
now only package what's already on disk and push it to a local test site.

All the scripts that do this live in **`build/`** — run them from there (or with a full
path); every command below assumes your working directory is `build/`.

## Directory layout

Each product gets its own **lowercase** folder at the repo root (matching its Joomla
element name, e.g. `com_academy`):

```
<family>/                      (academy, blog, or codex — at the repo root)
├── com_<family>/       component source — admin/, site/, api/, media/, language/
├── plugins/            flat plugin source folders (actionlog, blocks, ..., mailqueue, branding, loader)
├── modules/            flat module source folders (archive, categories, ..., tagssimilar)
├── build/              staging only — package manifests + the zips each package bundles
│   ├── pkg_<family>/            final installer package staging
│   ├── plugins/                 "plugins" sub-package staging (pkg_<family>_integrations)
│   └── modules/                 "modules" sub-package staging (pkg_<family>_modules)
└── dist/               every final .zip — the actual package output
    ├── com_<family>.zip, pkg_<family>.zip
    ├── plugins.zip, modules.zip
    ├── plg_<family>_*.zip (27), mod_<family>_*.zip (9)
    ├── plg_task_<family>_mailqueue.zip, plg_system_<family>branding.zip
    ├── plg_system_<family>loader.zip
    └── (stray backup zips, if present)
```

The repo's own top-level `plugins/` folder holds source for two extensions shared across
all three products, not owned by any single one, in the same `plugins/<group>/<element>/`
shape Joomla installs plugins into:

| Source | Staging | Final output |
|---|---|---|
| `plugins/content/video/`, `plugins/editors-xtd/video/` | `build/video-feature/` | `dist/pkg_video_feature.zip` |
| `plugins/user/genesisprofile/` | *(none — single plugin, zipped as-is)* | `dist/plg_user_genesisprofile.zip` |

The top-level `dist/` is deliberately only for these two shared packages — each product's
own `pkg_<family>.zip` etc. still land in that product's own `<family>/dist/`, unchanged.

There used to be a shared `plg_system_articlefamilies.zip` too — it's been retired in
favor of a `plg_system_<family>loader` plugin per product, so a customer who only buys
one product doesn't get a plugin that mentions the other two.

Extension identifiers (Joomla element/extension names, namespaces, database folder/element
values) don't change when files move — only filesystem locations and package zip names
do. No script in `build/` hardcodes an absolute path — every one resolves its own
location from `$PSScriptRoot` and computes the repo root from there. The sole exception
is `deploy-to-test-site.ps1`'s `-SiteRoot`, which has no default and must be passed
explicitly every time — there's no way to infer where the test site lives.

## History: this used to be a generated tree

Up through 2026-09, `academy/`, `blog/`, and `codex/` were **generated** on every build by
cloning a stock Joomla 6.1.3 core install and a set of `extensions/post-*` patch scripts
(both living outside this repo, in the old `H:\Joomla_6.1.3-Stable-Full_Package` tree that
this repo's `dist/` folder used to be nested inside of), rewriting `com_content` and
Joomla's stock plugins into the Academy/Blog/Codex equivalents. That generation pipeline
(`build-components.ps1`, `build-family-modules.ps1`, `build-integration-plugins.ps1`,
`build-mail-task-plugins.ps1`, `build-video-feature.ps1`, `build-genesisprofile.ps1`,
`verify-family-parity.ps1`) has been **deleted**. Now that this code is shared publicly,
each extension's source is fixed up by hand directly under `academy/`, `blog/`, `codex/`,
and `plugins/` — there's no external Joomla core tree this repo depends on anymore.

## Packaging + deploy

Run everything with one command:

```powershell
.\build-family-installers.ps1
```

This zips up whatever currently exists under `academy/`, `blog/`, `codex/`, and
`plugins/` into installable Joomla packages — it does not generate or modify any source.
For each family it zips the component, every plugin under `<family>/plugins/` (content
plugins, the mailqueue task plugin, the branding and loader system plugins), and every
module under `<family>/modules/`, assembles the `pkg_<family>_integrations.xml` /
`pkg_<family>_modules.xml` sub-package manifests, and zips the final `pkg_<family>.zip`.
It also (re)builds the two shared packages, `pkg_video_feature.zip` and
`plg_user_genesisprofile.zip`.

### Script reference

`build/` carries only two scripts now: `build-family-installers.ps1` (packaging) and
`deploy-to-test-site.ps1` (push to the local test site).

#### `build-family-installers.ps1`
**The main entry point — run this one.** For each of Academy, Blog, and Codex: zips
`com_<family>`, every plugin folder under `<family>/plugins/` (as `plg_<family>_<element>.zip`,
except `mailqueue` → `plg_task_<family>_mailqueue.zip`, `branding` →
`plg_system_<family>branding.zip`, and `loader` → `plg_system_<family>loader.zip`), and
every module folder under `<family>/modules/` (as `mod_<family>_<mode>.zip`); assembles
those into `plugins.zip` and `modules.zip` sub-packages; then assembles
`<family>/build/pkg_<family>/` (component zip + `plugins.zip` + `modules.zip` + manifest +
install script + language files) into the final `<family>/dist/pkg_<family>.zip`. Also
rebuilds the two shared packages (`pkg_video_feature.zip`, `plg_user_genesisprofile.zip`)
from the repo's top-level `plugins/` folder.
```powershell
.\build-family-installers.ps1
```

#### `deploy-to-test-site.ps1`
Pushes the packaged extension **source** (not the installer zips) directly into the local
WAMP test site, matching its established install-directory layout — a raw file mirror
(`robocopy /MIR`) per component/plugin/module folder, plus the associated language
files. Doesn't touch the site's database — it assumes the extensions are already
installed/registered there and you're pushing updated code over them. `-SiteRoot` has no
default and is required every time — the script won't guess where your test site lives.
```powershell
.\deploy-to-test-site.ps1 -SiteRoot 'C:\wamp64\www\Joomla'
```
Run `build-family-installers.ps1` first — this script deploys whatever is currently in
`<family>/`, it doesn't build anything itself.

> **Note on the `plg_system_<family>loader` plugins:** because this script only mirrors
> files, it can't make Joomla aware of a plugin that has never been installed before —
> that requires an actual install (upload `pkg_<family>.zip` through Extensions ▸ Manage,
> or Discover it) so a `#__extensions` row gets created and enabled. A test site that
> still has the old shared `plg_system_articlefamilies` plugin installed from before this
> split will keep working unaffected until you do that.

## Typical workflow

**Repackage + redeploy to the test site, after hand-editing extension source:**
```powershell
.\build-family-installers.ps1
.\deploy-to-test-site.ps1 -SiteRoot 'C:\wamp64\www\Joomla'
```

## Category layout styles

Choose List, Blog, Standard, Card, Learning, Simple, or Nickel under the component's
Category Layouts settings. Category List and Category Blog menus offer a Category
Layout selector with Use Global; selecting a named layout menu type is an explicit
override. Card, Learning, Simple, Nickel, and Standard share the category blog shell
and its pagination and post sublayouts.

The former global List item style selector is retained only as a hidden compatibility
value for saved configurations. Featured menus still offer Featured item style for
independent featured-page styling. Category layouts and featured pages load the same
`media/css/post-list-styles.css` file. The `site/layouts/postlist/` folders remain shared
rendering helpers, not separate category layout selectors.
