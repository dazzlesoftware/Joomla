# Repository change workflow

- Keep Academy, Blog, and Codex synchronized for every shared behavior change. Update all corresponding component, layout, media, module, and plugin sources while preserving family-specific identifiers and intentional differences.
- Check all three families for existing drift when making changes; do not assume packaging synchronizes source files.
- After source changes, run `build/build-family-installers.ps1` to rebuild every family component, module, plugin, full installer, and shared plugin package from current sources.
- Validate relevant source changes and package contents before reporting completion. ZIP files are ignored by Git and must be rebuilt locally.
- Deploy project changes to the user-authorized test site `C:\wamp64\www\Joomla` using `build/deploy-to-test-site.ps1 -SiteRoot 'C:\wamp64\www\Joomla'` after rebuilding. Include all families and shared plugins, then verify deployed files against source. This is standing authorization for test-site updates; preserve site configuration and user data.

- Use Joomla/Bootstrap form and button styling for new controls. Core list/text fields supply their standard classes automatically; componentlayout fields need `class="form-select"`. Custom inputs, selects, checkboxes/radios, and buttons must use the corresponding Bootstrap classes. Preserve intentional template styling and do not alter default Blog/List appearance as part of a control-styling fix.

- Use Font Awesome font icons for project UI icons, including placeholders and ratings. Editor buttons are an exception: retain Joomla original SVG icons for ported buttons and provide SVG icons for custom buttons so TinyMCE renders them reliably.
