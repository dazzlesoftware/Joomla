# Powered by credit implementation

The credit renders once after frontend component output through each product's site dispatcher. The shared `powered-by` layout within each component supports Joomla template overrides. Administrator, non-HTML, task, modal and pagebreak output is excluded.

Show Powered By is enabled by default under component Settings > General (the existing navigation fieldset). CSS is appended to the existing media/css/post-list-styles.css stylesheet.

These are independently installable components without a shared runtime library. Each uses the same synchronized implementation and reusable layout across its frontend views; no new shared dependency, module footer or system plugin was introduced.

## Modified files

- [academy/com_academy/site/src/Dispatcher/Dispatcher.php](G:/GitHub/Genesis/Joomla/academy/com_academy/site/src/Dispatcher/Dispatcher.php)
- [academy/com_academy/media/css/post-list-styles.css](G:/GitHub/Genesis/Joomla/academy/com_academy/media/css/post-list-styles.css)
- [academy/com_academy/admin/forms/settings.xml](G:/GitHub/Genesis/Joomla/academy/com_academy/admin/forms/settings.xml)
- [academy/com_academy/language/en-GB/com_academy.ini](G:/GitHub/Genesis/Joomla/academy/com_academy/language/en-GB/com_academy.ini)
- [academy/com_academy/site-language/en-GB/com_academy.ini](G:/GitHub/Genesis/Joomla/academy/com_academy/site-language/en-GB/com_academy.ini)
- [blog/com_blog/site/src/Dispatcher/Dispatcher.php](G:/GitHub/Genesis/Joomla/blog/com_blog/site/src/Dispatcher/Dispatcher.php)
- [blog/com_blog/media/css/post-list-styles.css](G:/GitHub/Genesis/Joomla/blog/com_blog/media/css/post-list-styles.css)
- [blog/com_blog/admin/forms/settings.xml](G:/GitHub/Genesis/Joomla/blog/com_blog/admin/forms/settings.xml)
- [blog/com_blog/language/en-GB/com_blog.ini](G:/GitHub/Genesis/Joomla/blog/com_blog/language/en-GB/com_blog.ini)
- [blog/com_blog/site-language/en-GB/com_blog.ini](G:/GitHub/Genesis/Joomla/blog/com_blog/site-language/en-GB/com_blog.ini)
- [codex/com_codex/site/src/Dispatcher/Dispatcher.php](G:/GitHub/Genesis/Joomla/codex/com_codex/site/src/Dispatcher/Dispatcher.php)
- [codex/com_codex/media/css/post-list-styles.css](G:/GitHub/Genesis/Joomla/codex/com_codex/media/css/post-list-styles.css)
- [codex/com_codex/admin/forms/settings.xml](G:/GitHub/Genesis/Joomla/codex/com_codex/admin/forms/settings.xml)
- [codex/com_codex/language/en-GB/com_codex.ini](G:/GitHub/Genesis/Joomla/codex/com_codex/language/en-GB/com_codex.ini)
- [codex/com_codex/site-language/en-GB/com_codex.ini](G:/GitHub/Genesis/Joomla/codex/com_codex/site-language/en-GB/com_codex.ini)

## Added files

- [academy/com_academy/site/layouts/powered-by.php](G:/GitHub/Genesis/Joomla/academy/com_academy/site/layouts/powered-by.php)
- [blog/com_blog/site/layouts/powered-by.php](G:/GitHub/Genesis/Joomla/blog/com_blog/site/layouts/powered-by.php)
- [codex/com_codex/site/layouts/powered-by.php](G:/GitHub/Genesis/Joomla/codex/com_codex/site/layouts/powered-by.php)
- [build/test-powered-by.php](G:/GitHub/Genesis/Joomla/build/test-powered-by.php)
- [build/POWERED-BY.md](G:/GitHub/Genesis/Joomla/build/POWERED-BY.md)

## Language strings

Each prefix `COM_ACADEMY_`, `COM_BLOG_`, and `COM_CODEX_` receives:

- `POWERED_BY`: Powered by
- `CREDIT_PRODUCT`: Genesis Academy / Genesis Blog / Genesis Codex
- `CREDIT_COMPANY`: Dazzle Software LLC.
- `CREDIT_COPYRIGHT`: © 2005–2027 %s All rights reserved.
- `SHOW_POWERED_BY_LABEL`: Show Powered By
- `SHOW_POWERED_BY_DESC`: Show the product credit below frontend component output.

The first four strings are in the frontend language files; the last two are in Administrator language files.

## Validation

Live frontend responses have exactly one credit with the correct product and both required link attributes. Dispatcher Show/Hide was tested for every family using in-memory settings only. Administrator login has no credit. PHP syntax, XML, installer content and deployed file checks passed. All family installers, modules and plugins were rebuilt and deployed to C:/wamp64/www/Joomla. Generated ZIP files are ignored by Git.
