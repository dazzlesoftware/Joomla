# View-specific frontend subtemplates

Academy, Blog and Codex each provide independent Joomla subtemplates under `site/tmpl`.
They delegate to the existing shared layouts by default, preserving existing layout overrides.

- `post/default_author.php`, `default_comments.php`, `default_details.php`, `default_engagement.php`, `default_wiki_body.php`, `default_navigation.php`
- `category/card_header.php`, `card_title.php`, `card_image.php`, `card_placeholder.php`, `card_tags.php`, `card_meta.php`, `card_footer.php`
- `category/card_actions.php`, `card_subcategories.php`, `card_navigation.php`, plus their `default_*` equivalents for the list layout
- `featured/default_header.php`, `default_title.php`, `default_image.php`, `default_placeholder.php`, `default_tags.php`, `default_meta.php`, `default_footer.php`, `default_navigation.php`
- `author/default_navigation.php`

For example, copy `site/tmpl/category/card_header.php` to
`templates/YOUR_TEMPLATE/html/com_academy/category/card_header.php` and replace its render call with your markup.
This changes category listing headers without changing featured listing headers or the single post page.
Use `com_blog` or `com_codex` for the other products. Item subtemplates receive `$this->item` and its `params`;
category section subtemplates receive `$this->params` and `$this->subcategories`.

Category standard, simple, learning and nickel layouts currently select the card shell and therefore use `card_*` parts.
Wiki uses Joomla's `default_*` fallback; create `post/wiki_author.php`, for example, for a wiki-only author override.
Shared `site/layouts` files remain the common implementation, used by these delegates and other callers.
Existing full template overrides remain authoritative and must opt into the new subtemplates if desired.
The frontend Powered by credit remains at dispatcher level and is not duplicated by these subtemplates.
