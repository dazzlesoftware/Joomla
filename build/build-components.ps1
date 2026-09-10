param([string]$SourceRoot = (Split-Path (Split-Path $PSScriptRoot -Parent) -Parent))

$ErrorActionPreference = 'Stop'
$components = @(
    @{ Name = 'blog'; Title = 'Blog' },
    @{ Name = 'codex'; Title = 'Codex' },
    @{ Name = 'academy'; Title = 'Academy' }
)

function Replace-InTree([string]$Path, [array]$Replacements) {
    $extensions = @('.php', '.xml', '.ini', '.sql', '.json')
    Get-ChildItem -LiteralPath $Path -Recurse -File | Where-Object { $extensions -contains $_.Extension } | ForEach-Object {
        $text = [IO.File]::ReadAllText($_.FullName)
        foreach ($replacement in $Replacements) { $text = $text.Replace($replacement[0], $replacement[1]) }
        [IO.File]::WriteAllText($_.FullName, $text, [Text.UTF8Encoding]::new($false))
    }
}

function Convert-ArticleCodeToPosts([string]$Path) {
    $textExtensions = @('.php', '.xml', '.ini', '.sql', '.json', '.js', '.css')
    Get-ChildItem -LiteralPath $Path -Recurse -File | Where-Object { $textExtensions -contains $_.Extension } | ForEach-Object {
        $text = [IO.File]::ReadAllText($_.FullName)
        $text = $text.Replace('ARTICLES', 'POSTS').Replace('ARTICLE', 'POST')
        $text = $text.Replace('Articles', 'Posts').Replace('Article', 'Post')
        $text = $text.Replace('articles', 'posts').Replace('article', 'post')
        [IO.File]::WriteAllText($_.FullName, $text, [Text.UTF8Encoding]::new($false))
    }

    Get-ChildItem -LiteralPath $Path -Recurse -Force | Sort-Object { $_.FullName.Length } -Descending | ForEach-Object {
        $newName = $_.Name.Replace('ARTICLES', 'POSTS').Replace('ARTICLE', 'POST').Replace('Articles', 'Posts').Replace('Article', 'Post').Replace('articles', 'posts').Replace('article', 'post')
        if ($newName -ne $_.Name) { Rename-Item -LiteralPath $_.FullName -NewName $newName }
    }
}

function Convert-LegacyContentStorage([string]$Path) {
    $textExtensions = @('.php', '.xml', '.ini', '.sql', '.json', '.js', '.css')
    Get-ChildItem -LiteralPath $Path -Recurse -File | Where-Object { $textExtensions -contains $_.Extension } | ForEach-Object {
        $text = [IO.File]::ReadAllText($_.FullName)
        $text = $text.Replace('POSTTEXT', 'POST_CONTENT').Replace('Posttext', 'Post Content').Replace('posttext', 'post_content')
        $text = $text.Replace('INTROTEXT', 'SUMMARY').Replace('Introtext', 'Summary').Replace('introtext', 'summary')
        $text = $text.Replace('FULLTEXT', 'BODY').Replace('Fulltext', 'Body').Replace('fulltext', 'body')
        $text = $text.Replace('->images', '->media').Replace("['images']", "['media']").Replace("'images'", "'media'").Replace('"images"', '"media"').Replace('`images`', '`media`').Replace('a.images', 'a.media')
        $text = $text.Replace('->attribs', '->options').Replace("['attribs']", "['options']").Replace("'attribs'", "'options'").Replace('"attribs"', '"options"').Replace('`attribs`', '`options`').Replace('a.attribs', 'a.options')

        # Link A/B/C were removed. Remove the inherited storage access as well while
        # leaving unrelated URL variables (media downloads and modal routes) alone.
        $text = [regex]::Replace($text, '(?m)^.*(?:a\.urls|\$item->urls|\$data\[''urls''\]|\$row->urls|\$this->item->urls|\$tmp->urls).*\r?\n?', '')
        $text = [regex]::Replace($text, '(?m)^\s*["'']urls["''],?\s*\r?\n?', '')
        $text = [regex]::Replace($text, '(?m)^\s*`urls`\s+text\s+NOT\s+NULL,\s*\r?\n?', '')
        [IO.File]::WriteAllText($_.FullName, $text, [Text.UTF8Encoding]::new($false))
    }
}

foreach ($component in $components) {
    $name = $component.Name
    $title = $component.Title
    $upper = $name.ToUpperInvariant()
    $target = Join-Path $SourceRoot "dist/$name/com_$name"

    if (Test-Path -LiteralPath $target) { Remove-Item -LiteralPath $target -Recurse -Force }
    New-Item -ItemType Directory -Path "$target/admin/sql", "$target/site", "$target/api", "$target/media", "$target/language/en-GB", "$target/site-language/en-GB" | Out-Null
    Copy-Item -Path "$SourceRoot/administrator/components/com_content/*" -Destination "$target/admin" -Recurse
    Copy-Item -Path "$SourceRoot/components/com_content/*" -Destination "$target/site" -Recurse
    Copy-Item -Path "$SourceRoot/api/components/com_content/*" -Destination "$target/api" -Recurse
    Copy-Item -Path "$SourceRoot/media/com_content/*" -Destination "$target/media" -Recurse
    Copy-Item -Path "$SourceRoot/extensions/post-dashboard/src/*" -Destination "$target/admin/src" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-dashboard/tmpl/*" -Destination "$target/admin/tmpl" -Recurse -Force
    New-Item -ItemType Directory -Path "$target/site/layouts" -Force | Out-Null
    Copy-Item -LiteralPath "$SourceRoot/extensions/post-comments/layouts/comments.php" -Destination "$target/site/layouts/comments.php"
    Copy-Item -Path "$SourceRoot/extensions/post-comments/site/src/*" -Destination "$target/site/src" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-comments/admin/src/*" -Destination "$target/admin/src" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-comments/admin/tmpl/*" -Destination "$target/admin/tmpl" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-polls/admin/src/*" -Destination "$target/admin/src" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-polls/admin/tmpl/*" -Destination "$target/admin/tmpl" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-polls/site/src/*" -Destination "$target/site/src" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-import/admin/src/*" -Destination "$target/admin/src" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-import/admin/tmpl/*" -Destination "$target/admin/tmpl" -Recurse -Force
    Copy-Item -LiteralPath "$SourceRoot/extensions/post-engagement/layouts/engagement.php" -Destination "$target/site/layouts/engagement.php"
    Copy-Item -Path "$SourceRoot/extensions/post-engagement/site/src/*" -Destination "$target/site/src" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-mail/admin/src/*" -Destination "$target/admin/src" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-mail/admin/tmpl/*" -Destination "$target/admin/tmpl" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-autopost/admin/src/*" -Destination "$target/admin/src" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-autopost/admin/tmpl/*" -Destination "$target/admin/tmpl" -Recurse -Force
    Copy-Item -LiteralPath "$SourceRoot/extensions/post-nav/layouts/postnav.php" -Destination "$target/site/layouts/postnav.php"
    New-Item -ItemType Directory -Path "$target/site/layouts/postlist" -Force | Out-Null
    Copy-Item -Path "$SourceRoot/extensions/post-nav/layouts/postlist/*" -Destination "$target/site/layouts/postlist" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-nav/site/src/*" -Destination "$target/site/src" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-nav/site/tmpl/*" -Destination "$target/site/tmpl" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-settings/admin/src/*" -Destination "$target/admin/src" -Recurse -Force
    Copy-Item -Path "$SourceRoot/extensions/post-settings/admin/tmpl/*" -Destination "$target/admin/tmpl" -Recurse -Force
    New-Item -ItemType Directory -Path "$target/site/layouts/joomla/content" -Force | Out-Null
    Copy-Item -LiteralPath "$SourceRoot/layouts/joomla/content/blog_style_default_item_title.php" -Destination "$target/site/layouts/joomla/content/blog_style_default_item_title.php"
    Copy-Item -LiteralPath "$SourceRoot/layouts/joomla/content/featured_image.php" -Destination "$target/site/layouts/joomla/content/featured_image.php"
    Copy-Item -LiteralPath "$SourceRoot/administrator/language/en-GB/com_content.ini" -Destination "$target/language/en-GB/com_$name.ini"
    Copy-Item -LiteralPath "$SourceRoot/administrator/language/en-GB/com_content.sys.ini" -Destination "$target/language/en-GB/com_$name.sys.ini"
    Copy-Item -LiteralPath "$SourceRoot/language/en-GB/com_content.ini" -Destination "$target/site-language/en-GB/com_$name.ini"

    Rename-Item -LiteralPath "$target/admin/content.xml" -NewName "$name.xml"
    Rename-Item -LiteralPath "$target/admin/src/Extension/ContentComponent.php" -NewName "${title}Component.php"

    $replacements = @(
        @('#__contentitem_tag_map', '__JOOMLA_CONTENTITEM_TAG_MAP__'),
        @('com_contenthistory', '__JOOMLA_CONTENTHISTORY__'),
        @('COM_CONTENTHISTORY', '__JOOMLA_CONTENTHISTORY_LANG__'),
        @('#__content_frontpage', "#__${name}_frontpage"),
        @('#__content_rating', "#__${name}_rating"),
        @('#__content', "#__${name}"),
        @('com_content', "com_$name"),
        @('COM_CONTENT', "COM_$upper"),
        @('\\Joomla\\Component\\Content', "\\Joomla\\Component\\$title"),
        @('Joomla\Component\Content', "Joomla\Component\$title"),
        @('ContentComponent', "${title}Component"),
        @('use Joomla\CMS\Table\Content;', "use Joomla\Component\$title\Administrator\Table\ArticleTable;"),
        @('new Content(', 'new ArticleTable('),
        @("Categories::getInstance('Content'", "Categories::getInstance('$title'"),
        @('contentadministrator', "${name}administrator"),
        @('contenticon', "${name}icon"),
        @('media/com_content', "media/com_$name"),
        @("PluginHelper::importPlugin('content'", "PluginHelper::importPlugin('$name'"),
        @("PluginHelper::importPlugin('workflow'", "PluginHelper::importPlugin('$name'"),
        @('__JOOMLA_CONTENTITEM_TAG_MAP__', '#__contentitem_tag_map'),
        @('__JOOMLA_CONTENTHISTORY__', 'com_contenthistory'),
        @('__JOOMLA_CONTENTHISTORY_LANG__', 'COM_CONTENTHISTORY')
    )
    Replace-InTree $target $replacements
    Replace-InTree $target @(@('FAMILYNAME', $upper), @('FamilyName', $title), @('familyname', $name), @('FamilyTitle', $title))

    New-Item -ItemType Directory -Path "$target/media/js", "$target/media/css" -Force | Out-Null
    Copy-Item -LiteralPath "$SourceRoot/extensions/article-blocks/admin-block-editor.js" -Destination "$target/media/js/admin-block-editor.js"
    Copy-Item -LiteralPath "$SourceRoot/extensions/article-blocks/admin-block-editor.css" -Destination "$target/media/css/admin-block-editor.css"

    [xml]$articleForm = Get-Content -LiteralPath "$target/admin/forms/article.xml" -Raw
    $linkFields = $articleForm.SelectSingleNode('//fields[@name="urls"]')
    if ($linkFields) { [void]$linkFields.ParentNode.RemoveChild($linkFields) }
    $fullImageFields = $articleForm.SelectSingleNode('//fieldset[@name="image-full"]')
    if ($fullImageFields) { [void]$fullImageFields.ParentNode.RemoveChild($fullImageFields) }
    $featuredImageFields = $articleForm.SelectSingleNode('//fieldset[@name="image-intro"]')
    if ($featuredImageFields) { $featuredImageFields.SetAttribute('name', 'image-featured') }
    $articleTextField = $articleForm.form.fieldset.field | Where-Object { $_.name -eq 'articletext' } | Select-Object -First 1
    $stateField = $articleForm.form.fieldset.field | Where-Object { $_.name -eq 'state' } | Select-Object -First 1
    $pendingOption = $articleForm.CreateElement('option'); $pendingOption.SetAttribute('value', '-3'); $pendingOption.InnerText = "COM_${upper}_POST_PENDING"; [void]$stateField.AppendChild($pendingOption)
    $blockField = $articleForm.CreateElement('field'); $blockField.SetAttribute('name', 'block_data'); $blockField.SetAttribute('type', 'hidden'); $blockField.SetAttribute('filter', 'raw'); [void]$articleTextField.ParentNode.InsertAfter($blockField, $articleTextField)
    $articleForm.Save("$target/admin/forms/article.xml")

    [xml]$siteArticleForm = Get-Content -LiteralPath "$target/site/forms/article.xml" -Raw
    $siteLinkFields = $siteArticleForm.SelectSingleNode('//fields[@name="urls"]')
    if ($siteLinkFields) { [void]$siteLinkFields.ParentNode.RemoveChild($siteLinkFields) }
    $siteFullImageFields = $siteArticleForm.SelectSingleNode('//fieldset[@name="image-full"]')
    if ($siteFullImageFields) { [void]$siteFullImageFields.ParentNode.RemoveChild($siteFullImageFields) }
    $siteFeaturedImageFields = $siteArticleForm.SelectSingleNode('//fieldset[@name="image-intro"]')
    if ($siteFeaturedImageFields) { $siteFeaturedImageFields.SetAttribute('name', 'image-featured') }
    $siteArticleForm.Save("$target/site/forms/article.xml")

    [xml]$configXml = Get-Content -LiteralPath "$target/admin/config.xml" -Raw
    foreach ($linkTarget in @('targeta', 'targetb', 'targetc')) {
        foreach ($fieldNode in @($configXml.SelectNodes("//field[@name='$linkTarget']"))) {
            [void]$fieldNode.ParentNode.RemoveChild($fieldNode)
        }
    }
    foreach ($fullImageSetting in @($configXml.SelectNodes('//field[@name="float_fulltext"]'))) {
        [void]$fullImageSetting.ParentNode.RemoveChild($fullImageSetting)
    }
    $editorSet = $configXml.CreateElement('fieldset'); $editorSet.SetAttribute('name', 'block_editor'); $editorSet.SetAttribute('label', 'COM_' + $upper + '_CONFIG_EDITOR_LABEL')
    $quoteSet = $configXml.CreateElement('fieldset'); $quoteSet.SetAttribute('name', 'quote_settings'); $quoteSet.SetAttribute('label', 'Quote Settings')
    $tabSet = $configXml.CreateElement('fieldset'); $tabSet.SetAttribute('name', 'tab_settings'); $tabSet.SetAttribute('label', 'Tab Settings')
    $accordionSet = $configXml.CreateElement('fieldset'); $accordionSet.SetAttribute('name', 'accordion_settings'); $accordionSet.SetAttribute('label', 'Accordion Settings')
    $columnSet = $configXml.CreateElement('fieldset'); $columnSet.SetAttribute('name', 'column_settings'); $columnSet.SetAttribute('label', 'Column Settings')
    $defaultEditor = $configXml.CreateElement('field'); $defaultEditor.SetAttribute('name', 'default_editor_mode'); $defaultEditor.SetAttribute('type', 'list'); $defaultEditor.SetAttribute('default', 'classic'); $defaultEditor.SetAttribute('label', 'COM_' + $upper + '_CONFIG_DEFAULT_EDITOR_LABEL')
    foreach ($optionData in @(@('classic', "COM_${upper}_EDITOR_CLASSIC"), @('blocks', "COM_${upper}_EDITOR_BLOCKS"))) { $option = $configXml.CreateElement('option'); $option.SetAttribute('value', $optionData[0]); $option.InnerText = $optionData[1]; [void]$defaultEditor.AppendChild($option) }
    [void]$editorSet.AppendChild($defaultEditor)
    $quoteTemplate = $configXml.CreateElement('field'); $quoteTemplate.SetAttribute('name', 'quote_template'); $quoteTemplate.SetAttribute('type', 'list'); $quoteTemplate.SetAttribute('default', 'simple'); $quoteTemplate.SetAttribute('label', 'Quote Template')
    foreach ($optionData in @(@('simple', 'Simple Bootstrap'), @('color', 'Color Block'), @('framed', 'Framed'), @('card', 'Quote Card'), @('panel', 'Dark Panel'), @('minimal', 'Minimal'))) { $option = $configXml.CreateElement('option'); $option.SetAttribute('value', $optionData[0]); $option.InnerText = $optionData[1]; [void]$quoteTemplate.AppendChild($option) }
    [void]$quoteSet.AppendChild($quoteTemplate)
    $quoteColorMode = $configXml.CreateElement('field'); $quoteColorMode.SetAttribute('name', 'quote_color_mode'); $quoteColorMode.SetAttribute('type', 'list'); $quoteColorMode.SetAttribute('default', 'bootstrap'); $quoteColorMode.SetAttribute('label', 'Quote Color Source')
    foreach ($optionData in @(@('bootstrap', 'Bootstrap Color'), @('custom', 'Custom Color'))) { $option = $configXml.CreateElement('option'); $option.SetAttribute('value', $optionData[0]); $option.InnerText = $optionData[1]; [void]$quoteColorMode.AppendChild($option) }; [void]$quoteSet.AppendChild($quoteColorMode)
    $quoteBootstrapColor = $configXml.CreateElement('field'); $quoteBootstrapColor.SetAttribute('name', 'quote_bootstrap_color'); $quoteBootstrapColor.SetAttribute('type', 'list'); $quoteBootstrapColor.SetAttribute('default', 'primary'); $quoteBootstrapColor.SetAttribute('label', 'Bootstrap Quote Color'); $quoteBootstrapColor.SetAttribute('showon', 'quote_color_mode:bootstrap')
    foreach ($colorName in @('primary','secondary','success','danger','warning','info','light','dark')) { $option = $configXml.CreateElement('option'); $option.SetAttribute('value', $colorName); $option.InnerText = (Get-Culture).TextInfo.ToTitleCase($colorName); [void]$quoteBootstrapColor.AppendChild($option) }; [void]$quoteSet.AppendChild($quoteBootstrapColor)
    $quoteCustomColor = $configXml.CreateElement('field'); $quoteCustomColor.SetAttribute('name', 'quote_custom_color'); $quoteCustomColor.SetAttribute('type', 'color'); $quoteCustomColor.SetAttribute('default', '#0d6efd'); $quoteCustomColor.SetAttribute('label', 'Custom Quote Color'); $quoteCustomColor.SetAttribute('showon', 'quote_color_mode:custom'); [void]$quoteSet.AppendChild($quoteCustomColor)
    $tabTemplate = $configXml.CreateElement('field'); $tabTemplate.SetAttribute('name', 'tab_template'); $tabTemplate.SetAttribute('type', 'list'); $tabTemplate.SetAttribute('default', 'classic'); $tabTemplate.SetAttribute('label', 'Tab Template'); foreach($optionData in @(@('classic','Classic Bootstrap'),@('pills','Pills'),@('underline','Underline'),@('cards','Card Tabs'),@('colorbar','Color Bar'),@('icons','Icon Tabs'))){$option=$configXml.CreateElement('option');$option.SetAttribute('value',$optionData[0]);$option.InnerText=$optionData[1];[void]$tabTemplate.AppendChild($option)};[void]$tabSet.AppendChild($tabTemplate)
    $tabMode = $configXml.CreateElement('field'); $tabMode.SetAttribute('name', 'tab_mode'); $tabMode.SetAttribute('type', 'list'); $tabMode.SetAttribute('default', 'horizontal'); $tabMode.SetAttribute('label', 'Tab Orientation'); foreach($optionData in @(@('horizontal','Horizontal'),@('vertical','Vertical'))){$option=$configXml.CreateElement('option');$option.SetAttribute('value',$optionData[0]);$option.InnerText=$optionData[1];[void]$tabMode.AppendChild($option)};[void]$tabSet.AppendChild($tabMode)
    $tabColorMode = $configXml.CreateElement('field'); $tabColorMode.SetAttribute('name', 'tab_color_mode'); $tabColorMode.SetAttribute('type', 'list'); $tabColorMode.SetAttribute('default', 'bootstrap'); $tabColorMode.SetAttribute('label', 'Tab Color Source'); foreach($optionData in @(@('bootstrap','Bootstrap Color'),@('custom','Custom Color'))){$option=$configXml.CreateElement('option');$option.SetAttribute('value',$optionData[0]);$option.InnerText=$optionData[1];[void]$tabColorMode.AppendChild($option)};[void]$tabSet.AppendChild($tabColorMode)
    $tabBootstrapColor = $configXml.CreateElement('field'); $tabBootstrapColor.SetAttribute('name', 'tab_bootstrap_color'); $tabBootstrapColor.SetAttribute('type', 'list'); $tabBootstrapColor.SetAttribute('default', 'primary'); $tabBootstrapColor.SetAttribute('label', 'Bootstrap Tab Color'); $tabBootstrapColor.SetAttribute('showon', 'tab_color_mode:bootstrap'); foreach($colorName in @('primary','secondary','success','danger','warning','info','light','dark')){$option=$configXml.CreateElement('option');$option.SetAttribute('value',$colorName);$option.InnerText=(Get-Culture).TextInfo.ToTitleCase($colorName);[void]$tabBootstrapColor.AppendChild($option)};[void]$tabSet.AppendChild($tabBootstrapColor)
    $tabCustomColor = $configXml.CreateElement('field'); $tabCustomColor.SetAttribute('name', 'tab_custom_color'); $tabCustomColor.SetAttribute('type', 'color'); $tabCustomColor.SetAttribute('default', '#0d6efd'); $tabCustomColor.SetAttribute('label', 'Custom Tab Color'); $tabCustomColor.SetAttribute('showon', 'tab_color_mode:custom'); [void]$tabSet.AppendChild($tabCustomColor)
    $accordionTemplate = $configXml.CreateElement('field'); $accordionTemplate.SetAttribute('name', 'accordion_template'); $accordionTemplate.SetAttribute('type', 'list'); $accordionTemplate.SetAttribute('default', 'classic'); $accordionTemplate.SetAttribute('label', 'Accordion Template'); foreach($optionData in @(@('classic','Classic Bootstrap'),@('separated','Separated Cards'),@('numbered','Numbered Process'),@('minimal','Minimal FAQ'),@('color-panel','Color Panel'),@('gradient-card','Gradient Card'),@('compact','Compact Dark'),@('two-column','Two Columns'))){$option=$configXml.CreateElement('option');$option.SetAttribute('value',$optionData[0]);$option.InnerText=$optionData[1];[void]$accordionTemplate.AppendChild($option)};[void]$accordionSet.AppendChild($accordionTemplate)
    $accordionColorMode = $configXml.CreateElement('field'); $accordionColorMode.SetAttribute('name', 'accordion_color_mode'); $accordionColorMode.SetAttribute('type', 'list'); $accordionColorMode.SetAttribute('default', 'bootstrap'); $accordionColorMode.SetAttribute('label', 'Accordion Color Source'); foreach($optionData in @(@('bootstrap','Bootstrap Color'),@('custom','Custom Color'))){$option=$configXml.CreateElement('option');$option.SetAttribute('value',$optionData[0]);$option.InnerText=$optionData[1];[void]$accordionColorMode.AppendChild($option)};[void]$accordionSet.AppendChild($accordionColorMode)
    $accordionBootstrapColor = $configXml.CreateElement('field'); $accordionBootstrapColor.SetAttribute('name', 'accordion_bootstrap_color'); $accordionBootstrapColor.SetAttribute('type', 'list'); $accordionBootstrapColor.SetAttribute('default', 'primary'); $accordionBootstrapColor.SetAttribute('label', 'Bootstrap Accordion Color'); $accordionBootstrapColor.SetAttribute('showon', 'accordion_color_mode:bootstrap'); foreach($colorName in @('primary','secondary','success','danger','warning','info','light','dark')){$option=$configXml.CreateElement('option');$option.SetAttribute('value',$colorName);$option.InnerText=(Get-Culture).TextInfo.ToTitleCase($colorName);[void]$accordionBootstrapColor.AppendChild($option)};[void]$accordionSet.AppendChild($accordionBootstrapColor)
    $accordionCustomColor = $configXml.CreateElement('field'); $accordionCustomColor.SetAttribute('name', 'accordion_custom_color'); $accordionCustomColor.SetAttribute('type', 'color'); $accordionCustomColor.SetAttribute('default', '#0d6efd'); $accordionCustomColor.SetAttribute('label', 'Custom Accordion Color'); $accordionCustomColor.SetAttribute('showon', 'accordion_color_mode:custom'); [void]$accordionSet.AppendChild($accordionCustomColor)
    $columnTemplate = $configXml.CreateElement('field'); $columnTemplate.SetAttribute('name', 'column_template'); $columnTemplate.SetAttribute('type', 'list'); $columnTemplate.SetAttribute('default', 'equal'); $columnTemplate.SetAttribute('label', 'Column Template'); foreach($optionData in @(@('equal','Equal Columns'),@('sidebar-left','Left Sidebar (1/3 + 2/3)'),@('sidebar-right','Right Sidebar (2/3 + 1/3)'),@('cards','Card Columns'),@('bordered','Bordered Columns'),@('color','Color Columns'),@('gapless','Gapless Split'),@('feature','Feature + Supporting'))){$option=$configXml.CreateElement('option');$option.SetAttribute('value',$optionData[0]);$option.InnerText=$optionData[1];[void]$columnTemplate.AppendChild($option)};[void]$columnSet.AppendChild($columnTemplate)
    $columnGap = $configXml.CreateElement('field'); $columnGap.SetAttribute('name', 'column_gap'); $columnGap.SetAttribute('type', 'list'); $columnGap.SetAttribute('default', '3'); $columnGap.SetAttribute('label', 'Column Gap'); foreach($optionData in @(@('0','None'),@('1','Extra Small'),@('2','Small'),@('3','Medium'),@('4','Large'),@('5','Extra Large'))){$option=$configXml.CreateElement('option');$option.SetAttribute('value',$optionData[0]);$option.InnerText=$optionData[1];[void]$columnGap.AppendChild($option)};[void]$columnSet.AppendChild($columnGap)
    $columnAlign = $configXml.CreateElement('field'); $columnAlign.SetAttribute('name', 'column_vertical_align'); $columnAlign.SetAttribute('type', 'list'); $columnAlign.SetAttribute('default', 'start'); $columnAlign.SetAttribute('label', 'Vertical Alignment'); foreach($optionData in @(@('start','Top'),@('center','Center'),@('end','Bottom'),@('stretch','Stretch'))){$option=$configXml.CreateElement('option');$option.SetAttribute('value',$optionData[0]);$option.InnerText=$optionData[1];[void]$columnAlign.AppendChild($option)};[void]$columnSet.AppendChild($columnAlign)
    $columnColorMode = $configXml.CreateElement('field'); $columnColorMode.SetAttribute('name', 'column_color_mode'); $columnColorMode.SetAttribute('type', 'list'); $columnColorMode.SetAttribute('default', 'bootstrap'); $columnColorMode.SetAttribute('label', 'Column Color Source'); foreach($optionData in @(@('bootstrap','Bootstrap Color'),@('custom','Custom Color'))){$option=$configXml.CreateElement('option');$option.SetAttribute('value',$optionData[0]);$option.InnerText=$optionData[1];[void]$columnColorMode.AppendChild($option)};[void]$columnSet.AppendChild($columnColorMode)
    $columnBootstrapColor = $configXml.CreateElement('field'); $columnBootstrapColor.SetAttribute('name', 'column_bootstrap_color'); $columnBootstrapColor.SetAttribute('type', 'list'); $columnBootstrapColor.SetAttribute('default', 'primary'); $columnBootstrapColor.SetAttribute('label', 'Bootstrap Column Color'); $columnBootstrapColor.SetAttribute('showon', 'column_color_mode:bootstrap'); foreach($colorName in @('primary','secondary','success','danger','warning','info','light','dark')){$option=$configXml.CreateElement('option');$option.SetAttribute('value',$colorName);$option.InnerText=(Get-Culture).TextInfo.ToTitleCase($colorName);[void]$columnBootstrapColor.AppendChild($option)};[void]$columnSet.AppendChild($columnBootstrapColor)
    $columnCustomColor = $configXml.CreateElement('field'); $columnCustomColor.SetAttribute('name', 'column_custom_color'); $columnCustomColor.SetAttribute('type', 'color'); $columnCustomColor.SetAttribute('default', '#0d6efd'); $columnCustomColor.SetAttribute('label', 'Custom Column Color'); $columnCustomColor.SetAttribute('showon', 'column_color_mode:custom'); [void]$columnSet.AppendChild($columnCustomColor)
    [void]$configXml.config.AppendChild($editorSet); [void]$configXml.config.AppendChild($quoteSet); [void]$configXml.config.AppendChild($tabSet); [void]$configXml.config.AppendChild($accordionSet); [void]$configXml.config.AppendChild($columnSet); $configXml.Save("$target/admin/config.xml")

    [xml]$configXml = Get-Content -LiteralPath "$target/admin/config.xml" -Raw
    $commentsSet = $configXml.CreateElement('fieldset'); $commentsSet.SetAttribute('name', 'comments'); $commentsSet.SetAttribute('label', 'COM_' + $upper + '_COMMENTS_SETTINGS')
    $provider = $configXml.CreateElement('field'); $provider.SetAttribute('name', 'comments_provider'); $provider.SetAttribute('type', 'list'); $provider.SetAttribute('default', 'disabled'); $provider.SetAttribute('label', 'COM_' + $upper + '_COMMENTS_PROVIDER')
    foreach ($optionData in @(@('disabled','JDISABLED'),@('native','Native Comments'),@('disqus','Disqus'),@('intensedebate','IntenseDebate'),@('compojoom','CompoJoom Comments'),@('jlex','JLex Comments'),@('hypercomments','HyperComments'),@('komento','Komento'),@('jcomments','JComments'))) { $option=$configXml.CreateElement('option');$option.SetAttribute('value',$optionData[0]);$option.InnerText=$optionData[1];[void]$provider.AppendChild($option) }
    [void]$commentsSet.AppendChild($provider)
    foreach ($fieldData in @(@('disqus_shortname',"COM_${upper}_DISQUS_SHORTNAME",'comments_provider:disqus'),@('intensedebate_account',"COM_${upper}_INTENSEDEBATE_ACCOUNT",'comments_provider:intensedebate'),@('hypercomments_widget_id',"COM_${upper}_HYPERCOMMENTS_WIDGET_ID",'comments_provider:hypercomments'))) { $field=$configXml.CreateElement('field');$field.SetAttribute('name',$fieldData[0]);$field.SetAttribute('type','text');$field.SetAttribute('label',$fieldData[1]);$field.SetAttribute('showon',$fieldData[2]);[void]$commentsSet.AppendChild($field) }
    $moderation=$configXml.CreateElement('field');$moderation.SetAttribute('name','comments_moderation');$moderation.SetAttribute('type','radio');$moderation.SetAttribute('layout','joomla.form.field.radio.switcher');$moderation.SetAttribute('default','1');$moderation.SetAttribute('label','Comment moderation');$moderation.SetAttribute('showon','comments_provider:native');foreach($value in 0,1){$o=$configXml.CreateElement('option');$o.SetAttribute('value',[string]$value);$o.InnerText=if($value){'JYES'}else{'JNO'};[void]$moderation.AppendChild($o)};[void]$commentsSet.AppendChild($moderation)
    [void]$configXml.config.AppendChild($commentsSet); $configXml.Save("$target/admin/config.xml")
    [xml]$configXml=Get-Content -LiteralPath "$target/admin/config.xml" -Raw;$safety=$configXml.CreateElement('fieldset');$safety.SetAttribute('name','comment_safety');$safety.SetAttribute('label','Native Comment Safety');foreach($data in @(@('comments_notify','radio','Notify administrators of new comments','1'),@('comments_cooldown','number','Seconds between comments from one visitor','60'))){$field=$configXml.CreateElement('field');$field.SetAttribute('name',$data[0]);$field.SetAttribute('type',$data[1]);$field.SetAttribute('label',$data[2]);$field.SetAttribute('default',$data[3]);if($data[1]-eq 'radio'){$field.SetAttribute('layout','joomla.form.field.radio.switcher');foreach($value in 0,1){$o=$configXml.CreateElement('option');$o.SetAttribute('value',[string]$value);$o.InnerText=if($value){'JYES'}else{'JNO'};[void]$field.AppendChild($o)}}else{$field.SetAttribute('min','0');$field.SetAttribute('max','3600')}[void]$safety.AppendChild($field)};[void]$configXml.config.AppendChild($safety);$configXml.Save("$target/admin/config.xml")

    [xml]$configXml = Get-Content -LiteralPath "$target/admin/config.xml" -Raw
    $pollSet=$configXml.CreateElement('fieldset');$pollSet.SetAttribute('name','polls');$pollSet.SetAttribute('label',"COM_${upper}_POLLS_SETTINGS")
    $showVotes=$configXml.CreateElement('field');$showVotes.SetAttribute('name','poll_show_votes');$showVotes.SetAttribute('type','radio');$showVotes.SetAttribute('layout','joomla.form.field.radio.switcher');$showVotes.SetAttribute('default','1');$showVotes.SetAttribute('label',"COM_${upper}_POLL_SHOW_VOTES");foreach($value in 0,1){$o=$configXml.CreateElement('option');$o.SetAttribute('value',[string]$value);$o.InnerText=if($value){'JSHOW'}else{'JHIDE'};[void]$showVotes.AppendChild($o)};[void]$pollSet.AppendChild($showVotes)
    $pollStyle=$configXml.CreateElement('field');$pollStyle.SetAttribute('name','poll_results_style');$pollStyle.SetAttribute('type','list');$pollStyle.SetAttribute('default','progress');$pollStyle.SetAttribute('label',"COM_${upper}_POLL_RESULTS_STYLE");foreach($data in @(@('progress',"COM_${upper}_POLL_STYLE_PROGRESS"),@('simple',"COM_${upper}_POLL_STYLE_SIMPLE"),@('badges',"COM_${upper}_POLL_STYLE_BADGES"))){$o=$configXml.CreateElement('option');$o.SetAttribute('value',$data[0]);$o.InnerText=$data[1];[void]$pollStyle.AppendChild($o)};[void]$pollSet.AppendChild($pollStyle)
    foreach($toggleData in @(@('poll_progress_labels',"COM_${upper}_POLL_PROGRESS_LABELS"),@('poll_progress_striped',"COM_${upper}_POLL_PROGRESS_STRIPED"))){$field=$configXml.CreateElement('field');$field.SetAttribute('name',$toggleData[0]);$field.SetAttribute('type','radio');$field.SetAttribute('layout','joomla.form.field.radio.switcher');$field.SetAttribute('default','1');$field.SetAttribute('label',$toggleData[1]);$field.SetAttribute('showon','poll_results_style:progress');foreach($value in 0,1){$o=$configXml.CreateElement('option');$o.SetAttribute('value',[string]$value);$o.InnerText=if($value){'JYES'}else{'JNO'};[void]$field.AppendChild($o)};[void]$pollSet.AppendChild($field)}
    $colorMode=$configXml.CreateElement('field');$colorMode.SetAttribute('name','poll_progress_color_mode');$colorMode.SetAttribute('type','list');$colorMode.SetAttribute('default','palette');$colorMode.SetAttribute('label',"COM_${upper}_POLL_PROGRESS_COLOR_MODE");$colorMode.SetAttribute('showon','poll_results_style:progress');foreach($data in @(@('palette',"COM_${upper}_POLL_COLOR_PALETTE"),@('primary',"COM_${upper}_POLL_COLOR_PRIMARY"),@('custom',"COM_${upper}_POLL_COLOR_CUSTOM"))){$o=$configXml.CreateElement('option');$o.SetAttribute('value',$data[0]);$o.InnerText=$data[1];[void]$colorMode.AppendChild($o)};[void]$pollSet.AppendChild($colorMode)
    $customColor=$configXml.CreateElement('field');$customColor.SetAttribute('name','poll_progress_custom_color');$customColor.SetAttribute('type','color');$customColor.SetAttribute('default','#0d6efd');$customColor.SetAttribute('label',"COM_${upper}_POLL_PROGRESS_CUSTOM_COLOR");$customColor.SetAttribute('showon','poll_results_style:progress[AND]poll_progress_color_mode:custom');[void]$pollSet.AppendChild($customColor)
    [void]$configXml.config.AppendChild($pollSet);$configXml.Save("$target/admin/config.xml")
    [xml]$configXml=Get-Content -LiteralPath "$target/admin/config.xml" -Raw;$pollAccess=$configXml.CreateElement('fieldset');$pollAccess.SetAttribute('name','poll_access');$pollAccess.SetAttribute('label','Poll Access');$field=$configXml.CreateElement('field');$field.SetAttribute('name','poll_guest_voting');$field.SetAttribute('type','radio');$field.SetAttribute('layout','joomla.form.field.radio.switcher');$field.SetAttribute('default','1');$field.SetAttribute('label','Allow guest voting');foreach($value in 0,1){$o=$configXml.CreateElement('option');$o.SetAttribute('value',[string]$value);$o.InnerText=if($value){'JYES'}else{'JNO'};[void]$field.AppendChild($o)};[void]$pollAccess.AppendChild($field);[void]$configXml.config.AppendChild($pollAccess);$configXml.Save("$target/admin/config.xml")
    [xml]$configXml=Get-Content -LiteralPath "$target/admin/config.xml" -Raw;$engagement=$configXml.CreateElement('fieldset');$engagement.SetAttribute('name','engagement');$engagement.SetAttribute('label',"COM_${upper}_ENGAGEMENT_SETTINGS");foreach($data in @(@('engagement_ratings',"COM_${upper}_ENABLE_RATINGS"),@('engagement_sharing',"COM_${upper}_ENABLE_SHARING"),@('engagement_subscribe',"COM_${upper}_ENABLE_SUBSCRIBE"))){$field=$configXml.CreateElement('field');$field.SetAttribute('name',$data[0]);$field.SetAttribute('type','radio');$field.SetAttribute('layout','joomla.form.field.radio.switcher');$field.SetAttribute('default','1');$field.SetAttribute('label',$data[1]);foreach($value in 0,1){$o=$configXml.CreateElement('option');$o.SetAttribute('value',[string]$value);$o.InnerText=if($value){'JYES'}else{'JNO'};[void]$field.AppendChild($o)};[void]$engagement.AppendChild($field)};[void]$configXml.config.AppendChild($engagement);$configXml.Save("$target/admin/config.xml")
    [xml]$configXml=Get-Content -LiteralPath "$target/admin/config.xml" -Raw;$appearance=$configXml.CreateElement('fieldset');$appearance.SetAttribute('name','engagement_appearance');$appearance.SetAttribute('label',"COM_${upper}_ENGAGEMENT_APPEARANCE");foreach($data in @(@('rating_label','text',"COM_${upper}_RATING_LABEL",'Rate this post:'),@('subscribe_heading','text',"COM_${upper}_SUBSCRIBE_HEADING",'Stay Informed'),@('subscribe_text','textarea',"COM_${upper}_SUBSCRIBE_TEXT",'Subscribe for updates and new posts.'),@('subscribe_button','text',"COM_${upper}_SUBSCRIBE_BUTTON",'Subscribe'),@('subscribe_consent','textarea',"COM_${upper}_SUBSCRIBE_CONSENT",'I agree to receive email updates and can unsubscribe at any time.'))){$field=$configXml.CreateElement('field');$field.SetAttribute('name',$data[0]);$field.SetAttribute('type',$data[1]);$field.SetAttribute('label',$data[2]);$field.SetAttribute('default',$data[3]);[void]$appearance.AppendChild($field)};foreach($network in @('facebook','x','linkedin','pinterest','email')){$field=$configXml.CreateElement('field');$field.SetAttribute('name',('share_' + $network));$field.SetAttribute('type','radio');$field.SetAttribute('layout','joomla.form.field.radio.switcher');$field.SetAttribute('default','1');$field.SetAttribute('label',"Share via $network");foreach($value in 0,1){$o=$configXml.CreateElement('option');$o.SetAttribute('value',[string]$value);$o.InnerText=if($value){'JYES'}else{'JNO'};[void]$field.AppendChild($o)};[void]$appearance.AppendChild($field)};[void]$configXml.config.AppendChild($appearance);$configXml.Save("$target/admin/config.xml")
    [xml]$configXml=Get-Content -LiteralPath "$target/admin/config.xml" -Raw;$tracking=$configXml.CreateElement('fieldset');$tracking.SetAttribute('name','email_tracking');$tracking.SetAttribute('label',"COM_${upper}_EMAIL_TRACKING_SETTINGS");foreach($data in @(@('email_open_tracking',"COM_${upper}_EMAIL_OPEN_TRACKING"),@('email_link_tracking',"COM_${upper}_EMAIL_LINK_TRACKING"))){$field=$configXml.CreateElement('field');$field.SetAttribute('name',$data[0]);$field.SetAttribute('type','radio');$field.SetAttribute('layout','joomla.form.field.radio.switcher');$field.SetAttribute('default','0');$field.SetAttribute('label',$data[1]);foreach($value in 0,1){$o=$configXml.CreateElement('option');$o.SetAttribute('value',[string]$value);$o.InnerText=if($value){'JYES'}else{'JNO'};[void]$field.AppendChild($o)};[void]$tracking.AppendChild($field)};[void]$configXml.config.AppendChild($tracking);$configXml.Save("$target/admin/config.xml")
    [xml]$configXml=Get-Content -LiteralPath "$target/admin/config.xml" -Raw
    $delivery=$configXml.CreateElement('fieldset');$delivery.SetAttribute('name','email_delivery');$delivery.SetAttribute('label',"COM_${upper}_EMAIL_DELIVERY_SETTINGS")
    $service=$configXml.CreateElement('field');$service.SetAttribute('name','campaign_mail_service');$service.SetAttribute('type','list');$service.SetAttribute('default','joomla');$service.SetAttribute('label',"COM_${upper}_EMAIL_SERVICE")
    foreach($data in @(@('joomla','Joomla Default'),@('mail','PHP Mail'),@('smtp','SMTP'),@('mailgun','Mailgun'),@('sendgrid','SendGrid'),@('postmark','Postmark'))){$o=$configXml.CreateElement('option');$o.SetAttribute('value',$data[0]);$o.InnerText=$data[1];[void]$service.AppendChild($o)};[void]$delivery.AppendChild($service)
    foreach($data in @(
        @('campaign_from_email','email',"COM_${upper}_CAMPAIGN_FROM_EMAIL",''),@('campaign_from_name','text',"COM_${upper}_CAMPAIGN_FROM_NAME",''),@('campaign_reply_to','email',"COM_${upper}_CAMPAIGN_REPLY_TO",''),
        @('campaign_smtp_host','text','SMTP host','campaign_mail_service:smtp'),@('campaign_smtp_port','number','SMTP port','campaign_mail_service:smtp'),@('campaign_smtp_security','list','SMTP security','campaign_mail_service:smtp'),@('campaign_smtp_user','text','SMTP username','campaign_mail_service:smtp'),@('campaign_smtp_password','password','SMTP password','campaign_mail_service:smtp'),
        @('campaign_mailgun_key','password','Mailgun API key','campaign_mail_service:mailgun'),@('campaign_mailgun_domain','text','Mailgun sending domain','campaign_mail_service:mailgun'),@('campaign_mailgun_region','list','Mailgun region','campaign_mail_service:mailgun'),
        @('campaign_sendgrid_key','password','SendGrid API key','campaign_mail_service:sendgrid'),
        @('campaign_postmark_key','password','Postmark server token','campaign_mail_service:postmark'),@('campaign_postmark_stream','text','Postmark message stream','campaign_mail_service:postmark')
    )){$f=$configXml.CreateElement('field');$f.SetAttribute('name',$data[0]);$f.SetAttribute('type',$data[1]);$f.SetAttribute('label',$data[2]);if($data[3]){$f.SetAttribute('showon',$data[3])};if($data[0]-eq 'campaign_smtp_port'){$f.SetAttribute('default','587');$f.SetAttribute('min','1');$f.SetAttribute('max','65535')};if($data[0]-eq 'campaign_postmark_stream'){$f.SetAttribute('default','outbound')};if($data[0]-eq 'campaign_smtp_security'){foreach($v in @(@('tls','TLS'),@('ssl','SSL'),@('','None'))){$o=$configXml.CreateElement('option');$o.SetAttribute('value',$v[0]);$o.InnerText=$v[1];[void]$f.AppendChild($o)}};if($data[0]-eq 'campaign_mailgun_region'){foreach($v in @(@('us','United States'),@('eu','Europe'))){$o=$configXml.CreateElement('option');$o.SetAttribute('value',$v[0]);$o.InnerText=$v[1];[void]$f.AppendChild($o)}};[void]$delivery.AppendChild($f)}
    [void]$configXml.config.AppendChild($delivery);$configXml.Save("$target/admin/config.xml")
    [xml]$configXml=Get-Content -LiteralPath "$target/admin/config.xml" -Raw;$listDisplay=$configXml.CreateElement('fieldset');$listDisplay.SetAttribute('name','list_display');$listDisplay.SetAttribute('label',"COM_${upper}_LIST_DISPLAY_SETTINGS");$listingLayout=$configXml.CreateElement('field');$listingLayout.SetAttribute('name','post_listing_layout');$listingLayout.SetAttribute('type','list');$listingLayout.SetAttribute('default','rows');$listingLayout.SetAttribute('label',"COM_${upper}_POST_LISTING_LAYOUT_LABEL");foreach($data in @(@('rows',"COM_${upper}_POST_LISTING_LAYOUT_ROWS"),@('columns',"COM_${upper}_POST_LISTING_LAYOUT_COLUMNS"))){$o=$configXml.CreateElement('option');$o.SetAttribute('value',$data[0]);$o.InnerText=$data[1];[void]$listingLayout.AppendChild($o)};[void]$listDisplay.AppendChild($listingLayout);$excerptField=$configXml.CreateElement('field');$excerptField.SetAttribute('name','list_excerpt_length');$excerptField.SetAttribute('type','number');$excerptField.SetAttribute('default','400');$excerptField.SetAttribute('filter','integer');$excerptField.SetAttribute('min','0');$excerptField.SetAttribute('label',"COM_${upper}_LIST_EXCERPT_LENGTH_LABEL");$excerptField.SetAttribute('description',"COM_${upper}_LIST_EXCERPT_LENGTH_DESC");[void]$listDisplay.AppendChild($excerptField);$styleField=$configXml.CreateElement('field');$styleField.SetAttribute('name','list_item_style');$styleField.SetAttribute('type','list');$styleField.SetAttribute('default','standard');$styleField.SetAttribute('label',"COM_${upper}_LIST_ITEM_STYLE_LABEL");$styleField.SetAttribute('description',"COM_${upper}_LIST_ITEM_STYLE_DESC");foreach($data in @(@('standard',"COM_${upper}_LIST_ITEM_STYLE_STANDARD"),@('compact',"COM_${upper}_LIST_ITEM_STYLE_COMPACT"))){$o=$configXml.CreateElement('option');$o.SetAttribute('value',$data[0]);$o.InnerText=$data[1];[void]$styleField.AppendChild($o)};[void]$listDisplay.AppendChild($styleField);[void]$configXml.config.AppendChild($listDisplay);$configXml.Save("$target/admin/config.xml")
    [xml]$configXml = Get-Content -LiteralPath "$target/admin/config.xml" -Raw
    $listDisplay = $configXml.config.fieldset | Where-Object { $_.name -eq 'list_display' } | Select-Object -First 1
    foreach ($definition in @(
        @('column_style', "COM_${upper}_COLUMN_STYLE_LABEL", 'grid', @(@('grid', "COM_${upper}_COLUMN_STYLE_GRID"), @('masonry', "COM_${upper}_COLUMN_STYLE_MASONRY"))),
        @('columns_per_row', "COM_${upper}_COLUMNS_PER_ROW_LABEL", '2', @(@('2', '2 Columns'), @('3', '3 Columns'), @('4', '4 Columns'), @('5', '5 Columns'), @('6', '6 Columns')))
    )) {
        $field = $configXml.CreateElement('field'); $field.SetAttribute('name', $definition[0]); $field.SetAttribute('type', 'list'); $field.SetAttribute('label', $definition[1]); $field.SetAttribute('default', $definition[2]); $field.SetAttribute('showon', 'post_listing_layout:columns')
        foreach ($data in $definition[3]) { $option = $configXml.CreateElement('option'); $option.SetAttribute('value', $data[0]); $option.InnerText = $data[1]; [void]$field.AppendChild($option) }
        [void]$listDisplay.InsertBefore($field, $listDisplay.SelectSingleNode("field[@name='list_excerpt_length']"))
    }
    $styleField = $listDisplay.SelectSingleNode("field[@name='list_item_style']")
    $styleField.RemoveAll(); $styleField.SetAttribute('name', 'list_item_style'); $styleField.SetAttribute('type', 'list'); $styleField.SetAttribute('default', 'standard'); $styleField.SetAttribute('label', "COM_${upper}_LIST_ITEM_STYLE_LABEL")
    foreach ($data in @(@('standard', "COM_${upper}_LIST_ITEM_STYLE_STANDARD"), @('card', "COM_${upper}_LIST_ITEM_STYLE_CARD"), @('learning', "COM_${upper}_LIST_ITEM_STYLE_LEARNING"), @('simple', "COM_${upper}_LIST_ITEM_STYLE_SIMPLE"), @('nickel', "COM_${upper}_LIST_ITEM_STYLE_NICKEL"))) { $option=$configXml.CreateElement('option'); $option.SetAttribute('value',$data[0]); $option.InnerText=$data[1]; [void]$styleField.AppendChild($option) }
    $configXml.Save("$target/admin/config.xml")

    # Move every functional option to the component's own Settings form. Joomla
    # Options deliberately retains only its native Permissions fieldset.
    [xml]$configXml = Get-Content -LiteralPath "$target/admin/config.xml" -Raw
    $integration = $configXml.config.fieldset | Where-Object { $_.name -eq 'integration' } | Select-Object -First 1
    if ($integration) {
        foreach ($nested in @($integration.fieldset)) {
            if ($nested.name -ne 'integration_workflows') {
                [void]$configXml.config.InsertBefore($nested.CloneNode($true), $integration)
            }
        }
        [void]$configXml.config.RemoveChild($integration)
    }
    $settingsXml = [xml]'<?xml version="1.0" encoding="UTF-8"?><form><fields name="params"></fields></form>'
    foreach ($fieldset in @($configXml.config.fieldset)) {
        if ($fieldset.name -eq 'permissions') { continue }
        [void]$settingsXml.form.fields.AppendChild($settingsXml.ImportNode($fieldset, $true))
        [void]$configXml.config.RemoveChild($fieldset)
    }
    $settingsXml.Save("$target/admin/forms/settings.xml")
    $configXml.Save("$target/admin/config.xml")

    $editTemplatePath = "$target/admin/tmpl/article/edit.php"
    $editTemplate = [IO.File]::ReadAllText($editTemplatePath)
    $editTemplate = $editTemplate.Replace("`$wa->useScript('keepalive')", "`$wa->registerAndUseScript('com_${name}.block-editor', 'com_${name}/admin-block-editor.js', ['version' => '1.4.1'], ['type' => 'module'])`r`n    ->registerAndUseStyle('com_${name}.block-editor', 'com_${name}/admin-block-editor.css', ['version' => '1.4.1']);`r`n`$wa->useScript('keepalive')")
    $classicMarkup = "                        <?php echo `$this->form->getLabel('articletext'); ?>`r`n                        <?php echo `$this->form->getInput('articletext'); ?>"
    $hybridMarkup = @"
                        <?php `$pollDb = Factory::getContainer()->get(DatabaseInterface::class); `$pollItems = `$pollDb->setQuery(`$pollDb->createQuery()->select(['id','title'])->from('#__${name}_polls')->where('state=1')->order('title'))->loadObjectList(); ?>
                        <div data-article-block-editor data-editor-id="jform_articletext" data-default-mode="<?php echo htmlspecialchars(ComponentHelper::getParams('com_$name')->get('default_editor_mode', 'classic'), ENT_QUOTES, 'UTF-8'); ?>" data-polls="<?php echo htmlspecialchars(json_encode(`$pollItems), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo `$this->form->getInput('block_data'); ?>
                            <div data-classic-editor><?php echo `$this->form->getLabel('articletext'); ?><?php echo `$this->form->getInput('articletext'); ?></div>
                            <div data-block-composer hidden>
                                <div class="article-composer-shell">
                                    <main class="article-composer-main"><div class="article-block-canvas" data-block-canvas></div></main>
                                    <aside class="article-block-palette">
                                        <h3>Insert Block</h3>
                                        <label class="visually-hidden" for="article-block-search">Search blocks</label><input id="article-block-search" class="form-control mb-3" type="search" placeholder="Search blocks">
                                        <h4>Layout</h4><div class="article-block-palette-grid">
                                        <?php foreach (['heading','text','tabs','columns','table','section','accordion'] as `$blockType) : ?><button type="button" class="btn btn-outline-secondary" data-add-block="<?php echo `$blockType; ?>"><?php echo ucfirst(`$blockType); ?></button><?php endforeach; ?>
                                        </div><h4>Elements</h4><div class="article-block-palette-grid">
                                        <?php foreach (['alert','quote','button','link','code'] as `$blockType) : ?><button type="button" class="btn btn-outline-secondary" data-add-block="<?php echo `$blockType; ?>"><?php echo ucfirst(`$blockType); ?></button><?php endforeach; ?>
                                        </div><h4>Media</h4><div class="article-block-palette-grid">
                                        <?php foreach (['image','video','audio','comparison'] as `$blockType) : ?><button type="button" class="btn btn-outline-secondary" data-add-block="<?php echo `$blockType; ?>"><?php echo ucfirst(`$blockType); ?></button><?php endforeach; ?>
                                        </div><h4>Joomla</h4><div class="article-block-palette-grid">
                                        <?php foreach (['html','rule','readmore','pagebreak','module','polls'] as `$blockType) : ?><button type="button" class="btn btn-outline-secondary" data-add-block="<?php echo `$blockType; ?>"><?php echo `$blockType === 'polls' ? 'Poll' : ucfirst(`$blockType); ?></button><?php endforeach; ?>
                                        </div><h4>Embeddables</h4><div class="article-block-palette-grid">
                                        <?php foreach (['gist','instagram','spotify','behance','soundcloud','slideshare','codepen','tweet','pinterest','youtube','vimeo','dailymotion','ted','facebook'] as `$blockType) : ?><button type="button" class="btn btn-outline-secondary" data-add-block="<?php echo `$blockType; ?>"><?php echo ucfirst(`$blockType); ?></button><?php endforeach; ?>
                                        </div>
                                    </aside>
                                </div>
                            </div>
                        </div>
"@
    $editTemplate = [regex]::Replace($editTemplate, "\s*<\?php echo \`$this->form->getLabel\('articletext'\); \?>\s*<\?php echo \`$this->form->getInput\('articletext'\); \?>", "`r`n" + $hybridMarkup.TrimEnd(), 1)
    $editTemplate = $editTemplate.Replace('<div class="col-lg-9">', '<div class="col-lg-9" data-article-editor-column>')
    $editTemplate = $editTemplate.Replace('<div class="col-lg-3">', '<div class="col-lg-3" data-article-global-column>')
    $editTemplate = $editTemplate.Replace('use Joomla\CMS\Language\Text;', "use Joomla\CMS\Language\Text;`r`nuse Joomla\CMS\Component\ComponentHelper;`r`nuse Joomla\Database\DatabaseInterface;")
    [IO.File]::WriteAllText($editTemplatePath, $editTemplate, [Text.UTF8Encoding]::new($false))

    $displayControllerPath = "$target/admin/src/Controller/DisplayController.php"
    $displayController = [IO.File]::ReadAllText($displayControllerPath).Replace("protected `$default_view = 'articles';", "protected `$default_view = 'dashboard';").Replace("`$this->input->get('view', 'articles')", "`$this->input->get('view', 'dashboard')")
    [IO.File]::WriteAllText($displayControllerPath, $displayController, [Text.UTF8Encoding]::new($false))

    Get-ChildItem -LiteralPath "$target/site" -Recurse -Filter '*.php' | ForEach-Object {
        $layoutCode = [IO.File]::ReadAllText($_.FullName)
        $layoutCode = $layoutCode.Replace("LayoutHelper::render('joomla.content.blog_style_default_item_title', `$this->item)", "LayoutHelper::render('joomla.content.blog_style_default_item_title', `$this->item, JPATH_COMPONENT . '/layouts')")
        $layoutCode = $layoutCode.Replace("LayoutHelper::render('joomla.content.intro_image', `$this->item)", "LayoutHelper::render('joomla.content.featured_image', `$this->item, JPATH_COMPONENT . '/layouts')")
        [IO.File]::WriteAllText($_.FullName, $layoutCode, [Text.UTF8Encoding]::new($false))
    }
    $postViewPath = "$target/site/tmpl/article/default.php"
    $postView = [IO.File]::ReadAllText($postViewPath).Replace("    <?php echo `$this->item->event->afterDisplayContent; ?>", "    <?php echo `$this->item->event->afterDisplayContent; ?>`r`n    <?php echo LayoutHelper::render('engagement', `$this->item, JPATH_COMPONENT . '/layouts'); ?>`r`n    <?php echo LayoutHelper::render('comments', `$this->item, JPATH_COMPONENT . '/layouts'); ?>")
    [IO.File]::WriteAllText($postViewPath, $postView, [Text.UTF8Encoding]::new($false))

    $featuredPath = "$target/site/tmpl/featured/default.php"
    $featuredTemplate = [IO.File]::ReadAllText($featuredPath)
    $featuredTemplate = $featuredTemplate.Replace("defined('_JEXEC') or die;", "defined('_JEXEC') or die;" + "`r`n`r`nuse Joomla\CMS\Layout\LayoutHelper;")
    $featuredTemplate = $featuredTemplate.Replace('<div class="blog-featured">', '<div class="blog-featured">' + "`r`n    <?php echo LayoutHelper::render('postnav', (object) [], JPATH_COMPONENT . '/layouts'); ?>")
    [IO.File]::WriteAllText($featuredPath, $featuredTemplate, [Text.UTF8Encoding]::new($false))

    $categoryBlogPath = "$target/site/tmpl/category/blog.php"
    $categoryBlogTemplate = [IO.File]::ReadAllText($categoryBlogPath)
    $categoryBlogTemplate = $categoryBlogTemplate.Replace('<div class="com-content-category-blog blog">', '<div class="com-content-category-blog blog">' + "`r`n    <?php echo LayoutHelper::render('postnav', (object) [], JPATH_COMPONENT . '/layouts'); ?>")
    [IO.File]::WriteAllText($categoryBlogPath, $categoryBlogTemplate, [Text.UTF8Encoding]::new($false))

    $avatarCallItem = '<?php if (!in_array($listStyle, [''card'', ''learning''], true)) { echo LayoutHelper::render(''postlist.'' . $listStyle . ''.avatar'', $this->item, JPATH_COMPONENT . ''/layouts''); } ?>'
    $avatarCallDisplayData = '<?php if (!in_array($listStyle, [''card'', ''learning''], true)) { echo LayoutHelper::render(''postlist.'' . $listStyle . ''.avatar'', $displayData, JPATH_COMPONENT . ''/layouts''); } ?>'
    $postmetaCall = "`n`n    <?php echo LayoutHelper::render('postlist.' . `$listStyle . '.meta', `$this->item, JPATH_COMPONENT . '/layouts'); ?>`n    <?php if (`$listStyle === 'card') { echo LayoutHelper::render('postlist.card.footer', `$this->item, JPATH_COMPONENT . '/layouts'); } ?>"
    $learningOpen = "<?php if (`$listStyle === 'learning') : ?>`r`n<div class=`"item-content learning-item-content`">`r`n    <?php echo LayoutHelper::render('joomla.content.blog_style_default_item_title', `$this->item, JPATH_COMPONENT . '/layouts'); ?>`r`n    <?php echo LayoutHelper::render('postlist.learning.details', `$this->item, JPATH_COMPONENT . '/layouts'); ?>`r`n</div>`r`n<?php else : ?>`r`n`r`n<div class=`"item-content`">"
    $readmoreLine = "        <?php echo LayoutHelper::render('joomla.content.readmore', ['item' => `$this->item, 'params' => `$params, 'link' => `$link]); ?>`n`n    <?php endif; ?>"
    $featuredImageLine = '<?php echo LayoutHelper::render(''joomla.content.featured_image'', $this->item, JPATH_COMPONENT . ''/layouts''); ?>'
    $cardAwareImage = @'
<?php
$featuredImages = json_decode((string) ($this->item->images ?? '{}'));
if (in_array($listStyle, ['card', 'learning'], true) && empty($featuredImages->featured_image)) {
    echo LayoutHelper::render('postlist.card.placeholder', $this->item, JPATH_COMPONENT . '/layouts');
} else {
    echo LayoutHelper::render('joomla.content.featured_image', $this->item, JPATH_COMPONENT . '/layouts');
}
?>
'@.TrimEnd()

    $featuredItemPath = "$target/site/tmpl/featured/default_item.php"
    $featuredItemTemplate = [IO.File]::ReadAllText($featuredItemPath)
    $featuredItemTemplate = $featuredItemTemplate.Replace('use Joomla\CMS\Factory;', "use Joomla\Component\$title\Site\Helper\ListExcerptHelper;`r`nuse Joomla\CMS\Factory;")
    $featuredItemTemplate = $featuredItemTemplate.Replace('$params  = &$this->item->params;', "`$params  = &`$this->item->params;`r`n`$listStyle = `$params->get('list_item_style', 'standard');")
    $featuredItemTemplate = $featuredItemTemplate.Replace('<?php echo $this->item->introtext; ?>', '<?php echo ListExcerptHelper::render($this->item, $params); ?>')
    $featuredItemTemplate = $featuredItemTemplate.Replace($featuredImageLine, $cardAwareImage)
    $featuredItemTemplate = $featuredItemTemplate.Replace('<h2 class="item-title">', '<h2 class="item-title d-flex align-items-center gap-2">' + $avatarCallItem + '<span>')
    $featuredItemTemplate = $featuredItemTemplate.Replace("</h2>", "</span></h2>")
    $featuredItemTemplate = $featuredItemTemplate.Replace($readmoreLine, $readmoreLine + $postmetaCall)
    $featuredItemTemplate = $featuredItemTemplate.Replace('<div class="item-content">', $learningOpen)
    $featuredItemTemplate = $featuredItemTemplate.TrimEnd() + "`r`n<?php endif; ?>`r`n"
    [IO.File]::WriteAllText($featuredItemPath, $featuredItemTemplate, [Text.UTF8Encoding]::new($false))

    $blogItemPath = "$target/site/tmpl/category/blog_item.php"
    $blogItemTemplate = [IO.File]::ReadAllText($blogItemPath)
    $blogItemTemplate = $blogItemTemplate.Replace('use Joomla\CMS\Factory;', "use Joomla\Component\$title\Site\Helper\ListExcerptHelper;`r`nuse Joomla\CMS\Factory;")
    $blogItemTemplate = $blogItemTemplate.Replace('$params = $this->item->params;', "`$params = `$this->item->params;`r`n`$listStyle = `$params->get('list_item_style', 'standard');")
    $blogItemTemplate = $blogItemTemplate.Replace('<?php echo $this->item->introtext; ?>', '<?php echo ListExcerptHelper::render($this->item, $params); ?>')
    $blogItemTemplate = $blogItemTemplate.Replace($featuredImageLine, $cardAwareImage)
    $blogItemTemplate = $blogItemTemplate.Replace($readmoreLine, $readmoreLine + $postmetaCall)
    $blogItemTemplate = $blogItemTemplate.Replace('<div class="item-content">', $learningOpen)
    $blogItemTemplate = [regex]::Replace($blogItemTemplate, '(\<\?php echo \$this-\>item-\>event-\>afterDisplayContent; \?\>\s*\</div\>)\s*$', "`$1`r`n<?php endif; ?>`r`n", 1)
    [IO.File]::WriteAllText($blogItemPath, $blogItemTemplate, [Text.UTF8Encoding]::new($false))

    $blogTitlePath = "$target/site/layouts/joomla/content/blog_style_default_item_title.php"
    $blogTitleTemplate = [IO.File]::ReadAllText($blogTitlePath)
    $blogTitleTemplate = $blogTitleTemplate.Replace("defined('_JEXEC') or die;", "defined('_JEXEC') or die;" + "`r`n`r`nuse Joomla\CMS\Layout\LayoutHelper;")
    $blogTitleTemplate = $blogTitleTemplate.Replace('$params  = $displayData->params;', "`$params  = `$displayData->params;`r`n`$listStyle = `$params->get('list_item_style', 'standard');")
    $blogTitleTemplate = $blogTitleTemplate.Replace('<h2>', '<h2 class="d-flex align-items-center gap-2">' + $avatarCallDisplayData + '<span>')
    $blogTitleTemplate = $blogTitleTemplate.Replace('</h2>', '</span></h2>')
    [IO.File]::WriteAllText($blogTitlePath, $blogTitleTemplate, [Text.UTF8Encoding]::new($false))

    $articleTablePath = "$target/admin/src/Table/ArticleTable.php"
    $articleTable = [IO.File]::ReadAllText($articleTablePath)
    $articleTableBody = @"
class ArticleTable extends \Joomla\CMS\Table\Content
{
    public function __construct(\Joomla\Database\DatabaseInterface `$db, ?\Joomla\Event\DispatcherInterface `$dispatcher = null)
    {
        parent::__construct(`$db, `$dispatcher);
        `$this->_tbl = '#__$name';
        `$this->typeAlias = 'com_$name.article';
    }

    protected function _getAssetName()
    {
        return 'com_$name.article.' . (int) `$this->{`$this->_tbl_key};
    }
}
"@
    $articleTable = [regex]::Replace($articleTable, 'class ArticleTable extends \\Joomla\\CMS\\Table\\Content\s*\{\s*\}', [System.Text.RegularExpressions.MatchEvaluator]{ param($match) $articleTableBody })
    [IO.File]::WriteAllText($articleTablePath, $articleTable, [Text.UTF8Encoding]::new($false))

    $articlesModelPath = "$target/admin/src/Model/ArticlesModel.php"
    $articlesModel = [IO.File]::ReadAllText($articlesModelPath)
    $articlesModel = [regex]::Replace($articlesModel, "\s*->where\(\`$db->quoteName\('wa\.extension'\) \. ' = ' \. \`$db->quote\('com_$name\.article'\)\)", '')
    $workflowJoin = "            ->join('INNER', `$db->quoteName('#__workflow_associations', 'wa'), `$db->quoteName('wa.item_id') . ' = ' . `$db->quoteName('a.id'))"
    $optionalWorkflowJoin = "            ->join('LEFT', `$db->quoteName('#__workflow_associations', 'wa'), `$db->quoteName('wa.item_id') . ' = ' . `$db->quoteName('a.id') . ' AND ' . `$db->quoteName('wa.extension') . ' = ' . `$db->quote('com_$name.article'))"
    $articlesModel = $articlesModel.Replace($workflowJoin, $optionalWorkflowJoin)
    $articlesModel = $articlesModel.Replace("            ->join('INNER', `$db->quoteName('#__workflow_stages', 'ws')", "            ->join('LEFT', `$db->quoteName('#__workflow_stages', 'ws')")
    $articlesModel = $articlesModel.Replace("            ->join('INNER', `$db->quoteName('#__workflows', 'w')", "            ->join('LEFT', `$db->quoteName('#__workflows', 'w')")
    [IO.File]::WriteAllText($articlesModelPath, $articlesModel, [Text.UTF8Encoding]::new($false))

    $extensionPath = "$target/admin/src/Extension/${title}Component.php"
    $extensionCode = [IO.File]::ReadAllText($extensionPath)
    $bootStart = "    public function boot(ContainerInterface `$container)`r`n    {"
    if (-not $extensionCode.Contains($bootStart)) {
        $bootStart = "    public function boot(ContainerInterface `$container)`n    {"
    }
    $extensionCode = $extensionCode.Replace($bootStart, $bootStart + "`r`n        Factory::getLanguage()->load('com_content', JPATH_SITE);")
    [IO.File]::WriteAllText($extensionPath, $extensionCode, [Text.UTF8Encoding]::new($false))

    $sqlSource = Get-Content -LiteralPath "$SourceRoot/installation/sql/mysql/extensions.sql"
    $installSql = ($sqlSource[158..229] -join "`r`n").Replace('#__content_frontpage', "#__${name}_frontpage").Replace('#__content_rating', "#__${name}_rating").Replace('#__content', "#__${name}")
    $installSql = $installSql.Replace('  `fulltext` mediumtext NOT NULL,', "  ``fulltext`` mediumtext NOT NULL,`r`n  ``editor_mode`` varchar(16) NOT NULL DEFAULT '',`r`n  ``block_data`` mediumtext NULL,")
    $commentsSql = @"
CREATE TABLE IF NOT EXISTS ``#__${name}_comments`` (
 ``id`` int unsigned NOT NULL AUTO_INCREMENT, ``post_id`` int unsigned NOT NULL, ``parent_id`` int unsigned NOT NULL DEFAULT 0,
 ``user_id`` int unsigned NOT NULL DEFAULT 0, ``name`` varchar(255) NOT NULL, ``email`` varchar(255) NOT NULL,
 ``body`` text NOT NULL, ``state`` tinyint NOT NULL DEFAULT 0, ``created`` datetime NOT NULL, ``ip_hash`` char(64) NOT NULL DEFAULT '',
 PRIMARY KEY (``id``), KEY ``idx_post_state`` (``post_id``,``state``)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
"@
    $pollsSql = @"
CREATE TABLE IF NOT EXISTS ``#__${name}_polls`` (``id`` int unsigned NOT NULL AUTO_INCREMENT,``title`` varchar(500) NOT NULL,``state`` tinyint NOT NULL DEFAULT 1,``multiple`` tinyint NOT NULL DEFAULT 0,``publish_up`` datetime NULL,``publish_down`` datetime NULL,``created`` datetime NOT NULL,PRIMARY KEY (``id``),KEY ``idx_state`` (``state``)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ``#__${name}_poll_options`` (``id`` int unsigned NOT NULL AUTO_INCREMENT,``poll_id`` int unsigned NOT NULL,``title`` varchar(500) NOT NULL,``ordering`` int NOT NULL DEFAULT 0,PRIMARY KEY (``id``),KEY ``idx_poll`` (``poll_id``)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ``#__${name}_poll_votes`` (``id`` bigint unsigned NOT NULL AUTO_INCREMENT,``poll_id`` int unsigned NOT NULL,``option_id`` int unsigned NOT NULL,``voter_key`` char(64) NOT NULL,``created`` datetime NOT NULL,PRIMARY KEY (``id``),UNIQUE KEY ``idx_vote`` (``poll_id``,``option_id``,``voter_key``),KEY ``idx_poll`` (``poll_id``)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ``#__${name}_rating_votes`` (``id`` bigint unsigned NOT NULL AUTO_INCREMENT,``post_id`` int unsigned NOT NULL,``rating`` tinyint unsigned NOT NULL,``voter_key`` char(64) NOT NULL,``created`` datetime NOT NULL,PRIMARY KEY (``id``),UNIQUE KEY ``idx_post_voter`` (``post_id``,``voter_key``)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ``#__${name}_subscribers`` (``id`` int unsigned NOT NULL AUTO_INCREMENT,``name`` varchar(255) NOT NULL,``email`` varchar(320) NOT NULL,``state`` tinyint NOT NULL DEFAULT 0,``token`` varchar(64) NOT NULL,``confirm_token`` varchar(64) NOT NULL DEFAULT '',``created`` datetime NOT NULL,``consented`` datetime NOT NULL,``confirmed`` datetime NULL,``unsubscribed`` datetime NULL,``consent_ip`` varchar(45) NOT NULL DEFAULT '',PRIMARY KEY (``id``),UNIQUE KEY ``idx_email`` (``email``),UNIQUE KEY ``idx_token`` (``token``),KEY ``idx_confirm_token`` (``confirm_token``)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ``#__${name}_subscriber_suppressions`` (``id`` int unsigned NOT NULL AUTO_INCREMENT,``email`` varchar(320) NOT NULL,``reason`` varchar(64) NOT NULL DEFAULT 'removed',``created`` datetime NOT NULL,PRIMARY KEY (``id``),UNIQUE KEY ``idx_email`` (``email``)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ``#__${name}_import_batches`` (``id`` int unsigned NOT NULL AUTO_INCREMENT,``source`` varchar(255) NOT NULL,``created`` datetime NOT NULL,``created_by`` int unsigned NOT NULL DEFAULT 0,``imported_count`` int unsigned NOT NULL DEFAULT 0,PRIMARY KEY (``id``)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ``#__${name}_import_items`` (``batch_id`` int unsigned NOT NULL,``post_id`` int unsigned NOT NULL,PRIMARY KEY (``batch_id``,``post_id``),KEY ``idx_post`` (``post_id``)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ``#__${name}_email_templates`` (``id`` int unsigned NOT NULL AUTO_INCREMENT,``title`` varchar(255) NOT NULL,``subject`` varchar(255) NOT NULL,``body`` mediumtext NOT NULL,``created`` datetime NOT NULL,PRIMARY KEY (``id``)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ``#__${name}_campaigns`` (``id`` int unsigned NOT NULL AUTO_INCREMENT,``subject`` varchar(255) NOT NULL,``body`` mediumtext NOT NULL,``state`` tinyint NOT NULL DEFAULT 0,``segment`` varchar(32) NOT NULL DEFAULT 'all',``scheduled_at`` datetime NULL,``created`` datetime NOT NULL,``created_by`` int unsigned NOT NULL DEFAULT 0,PRIMARY KEY (``id``),KEY ``idx_state_schedule`` (``state``,``scheduled_at``)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ``#__${name}_mail_queue`` (``id`` bigint unsigned NOT NULL AUTO_INCREMENT,``campaign_id`` int unsigned NOT NULL,``subscriber_id`` int unsigned NOT NULL,``name`` varchar(255) NOT NULL,``email`` varchar(320) NOT NULL,``token`` varchar(64) NOT NULL,``tracking_token`` char(48) NOT NULL DEFAULT '',``state`` tinyint NOT NULL DEFAULT 0,``attempts`` int NOT NULL DEFAULT 0,``error`` text NOT NULL,``opened_count`` int unsigned NOT NULL DEFAULT 0,``clicked_count`` int unsigned NOT NULL DEFAULT 0,``last_opened`` datetime NULL,``last_clicked`` datetime NULL,PRIMARY KEY (``id``),UNIQUE KEY ``idx_campaign_subscriber`` (``campaign_id``,``subscriber_id``),KEY ``idx_tracking_token`` (``tracking_token``),KEY ``idx_state`` (``state``),KEY ``idx_campaign`` (``campaign_id``)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ``#__${name}_autopost_logs`` (``id`` bigint unsigned NOT NULL AUTO_INCREMENT,``post_id`` int unsigned NOT NULL,``provider`` varchar(24) NOT NULL,``event`` varchar(12) NOT NULL,``status`` varchar(12) NOT NULL,``remote_id`` varchar(255) NOT NULL DEFAULT '',``message`` text NOT NULL,``response`` text NOT NULL,``created`` datetime NOT NULL,PRIMARY KEY (``id``),KEY ``idx_post`` (``post_id``),KEY ``idx_provider_status`` (``provider``,``status``),KEY ``idx_created`` (``created``)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"@
    [IO.File]::WriteAllText("$target/admin/sql/install.mysql.utf8.sql", $installSql + "`r`n" + $commentsSql + "`r`n" + $pollsSql, [Text.UTF8Encoding]::new($false))
    $uninstallSql = "DROP TABLE IF EXISTS ``#__${name}_autopost_logs``;`r`nDROP TABLE IF EXISTS ``#__${name}_subscribers``;`r`nDROP TABLE IF EXISTS ``#__${name}_rating_votes``;`r`nDROP TABLE IF EXISTS ``#__${name}_poll_votes``;`r`nDROP TABLE IF EXISTS ``#__${name}_poll_options``;`r`nDROP TABLE IF EXISTS ``#__${name}_polls``;`r`nDROP TABLE IF EXISTS ``#__${name}_comments``;`r`nDROP TABLE IF EXISTS ``#__${name}_rating``;`r`nDROP TABLE IF EXISTS ``#__${name}_frontpage``;`r`nDROP TABLE IF EXISTS ``#__${name}``;`r`n"
    [IO.File]::WriteAllText("$target/admin/sql/uninstall.mysql.utf8.sql", $uninstallSql, [Text.UTF8Encoding]::new($false))

    $installerScript = @"
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Table\Menu;
use Joomla\Database\DatabaseInterface;

class Com_${title}InstallerScript
{
    public function postflight(string `$type, InstallerAdapter `$adapter): bool
    {
        // Joomla recreates manifest submenu rows after install/update handlers.
        // Apply the second-level hierarchy only once those rows exist.
        return `$this->repairAdminMenu();
    }

    public function install(InstallerAdapter `$adapter): bool
    {
        return `$this->ensureSubscriberSchema() && `$this->ensureCampaignSchema() && `$this->ensureAutopostSchema() && `$this->registerContentTypes() && `$this->repairAdminMenu() && `$this->seedComponentDefaults();
    }

    public function update(InstallerAdapter `$adapter): bool
    {
        return `$this->ensureSubscriberSchema() && `$this->ensureCampaignSchema() && `$this->ensureAutopostSchema() && `$this->registerContentTypes() && `$this->repairAdminMenu() && `$this->seedComponentDefaults();
    }

    public function uninstall(InstallerAdapter `$adapter): bool
    {
        `$db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        `$query = `$db->getQuery(true)
            ->delete(`$db->quoteName('#__content_types'))
            ->where(`$db->quoteName('type_alias') . ' IN (' . implode(',', `$db->quote(['com_$name.article', 'com_$name.category'])) . ')');
        `$db->setQuery(`$query)->execute();
        return true;
    }

    private function registerContentTypes(): bool
    {
        `$db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        foreach (['article', 'category'] as `$section) {
            `$alias = 'com_$name.' . `$section;
            `$query = `$db->getQuery(true)->select('COUNT(*)')->from(`$db->quoteName('#__content_types'))->where(`$db->quoteName('type_alias') . ' = ' . `$db->quote(`$alias));
            if ((int) `$db->setQuery(`$query)->loadResult() > 0) { continue; }

            // The source row is Joomla's built-in content type; the installed
            // family alias itself is always the native "post" alias.
            `$sourceSection = `$section === 'post' ? 'arti' . 'cle' : `$section;
            `$sourceAlias = 'com_content.' . `$sourceSection;
            `$query = `$db->getQuery(true)->select('*')->from(`$db->quoteName('#__content_types'))->where(`$db->quoteName('type_alias') . ' = ' . `$db->quote(`$sourceAlias));
            `$row = `$db->setQuery(`$query)->loadObject();
            if (!`$row) { continue; }

            unset(`$row->type_id);
            foreach (`$row as `$field => `$value) {
                if (is_string(`$value)) {
                    `$value = str_replace(['com_content', '#__content', 'Joomla\\\\Component\\\\Content'], ['com_$name', '#__$name', 'Joomla\\\\Component\\\\$title'], `$value);
                    `$row->`$field = `$value;
                }
            }
            if (`$section === 'post') {
                `$mappings = json_decode((string) `$row->field_mappings);
                if (isset(`$mappings->common)) {
                    `$mappings->common->core_body = 'summary';
                    `$mappings->common->core_params = 'options';
                    `$mappings->common->core_images = 'media';
                    unset(`$mappings->common->core_urls);
                    `$mappings->special = (object) ['body' => 'body'];
                    `$row->field_mappings = json_encode(`$mappings, JSON_UNESCAPED_SLASHES);
                }
            }
            `$row->type_title = '$title ' . `$row->type_title;
            `$db->insertObject('#__content_types', `$row);
        }
        return true;
    }

    private function ensureSubscriberSchema(): bool
    {
        `$db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        `$db->setQuery("CREATE TABLE IF NOT EXISTS #__${name}_subscriber_suppressions (id int unsigned NOT NULL AUTO_INCREMENT,email varchar(320) NOT NULL,reason varchar(64) NOT NULL DEFAULT 'removed',created datetime NOT NULL,PRIMARY KEY (id),UNIQUE KEY idx_email (email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
        `$db->setQuery("CREATE TABLE IF NOT EXISTS #__${name}_import_batches (id int unsigned NOT NULL AUTO_INCREMENT,source varchar(255) NOT NULL,created datetime NOT NULL,created_by int unsigned NOT NULL DEFAULT 0,imported_count int unsigned NOT NULL DEFAULT 0,PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
        `$db->setQuery("CREATE TABLE IF NOT EXISTS #__${name}_import_items (batch_id int unsigned NOT NULL,post_id int unsigned NOT NULL,PRIMARY KEY (batch_id,post_id),KEY idx_post (post_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
        `$columns = `$db->getTableColumns('#__${name}_subscribers');
        `$additions = [
            'confirm_token' => "ALTER TABLE #__${name}_subscribers ADD confirm_token varchar(64) NOT NULL DEFAULT '' AFTER token",
            'confirmed' => "ALTER TABLE #__${name}_subscribers ADD confirmed datetime NULL AFTER consented",
            'unsubscribed' => "ALTER TABLE #__${name}_subscribers ADD unsubscribed datetime NULL AFTER confirmed",
            'consent_ip' => "ALTER TABLE #__${name}_subscribers ADD consent_ip varchar(45) NOT NULL DEFAULT '' AFTER unsubscribed",
        ];
        foreach (`$additions as `$column => `$sql) {
            if (!isset(`$columns[`$column])) {
                `$db->setQuery(str_replace('#__', `$db->getPrefix(), `$sql))->execute();
            }
        }
        `$db->setQuery("UPDATE #__${name}_subscribers SET confirmed=consented WHERE state=1 AND confirmed IS NULL")->execute();
        return true;
    }

    private function ensureCampaignSchema(): bool
    {
        `$db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        `$columns = `$db->getTableColumns('#__${name}_campaigns');
        if (!isset(`$columns['segment'])) {
            `$db->setQuery('ALTER TABLE #__${name}_campaigns ADD segment varchar(32) NOT NULL DEFAULT ' . `$db->quote('all') . ' AFTER state')->execute();
        }
        if (!isset(`$columns['scheduled_at'])) {
            `$db->setQuery('ALTER TABLE #__${name}_campaigns ADD scheduled_at datetime NULL AFTER segment')->execute();
        }
        `$queueColumns = `$db->getTableColumns('#__${name}_mail_queue');
        `$queueAdditions = [
            'tracking_token' => "ALTER TABLE #__${name}_mail_queue ADD tracking_token char(48) NOT NULL DEFAULT '' AFTER token",
            'opened_count' => "ALTER TABLE #__${name}_mail_queue ADD opened_count int unsigned NOT NULL DEFAULT 0 AFTER error",
            'clicked_count' => "ALTER TABLE #__${name}_mail_queue ADD clicked_count int unsigned NOT NULL DEFAULT 0 AFTER opened_count",
            'last_opened' => "ALTER TABLE #__${name}_mail_queue ADD last_opened datetime NULL AFTER clicked_count",
            'last_clicked' => "ALTER TABLE #__${name}_mail_queue ADD last_clicked datetime NULL AFTER last_opened",
        ];
        foreach (`$queueAdditions as `$column => `$sql) {
            if (!isset(`$queueColumns[`$column])) `$db->setQuery(`$sql)->execute();
        }
        return true;
    }

    private function ensureAutopostSchema(): bool
    {
        `$db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        `$db->setQuery("CREATE TABLE IF NOT EXISTS #__${name}_autopost_logs (id bigint unsigned NOT NULL AUTO_INCREMENT,post_id int unsigned NOT NULL,provider varchar(24) NOT NULL,event varchar(12) NOT NULL,status varchar(12) NOT NULL,remote_id varchar(255) NOT NULL DEFAULT '',message text NOT NULL,response text NOT NULL,created datetime NOT NULL,PRIMARY KEY (id),KEY idx_post (post_id),KEY idx_provider_status (provider,status),KEY idx_created (created)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
        return true;
    }

    private function repairAdminMenu(): bool
    {
        `$db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        `$query = `$db->getQuery(true)
            ->update(`$db->quoteName('#__menu'))
            ->set(`$db->quoteName('link') . ' = REPLACE(' . `$db->quoteName('link') . ', ' . `$db->quote('index.php?index.php?') . ', ' . `$db->quote('index.php?') . ')')
            ->where(`$db->quoteName('client_id') . ' = 1')
            ->where(`$db->quoteName('component_id') . ' = (SELECT ' . `$db->quoteName('extension_id') . ' FROM ' . `$db->quoteName('#__extensions') . ' WHERE ' . `$db->quoteName('element') . ' = ' . `$db->quote('com_$name') . ' AND ' . `$db->quoteName('type') . ' = ' . `$db->quote('component') . ')');
        `$db->setQuery(`$query)->execute();
        `$componentId = (int) `$db->setQuery(`$db->getQuery(true)->select('extension_id')->from('#__extensions')->where('type=' . `$db->quote('component'))->where('element=' . `$db->quote('com_$name')))->loadResult();
        foreach ([
            '${name}-posts-group' => ['view=posts'],
            '${name}-autopost-group' => ['view=autopost&', 'view=autopostlogs'],
            '${name}-marketing-group' => ['view=subscribers', 'view=newsletter', 'view=emailtemplates'],
            '${name}-migration-group' => ['view=import', 'view=export'],
        ] as `$alias => `$needles) {
            `$parentId = (int) `$db->setQuery(`$db->getQuery(true)->select('id')->from('#__menu')->where('client_id=1')->where('component_id=' . `$componentId)->where('alias=' . `$db->quote(`$alias)))->loadResult();
            if (!`$parentId) { continue; }
            foreach (`$needles as `$needle) {
                `$ids = `$db->setQuery(`$db->getQuery(true)->select('id')->from('#__menu')->where('client_id=1')->where('component_id=' . `$componentId)->where('id<>' . `$parentId)->where('link LIKE ' . `$db->quote('%' . `$needle . '%')))->loadColumn();
                foreach (`$ids as `$id) {
                    `$table = new Menu(`$db);
                    if (`$table->load((int) `$id) && (int) `$table->parent_id !== `$parentId) {
                        `$table->setLocation(`$parentId, 'last-child'); `$table->parent_id = `$parentId; `$table->store();
                    }
                }
            }
        }
        return true;
    }

    private function seedComponentDefaults(): bool
    {
        `$db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        `$query = `$db->getQuery(true)
            ->select([`$db->quoteName('element'), `$db->quoteName('params')])
            ->from(`$db->quoteName('#__extensions'))
            ->where(`$db->quoteName('type') . ' = ' . `$db->quote('component'))
            ->where(`$db->quoteName('element') . ' IN (' . implode(',', `$db->quote(['com_content', 'com_$name'])) . ')');
        `$rows = `$db->setQuery(`$query)->loadAssocList('element', 'params');

        `$current = trim((string) (`$rows['com_$name'] ?? ''));
        `$defaults = (string) (`$rows['com_content'] ?? '');
        `$params = new Joomla\Registry\Registry(
            ((`$current === '' || `$current === '{}') && `$defaults !== '') ? `$defaults : `$current
        );

        // These post components use their own direct status and featured fields.
        // com_content's workflow setting must not be inherited: without matching
        // workflow records Joomla replaces both controls with an unusable dash.
        `$params->set('workflow_enabled', 0);

        `$query = `$db->getQuery(true)
            ->update(`$db->quoteName('#__extensions'))
            ->set(`$db->quoteName('params') . ' = ' . `$db->quote((string) `$params))
            ->where(`$db->quoteName('type') . ' = ' . `$db->quote('component'))
            ->where(`$db->quoteName('element') . ' = ' . `$db->quote('com_$name'));
        `$db->setQuery(`$query)->execute();

        return true;
    }
}
"@
    [IO.File]::WriteAllText("$target/script.php", $installerScript, [Text.UTF8Encoding]::new($false))

    $manifest = @"
<?xml version="1.0" encoding="UTF-8"?>
<extension type="component" method="upgrade">
  <name>com_$name</name>
  <author>Local Joomla Development</author>
  <creationDate>2026-09</creationDate>
  <copyright>Derived from Joomla com_content under GPL-2.0-or-later</copyright>
  <license>GNU General Public License version 2 or later</license>
  <version>1.0.0</version>
  <description>COM_${upper}_XML_DESCRIPTION</description>
  <scriptfile>script.php</scriptfile>
  <namespace path="src">Joomla\Component\$title</namespace>
  <install><sql><file driver="mysql" charset="utf8">sql/install.mysql.utf8.sql</file></sql></install>
  <uninstall><sql><file driver="mysql" charset="utf8">sql/uninstall.mysql.utf8.sql</file></sql></uninstall>
  <files folder="site"><folder>forms</folder><folder>helpers</folder><folder>layouts</folder><folder>src</folder><folder>tmpl</folder></files>
  <languages folder="site-language"><language tag="en-GB">en-GB/com_$name.ini</language></languages>
  <media destination="com_$name" folder="media"><filename>joomla.asset.json</filename><folder>js</folder><folder>css</folder></media>
  <administration>
    <menu link="index.php?option=com_$name">COM_$upper</menu>
    <submenu>
      <menu link="option=com_$name&amp;view=dashboard">COM_${upper}_DASHBOARD_TITLE</menu>
      <menu link="option=com_$name&amp;view=settings">COM_${upper}_SETTINGS</menu>
      <menu type="heading" alias="${name}-posts-group">COM_${upper}_POSTS_GROUP</menu>
      <menu link="option=com_$name&amp;view=posts&amp;filter[published]=*&amp;filter[featured]=">COM_${upper}_ALL_POSTS</menu>
      <menu link="option=com_$name&amp;view=posts&amp;filter[published]=1&amp;filter[featured]=">COM_${upper}_PUBLISHED</menu>
      <menu link="option=com_$name&amp;view=posts&amp;filter[published]=0&amp;filter[featured]=">COM_${upper}_UNPUBLISHED</menu>
      <menu link="option=com_$name&amp;view=posts&amp;filter[published]=-3&amp;filter[featured]=">COM_${upper}_PENDING_REVIEW</menu>
      <menu link="option=com_$name&amp;view=posts&amp;filter[published]=2&amp;filter[featured]=">COM_${upper}_ARCHIVED</menu>
      <menu link="option=com_$name&amp;view=posts&amp;filter[published]=-2&amp;filter[featured]=">COM_${upper}_TRASHED</menu>
      <menu link="option=com_$name&amp;view=posts&amp;filter[published]=*&amp;filter[featured]=1">COM_${upper}_FEATURED</menu>
      <menu type="heading" alias="${name}-autopost-group">COM_${upper}_AUTOPOST_GROUP</menu>
      <menu link="option=com_$name&amp;view=autopost&amp;provider=facebook">COM_${upper}_AUTOPOST_FACEBOOK</menu>
      <menu link="option=com_$name&amp;view=autopost&amp;provider=twitter">COM_${upper}_AUTOPOST_TWITTER</menu>
      <menu link="option=com_$name&amp;view=autopost&amp;provider=linkedin">COM_${upper}_AUTOPOST_LINKEDIN</menu>
      <menu link="option=com_$name&amp;view=autopostlogs">COM_${upper}_AUTOPOST_LOGS</menu>
      <menu link="option=com_categories&amp;extension=com_$name">JCATEGORIES</menu>
      <menu link="option=com_$name&amp;view=tags">JTAG</menu>
      <menu link="option=com_$name&amp;view=comments">COM_${upper}_COMMENTS_SETTINGS</menu>
      <menu link="option=com_$name&amp;view=polls">COM_${upper}_POLLS</menu>
      <menu type="heading" alias="${name}-marketing-group">COM_${upper}_MARKETING_GROUP</menu>
      <menu link="option=com_$name&amp;view=subscribers">COM_${upper}_SUBSCRIBERS</menu>
      <menu link="option=com_$name&amp;view=newsletter">COM_${upper}_EMAIL_CAMPAIGN</menu>
      <menu link="option=com_$name&amp;view=emailtemplates">COM_${upper}_EMAIL_TEMPLATES</menu>
      <menu type="heading" alias="${name}-migration-group">COM_${upper}_MIGRATION_GROUP</menu>
      <menu link="option=com_$name&amp;view=import">COM_${upper}_IMPORT_POSTS</menu>
      <menu link="option=com_$name&amp;view=export">COM_${upper}_EXPORT_POSTS</menu>
    </submenu>
    <files folder="admin"><filename>access.xml</filename><filename>config.xml</filename><filename>$name.xml</filename><folder>forms</folder><folder>helpers</folder><folder>layouts</folder><folder>presets</folder><folder>services</folder><folder>sql</folder><folder>src</folder><folder>tmpl</folder></files>
    <languages folder="language"><language tag="en-GB">en-GB/com_$name.ini</language><language tag="en-GB">en-GB/com_$name.sys.ini</language></languages>
  </administration>
  <api><files folder="api"><folder>src</folder></files></api>
  <dashboards><dashboard title="COM_${upper}_DASHBOARD_TITLE" icon="icon-file-alt">$name</dashboard></dashboards>
</extension>
"@
    [IO.File]::WriteAllText("$target/$name.xml", $manifest, [Text.UTF8Encoding]::new($false))

    $extraLanguage = "`r`nCOM_$upper=`"$title`"`r`nCOM_${upper}_XML_DESCRIPTION=`"Independent $title posts using Joomla categories.`"`r`nCOM_${upper}_FIELD_EDITOR_MODE_LABEL=`"Editor mode`"`r`nCOM_${upper}_EDITOR_USE_DEFAULT=`"Use component default`"`r`nCOM_${upper}_EDITOR_CLASSIC=`"Joomla editor (TinyMCE)`"`r`nCOM_${upper}_EDITOR_BLOCKS=`"Block Composer`"`r`nCOM_${upper}_CONFIG_EDITOR_LABEL=`"Editor Settings`"`r`nCOM_${upper}_CONFIG_DEFAULT_EDITOR_LABEL=`"Default editor`"`r`nCOM_${upper}_POSTS=`"Posts`"`r`nCOM_${upper}_ALL_POSTS=`"All Posts`"`r`nCOM_${upper}_PUBLISHED=`"Published`"`r`nCOM_${upper}_UNPUBLISHED=`"Unpublished`"`r`nCOM_${upper}_PENDING_REVIEW=`"Pending Review`"`r`nCOM_${upper}_ARCHIVED=`"Archived`"`r`nCOM_${upper}_TRASHED=`"Trashed`"`r`nCOM_${upper}_DRAFTS=`"Drafts`"`r`nCOM_${upper}_PENDING=`"Pending`"`r`nCOM_${upper}_POST_PENDING=`"Pending Review`"`r`nCOM_${upper}_DASHBOARD_TITLE=`"$title Dashboard`""
    $extraLanguage += "`r`nCOM_${upper}_SETTINGS=`"Settings`"`r`nCOM_${upper}_SETTINGS_SAVED=`"Settings saved.`""
    $extraLanguage += "`r`nCOM_${upper}_POST_LISTING_LAYOUT_LABEL=`"Post Listing Style`"`r`nCOM_${upper}_POST_LISTING_LAYOUT_ROWS=`"Rows`"`r`nCOM_${upper}_POST_LISTING_LAYOUT_COLUMNS=`"Columns`""
    $extraLanguage += "`r`nCOM_${upper}_COLUMN_STYLE_LABEL=`"Column Style`"`r`nCOM_${upper}_COLUMN_STYLE_GRID=`"Grid Layout`"`r`nCOM_${upper}_COLUMN_STYLE_MASONRY=`"Masonry Layout`"`r`nCOM_${upper}_COLUMNS_PER_ROW_LABEL=`"Columns Per Row`"`r`nCOM_${upper}_LIST_ITEM_STYLE_CARD=`"Card Layout`"`r`nCOM_${upper}_LIST_ITEM_STYLE_LEARNING=`"Learning Layout`"`r`nCOM_${upper}_LIST_ITEM_STYLE_SIMPLE=`"Simple Layout`"`r`nCOM_${upper}_LIST_ITEM_STYLE_NICKEL=`"Nickel Layout`""
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.ini" -Value $extraLanguage
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.sys.ini" -Value $extraLanguage
    $commentsLanguage = "`r`nCOM_${upper}_COMMENTS_SETTINGS=`"Comments`"`r`nCOM_${upper}_COMMENTS_PROVIDER=`"Comment provider`"`r`nCOM_${upper}_DISQUS_SHORTNAME=`"Disqus shortname`"`r`nCOM_${upper}_INTENSEDEBATE_ACCOUNT=`"IntenseDebate account ID`"`r`nCOM_${upper}_HYPERCOMMENTS_WIDGET_ID=`"HyperComments widget ID`"`r`nCOM_${upper}_POLLS=`"Polls`"`r`nCOM_${upper}_IMPORT_POSTS=`"Import Posts`"`r`nCOM_${upper}_POLLS_SETTINGS=`"Poll Settings`"`r`nCOM_${upper}_POLL_SHOW_VOTES=`"Show vote numbers`"`r`nCOM_${upper}_POLL_RESULTS_STYLE=`"Results style`"`r`nCOM_${upper}_POLL_STYLE_PROGRESS=`"Bootstrap progress bars`"`r`nCOM_${upper}_POLL_STYLE_SIMPLE=`"Simple list`"`r`nCOM_${upper}_POLL_STYLE_BADGES=`"Badges`"`r`nCOM_${upper}_POLL_PROGRESS_LABELS=`"Show percentage labels inside bars`"`r`nCOM_${upper}_POLL_PROGRESS_STRIPED=`"Striped progress bars`"`r`nCOM_${upper}_POLL_PROGRESS_COLOR_MODE=`"Progress bar backgrounds`"`r`nCOM_${upper}_POLL_COLOR_PALETTE=`"Bootstrap color palette`"`r`nCOM_${upper}_POLL_COLOR_PRIMARY=`"Bootstrap primary`"`r`nCOM_${upper}_POLL_COLOR_CUSTOM=`"Custom color`"`r`nCOM_${upper}_POLL_PROGRESS_CUSTOM_COLOR=`"Progress bar color`""
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.ini" -Value $commentsLanguage
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.sys.ini" -Value $commentsLanguage
    $exportLanguage = "`r`nCOM_${upper}_EXPORT_POSTS=`"Export Posts`""
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.ini" -Value $exportLanguage
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.sys.ini" -Value $exportLanguage
    $engagementLanguage = "`r`nCOM_${upper}_ENGAGEMENT_SETTINGS=`"Post Engagement`"`r`nCOM_${upper}_ENABLE_RATINGS=`"Enable post ratings`"`r`nCOM_${upper}_ENABLE_SHARING=`"Enable social sharing`"`r`nCOM_${upper}_ENABLE_SUBSCRIBE=`"Enable subscription form`"`r`nCOM_${upper}_ENGAGEMENT_APPEARANCE=`"Engagement Appearance`"`r`nCOM_${upper}_RATING_LABEL=`"Rating label`"`r`nCOM_${upper}_SUBSCRIBE_HEADING=`"Subscription heading`"`r`nCOM_${upper}_SUBSCRIBE_TEXT=`"Subscription text`"`r`nCOM_${upper}_SUBSCRIBE_BUTTON=`"Subscription button label`"`r`nCOM_${upper}_SUBSCRIBE_CONSENT=`"Consent statement`""
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.ini" -Value $engagementLanguage
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.sys.ini" -Value $engagementLanguage
    $mailLanguage = "`r`nCOM_${upper}_SUBSCRIBERS=`"Subscribers`"`r`nCOM_${upper}_EMAIL_CAMPAIGN=`"Email Campaign`"`r`nCOM_${upper}_EMAIL_TEMPLATES=`"Email Templates`"`r`nCOM_${upper}_EMAIL_DELIVERY_SETTINGS=`"Email Delivery`"`r`nCOM_${upper}_EMAIL_SERVICE=`"Delivery service`"`r`nCOM_${upper}_CAMPAIGN_FROM_EMAIL=`"Sender email`"`r`nCOM_${upper}_CAMPAIGN_FROM_NAME=`"Sender name`"`r`nCOM_${upper}_CAMPAIGN_REPLY_TO=`"Reply-to email`"`r`nCOM_${upper}_EMAIL_TRACKING_SETTINGS=`"Email Tracking`"`r`nCOM_${upper}_EMAIL_OPEN_TRACKING=`"Track email opens`"`r`nCOM_${upper}_EMAIL_LINK_TRACKING=`"Track link clicks`""
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.ini" -Value $mailLanguage
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.sys.ini" -Value $mailLanguage
    $autopostLanguage = "`r`nCOM_${upper}_POSTS_GROUP=`"Posts`"`r`nCOM_${upper}_AUTOPOST_GROUP=`"Autoposting`"`r`nCOM_${upper}_MARKETING_GROUP=`"Marketing`"`r`nCOM_${upper}_MIGRATION_GROUP=`"Migration`"`r`nCOM_${upper}_AUTOPOST_FACEBOOK=`"Facebook`"`r`nCOM_${upper}_AUTOPOST_TWITTER=`"X / Twitter`"`r`nCOM_${upper}_AUTOPOST_LINKEDIN=`"LinkedIn`"`r`nCOM_${upper}_AUTOPOST_LOGS=`"Logs`""
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.ini" -Value $autopostLanguage
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.sys.ini" -Value $autopostLanguage
    $listDisplayLanguage = "`r`nCOM_${upper}_LIST_DISPLAY_SETTINGS=`"List Display`"`r`nCOM_${upper}_LIST_EXCERPT_LENGTH_LABEL=`"List excerpt length`"`r`nCOM_${upper}_LIST_EXCERPT_LENGTH_DESC=`"Character length for an automatic excerpt on list pages when a post has no manual Read More split. Set to 0 to always show full content on lists.`"`r`nCOM_${upper}_LIST_ITEM_STYLE_LABEL=`"List item style`"`r`nCOM_${upper}_LIST_ITEM_STYLE_DESC=`"Layout used for each post on list pages. Add more by creating a new folder under site/layouts/postlist and adding it as an option here.`"`r`nCOM_${upper}_LIST_ITEM_STYLE_STANDARD=`"Standard (avatar, rating, share buttons)`"`r`nCOM_${upper}_LIST_ITEM_STYLE_COMPACT=`"Compact (hits and comments only)`""
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.ini" -Value $listDisplayLanguage
    Add-Content -LiteralPath "$target/language/en-GB/com_$name.sys.ini" -Value $listDisplayLanguage

    $articleModelPath = "$target/admin/src/Model/ArticleModel.php"
    $articleModel = [IO.File]::ReadAllText($articleModelPath)
    $savePrelude = @"
    public function save(`$data)
    {
        `$autopostWasNew = empty(`$data['id']);
        // Store the editor field in the two native text columns without relying on ContentTable's legacy field alias.
        if (array_key_exists('articletext', `$data)) {
            `$parts = preg_split('#<hr\s+id=(?:"|&quot;)system-readmore(?:"|&quot;)\s*/?>#i', (string) `$data['articletext'], 2);
            `$data['introtext'] = `$parts[0] ?? '';
            `$data['fulltext']  = `$parts[1] ?? '';
            unset(`$data['articletext']);
        }
"@.TrimEnd().Replace("`n", "`r`n")
    $articleModel = [regex]::Replace($articleModel, '    public function save\(\$data\)\s*\{', [System.Text.RegularExpressions.MatchEvaluator]{ param($match) $savePrelude }, 1)
    $autopostHook = @"
            `$this->workflowAfterSave(`$data);

            try {
                (new \Joomla\Component\$title\Administrator\Service\AutopostService())->publish(
                    (int) `$this->getState(`$this->getName() . '.id'),
                    `$autopostWasNew ? 'new' : 'update'
                );
            } catch (\Throwable `$autopostError) {
                Factory::getApplication()->enqueueMessage('The post was saved, but autoposting could not run: ' . `$autopostError->getMessage(), 'warning');
            }
"@.TrimEnd().Replace("`n", "`r`n")
    $articleModel = $articleModel.Replace('            $this->workflowAfterSave($data);', $autopostHook)
    [IO.File]::WriteAllText($articleModelPath, $articleModel, [Text.UTF8Encoding]::new($false))
    Convert-ArticleCodeToPosts $target
    Convert-LegacyContentStorage $target

    # Global Rows/Columns setting with a per-menu Use Global override.
    foreach ($menuFormPath in @("$target/site/tmpl/category/blog.xml", "$target/site/tmpl/featured/default.xml")) {
        [xml]$menuForm = Get-Content -LiteralPath $menuFormPath -Raw
        $advanced = $menuForm.metadata.fields.fieldset | Where-Object { $_.name -eq 'advanced' } | Select-Object -First 1
        if ($advanced) {
            $field = $menuForm.CreateElement('field')
            $field.SetAttribute('name', 'post_listing_layout')
            $field.SetAttribute('type', 'list')
            $field.SetAttribute('label', "COM_${upper}_POST_LISTING_LAYOUT_LABEL")
            $field.SetAttribute('useglobal', 'true')
            $field.SetAttribute('validate', 'options')
            foreach ($data in @(@('rows', "COM_${upper}_POST_LISTING_LAYOUT_ROWS"), @('columns', "COM_${upper}_POST_LISTING_LAYOUT_COLUMNS"))) {
                $option = $menuForm.CreateElement('option'); $option.SetAttribute('value', $data[0]); $option.InnerText = $data[1]; [void]$field.AppendChild($option)
            }
            if ($advanced.FirstChild) { [void]$advanced.InsertBefore($field, $advanced.FirstChild) } else { [void]$advanced.AppendChild($field) }
            foreach ($definition in @(
                @('column_style', "COM_${upper}_COLUMN_STYLE_LABEL", @(@('grid', "COM_${upper}_COLUMN_STYLE_GRID"), @('masonry', "COM_${upper}_COLUMN_STYLE_MASONRY")), 'post_listing_layout:columns'),
                @('columns_per_row', "COM_${upper}_COLUMNS_PER_ROW_LABEL", @(@('2', '2 Columns'), @('3', '3 Columns'), @('4', '4 Columns'), @('5', '5 Columns'), @('6', '6 Columns')), 'post_listing_layout:columns'),
                @('list_item_style', "COM_${upper}_LIST_ITEM_STYLE_LABEL", @(@('standard', "COM_${upper}_LIST_ITEM_STYLE_STANDARD"), @('card', "COM_${upper}_LIST_ITEM_STYLE_CARD"), @('learning', "COM_${upper}_LIST_ITEM_STYLE_LEARNING"), @('simple', "COM_${upper}_LIST_ITEM_STYLE_SIMPLE"), @('nickel', "COM_${upper}_LIST_ITEM_STYLE_NICKEL")), '')
            )) {
                $dependent = $menuForm.CreateElement('field'); $dependent.SetAttribute('name', $definition[0]); $dependent.SetAttribute('type', 'list'); $dependent.SetAttribute('label', $definition[1]); $dependent.SetAttribute('useglobal', 'true'); $dependent.SetAttribute('validate', 'options')
                if ($definition[3]) { $dependent.SetAttribute('showon', $definition[3]) }
                foreach ($data in $definition[2]) { $option=$menuForm.CreateElement('option'); $option.SetAttribute('value',$data[0]); $option.InnerText=$data[1]; [void]$dependent.AppendChild($option) }
                [void]$advanced.InsertBefore($dependent, $advanced.FirstChild)
            }
            $menuForm.Save($menuFormPath)
        }
    }

    foreach ($listTemplatePath in @("$target/site/tmpl/category/blog.php", "$target/site/tmpl/featured/default.php")) {
        $listTemplate = [IO.File]::ReadAllText($listTemplatePath)
        $listTemplate = $listTemplate.Replace('items-leading <?php echo $this->params->get(''blog_class_leading''); ?>', 'items-leading post-style-<?php echo $this->params->get(''list_item_style'', ''standard''); ?> <?php echo $this->params->get(''blog_class_leading''); ?>')
        $layoutSetup = "<?php `$blogClass = `$this->params->get('blog_class', ''); ?>`r`n        <?php `$listingLayout = `$this->params->get('post_listing_layout', 'rows'); `$columnStyle = `$this->params->get('column_style', 'grid'); `$columnsPerRow = max(2, min(6, (int) `$this->params->get('columns_per_row', 2))); `$postStyle = `$this->params->get('list_item_style', 'standard'); ?>`r`n        <?php if (`$listingLayout === 'rows') { `$blogClass .= ' columns-1 post-listing-rows'; } else { `$blogClass .= ' post-listing-columns post-listing-' . `$columnStyle . ' columns-' . `$columnsPerRow; } `$blogClass .= ' post-style-' . `$postStyle; ?>"
        $listTemplate = $listTemplate.Replace("<?php `$blogClass = `$this->params->get('blog_class', ''); ?>", $layoutSetup)
        $listTemplate = $listTemplate.Replace("<?php if ((int) `$this->params->get('num_columns') > 1) : ?>", "<?php if (false) : ?>")
        $listTemplate += @'

<style>
.post-listing-grid{display:grid;grid-template-columns:repeat(var(--post-listing-columns,2),minmax(0,1fr));gap:1.5rem}
.post-listing-masonry{column-count:var(--post-listing-columns,2);column-gap:1.5rem}
.post-listing-masonry>.blog-item{display:inline-block;width:100%;break-inside:avoid;margin:0 0 1.5rem}
.post-listing-columns{--post-listing-columns:2}
.post-listing-columns.columns-3{--post-listing-columns:3}.post-listing-columns.columns-4{--post-listing-columns:4}.post-listing-columns.columns-5{--post-listing-columns:5}.post-listing-columns.columns-6{--post-listing-columns:6}
.items-leading.post-style-card{display:block;width:100%;margin-bottom:1.5rem}
.items-leading.post-style-card>.blog-item{width:100%}
.post-style-card>.blog-item{display:flex;flex-direction:column;height:100%;padding:0;overflow:hidden;border:1px solid var(--border-color,#dee2e6);border-radius:.65rem;background:var(--card-bg,#fff);box-shadow:0 .25rem .9rem rgba(0,0,0,.09);transition:transform .18s ease,box-shadow .18s ease}
.post-style-card>.blog-item:hover{transform:translateY(-2px);box-shadow:0 .55rem 1.35rem rgba(0,0,0,.13)}
.post-style-card>.blog-item>.item-image{width:100%;aspect-ratio:16/9;margin:0;overflow:hidden;background:#f0f1f3}
.post-style-card>.blog-item>.item-image>a{display:block;width:100%;height:100%}
.post-style-card>.blog-item>.item-image img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .25s ease}
.post-style-card>.blog-item>.post-card-placeholder{display:flex;align-items:center;justify-content:center;color:var(--secondary-color,#6c757d)}
.post-style-card>.blog-item>.post-card-placeholder svg{display:block;width:100%;height:100%}
.post-style-card>.blog-item:hover>.item-image img{transform:scale(1.025)}
.post-style-card>.blog-item>.item-content{display:flex;flex:1;flex-direction:column;padding:1.35rem}
.post-style-card>.blog-item>.item-content .item-title{margin-top:0;font-size:1.35rem;font-weight:700;line-height:1.25}
.post-style-card>.blog-item>.item-content .readmore{margin-top:1rem}
.post-style-card>.blog-item>.item-content .readmore .btn{font-weight:600}
.post-style-card>.blog-item>.item-content .postmeta-row{margin-top:auto!important;padding-top:1rem;border-top:1px solid var(--border-color,#dee2e6)}
.post-style-card>.blog-item>.item-content .postmeta-share{display:none!important}
.post-style-card>.blog-item>.item-content .article-info{display:none!important}
.post-style-card>.blog-item>.item-content .item-title .postmeta-avatar,.post-style-card>.blog-item>.item-content .item-title .postmeta-avatar-img{display:none!important}
.post-card-footer{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-top:.9rem;padding-top:1rem;border-top:1px solid var(--border-color,#dee2e6);color:var(--secondary-color,#6c757d)}
.post-card-footer-details{display:flex;min-width:0;flex-direction:column;gap:.2rem}
.post-card-footer-date{font-size:.9rem}
.post-card-footer-category{font-size:.9rem}
.post-card-footer-category a{color:inherit}
.post-card-footer-author{margin-left:auto;line-height:0}
.post-card-footer-author a{display:inline-flex;border-radius:.375rem}
.post-card-footer-author a:focus-visible{outline:3px solid currentColor;outline-offset:3px}
.post-style-learning>.blog-item{display:flex;flex-direction:column;height:100%;padding:0;overflow:hidden;border:1px solid var(--border-color,#dee2e6);border-radius:.65rem;background:var(--card-bg,#fff);box-shadow:0 .25rem .9rem rgba(0,0,0,.09);transition:transform .18s ease,box-shadow .18s ease}
.post-style-learning>.blog-item:hover{transform:translateY(-2px);box-shadow:0 .55rem 1.35rem rgba(0,0,0,.13)}
.post-style-learning>.blog-item>.item-image,.post-style-learning>.blog-item>.post-card-placeholder{display:flex;width:100%;aspect-ratio:16/9;margin:0;overflow:hidden;background:#f0f1f3;color:var(--secondary-color,#6c757d)}
.post-style-learning>.blog-item>.item-image>a{display:block;width:100%;height:100%}
.post-style-learning>.blog-item>.item-image img,.post-style-learning>.blog-item>.post-card-placeholder svg{display:block;width:100%;height:100%;object-fit:cover}
.post-style-learning>.blog-item>.learning-item-content{display:flex;flex:1;flex-direction:column;padding:1rem 1.1rem}
.post-style-learning>.blog-item>.learning-item-content .item-title{display:block;margin:0 0 .8rem;font-size:1.05rem;font-weight:700;line-height:1.3}
.post-style-learning>.blog-item>.learning-item-content .item-title span{display:block}
.learning-item-details{display:flex;flex-direction:column;gap:.65rem;color:var(--secondary-color,#6c757d);font-size:.9rem}
.learning-item-author{display:flex;align-items:center;gap:.55rem}
.learning-item-author .postmeta-avatar,.learning-item-author .postmeta-avatar-img{width:1.75rem;height:1.75rem}
.learning-item-category a{color:inherit}
.items-leading.post-style-learning{display:block;width:100%;margin-bottom:1.5rem}
.items-leading.post-style-learning>.blog-item{width:100%}
.post-style-simple>.blog-item{padding:.75rem 0;border-bottom:1px solid var(--border-color,#dee2e6)}
.post-style-nickel>.blog-item{padding:1.25rem;border-left:.35rem solid #6c757d;background:color-mix(in srgb,var(--card-bg,#fff) 94%,#6c757d)}
@media(max-width:991.98px){.post-listing-columns.columns-4,.post-listing-columns.columns-5,.post-listing-columns.columns-6{--post-listing-columns:3}}
@media(max-width:767.98px){.post-listing-grid{grid-template-columns:1fr}.post-listing-masonry{column-count:1}}
</style>
'@
        $listTemplate = $listTemplate.Replace('<div class="com-content-category-blog__items blog-items <?php echo $blogClass; ?>">', '<div class="com-content-category-blog__items blog-items <?php echo $blogClass; ?>" style="--post-listing-columns:<?php echo (int) $columnsPerRow; ?>">')
        $listTemplate = $listTemplate.Replace('<div class="blog-items <?php echo $blogClass; ?>">', '<div class="blog-items <?php echo $blogClass; ?>" style="--post-listing-columns:<?php echo (int) $columnsPerRow; ?>">')
        [IO.File]::WriteAllText($listTemplatePath, $listTemplate, [Text.UTF8Encoding]::new($false))
    }

    foreach ($variant in @(@('card','standard'), @('learning','standard'), @('simple','compact'), @('nickel','standard'))) {
        $variantPath = "$target/site/layouts/postlist/$($variant[0])"
        New-Item -ItemType Directory -Path $variantPath -Force | Out-Null
        Copy-Item -Path "$target/site/layouts/postlist/$($variant[1])/*" -Destination $variantPath -Force
    }

    $learningDetails = @'
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\FamilyName\Site\Helper\RouteHelper;

$item = $displayData;
?>
<div class="learning-item-details">
    <div class="learning-item-author">
        <?php echo LayoutHelper::render('postlist.learning.avatar', $item, JPATH_COMPONENT . '/layouts'); ?>
        <span><?php echo htmlspecialchars((string) ($item->author ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <?php if (!empty($item->category_title)) : ?>
        <div class="learning-item-category">
            <a href="<?php echo Route::_(RouteHelper::getCategoryRoute((int) $item->catid, $item->language)); ?>"><?php echo htmlspecialchars((string) $item->category_title, ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
    <?php endif; ?>
</div>
'@
    $learningDetails = $learningDetails.Replace('FamilyName', $title)
    [IO.File]::WriteAllText("$target/site/layouts/postlist/learning/details.php", $learningDetails, [Text.UTF8Encoding]::new($false))

    $cardFooter = @'
<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;

$item = $displayData;
$date = $item->publish_up ?: $item->created;
$category = trim((string) ($item->category_title ?? ''));
?>
<footer class="post-card-footer">
    <div class="post-card-footer-details">
        <?php if ($date) : ?>
            <time class="post-card-footer-date" datetime="<?php echo HTMLHelper::_('date', $date, 'c'); ?>">
                <?php echo HTMLHelper::_('date', $date, 'l, d F Y'); ?>
            </time>
        <?php endif; ?>
        <?php if ($category !== '') : ?>
            <span class="post-card-footer-category">
                <a href="<?php echo Route::_(RouteHelper::getCategoryRoute((int) $item->catid, $item->language)); ?>"><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></a>
            </span>
        <?php endif; ?>
    </div>
    <span class="post-card-footer-author">
        <?php echo LayoutHelper::render('postlist.card.avatar', $item, JPATH_COMPONENT . '/layouts'); ?>
    </span>
</footer>
'@
    $cardFooter = $cardFooter.Replace('Joomla\Component\Content', "Joomla\Component\$title")
    [IO.File]::WriteAllText("$target/site/layouts/postlist/card/footer.php", $cardFooter, [Text.UTF8Encoding]::new($false))

    $authorViewPath = "$target/site/src/View/Author"
    New-Item -ItemType Directory -Path $authorViewPath -Force | Out-Null
    $authorView = @'
<?php
namespace Joomla\Component\FamilyName\Site\View\Author;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public ?object $author = null;
    public array $posts = [];

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $authorId = $app->getInput()->getInt('id');

        if ($authorId < 1) {
            throw new \RuntimeException('Author not found.', 404);
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->author = $db->setQuery(
            $db->createQuery()->select(['id', 'name'])->from('#__users')->where('id=' . $authorId)->where('block=0')
        )->loadObject();

        if (!$this->author) {
            throw new \RuntimeException('Author not found.', 404);
        }

        $levels = array_map('intval', $app->getIdentity()->getAuthorisedViewLevels());
        $now = Factory::getDate()->toSql();
        $query = $db->createQuery()
            ->select(['id', 'title', 'alias', 'catid', 'introtext', 'images', 'publish_up', 'created', 'language'])
            ->from('#__familyname')
            ->where('created_by=' . $authorId)
            ->where('state=1')
            ->whereIn('access', $levels)
            ->where('(publish_up IS NULL OR publish_up <= ' . $db->quote($now) . ')')
            ->where('(publish_down IS NULL OR publish_down >= ' . $db->quote($now) . ')')
            ->order('publish_up DESC, created DESC');
        $this->posts = $db->setQuery($query)->loadObjectList();

        $this->document->setTitle($this->author->name);
        parent::display($tpl);
    }
}
'@
    $authorView = $authorView.Replace('FamilyName', $title).Replace('#__familyname', "#__${name}")
    [IO.File]::WriteAllText("$authorViewPath/HtmlView.php", $authorView, [Text.UTF8Encoding]::new($false))

    $authorTemplatePath = "$target/site/tmpl/author"
    New-Item -ItemType Directory -Path $authorTemplatePath -Force | Out-Null
    $authorTemplate = @'
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\FamilyName\Site\Helper\RouteHelper;

$avatarItem = (object) ['created_by' => $this->author->id];
?>
<div class="com-familyname-author">
    <header class="author-profile-header d-flex align-items-center gap-3 mb-4">
        <?php echo LayoutHelper::render('postlist.card.avatar', $avatarItem, JPATH_COMPONENT . '/layouts'); ?>
        <div>
            <h1 class="mb-1"><?php echo $this->escape($this->author->name); ?></h1>
            <div class="text-muted"><?php echo count($this->posts); ?> published post<?php echo count($this->posts) === 1 ? '' : 's'; ?></div>
        </div>
    </header>

    <div class="author-profile-posts">
        <?php foreach ($this->posts as $post) : ?>
            <article class="author-profile-post py-3 border-top">
                <h2 class="h4"><a href="<?php echo Route::_(RouteHelper::getPostRoute($post->id . ':' . $post->alias, $post->catid, $post->language)); ?>"><?php echo $this->escape($post->title); ?></a></h2>
                <time class="text-muted small" datetime="<?php echo $this->escape($post->publish_up ?: $post->created); ?>"><?php echo $this->escape($post->publish_up ?: $post->created); ?></time>
                <?php if ($post->introtext !== '') : ?><div class="mt-2"><?php echo $post->introtext; ?></div><?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (!$this->posts) : ?><p class="alert alert-info">This author has no published posts.</p><?php endif; ?>
    </div>
</div>
'@
    $authorTemplate = $authorTemplate.Replace('FamilyName', $title).Replace('com-familyname', "com-$name")
    [IO.File]::WriteAllText("$authorTemplatePath/default.php", $authorTemplate, [Text.UTF8Encoding]::new($false))

    $rssLayout = @'
<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
$params = $displayData;
if ($params->get('show_feed_link', 1)) :
    $feedUri = clone Uri::getInstance();
    $feedUri->setVar('format', 'feed');
    $feedUri->setVar('type', 'rss');
    $feedUri->delVar('limitstart');
?>
<div class="post-rss-link mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo Route::_($feedUri->toString()); ?>" type="application/rss+xml">
        <span class="icon-feed" aria-hidden="true"></span> RSS Feed
    </a>
</div>
<?php endif; ?>
'@
    [IO.File]::WriteAllText("$target/site/layouts/rsslink.php", $rssLayout, [Text.UTF8Encoding]::new($false))
    foreach ($templatePath in @("$target/site/tmpl/featured/default.php", "$target/site/tmpl/category/blog.php")) {
        $template = [IO.File]::ReadAllText($templatePath)
        $nav = "    <?php echo LayoutHelper::render('postnav', (object) [], JPATH_COMPONENT . '/layouts'); ?>"
        $template = $template.Replace($nav, $nav + "`r`n    <?php echo LayoutHelper::render('rsslink', `$this->params, JPATH_COMPONENT . '/layouts'); ?>")
        [IO.File]::WriteAllText($templatePath, $template, [Text.UTF8Encoding]::new($false))
    }

    $hiddenFeedLinks = @'
        // Add feed links
        if ($this->params->get('show_feed_link', 1)) {
            $link    = '&format=feed&limitstart=';
            $attribs = ['type' => 'application/rss+xml', 'title' => htmlspecialchars($this->getDocument()->getTitle())];
            $this->getDocument()->addHeadLink(Route::_($link . '&type=rss'), 'alternate', 'rel', $attribs);
            $attribs = ['type' => 'application/atom+xml', 'title' => htmlspecialchars($this->getDocument()->getTitle())];
            $this->getDocument()->addHeadLink(Route::_($link . '&type=atom'), 'alternate', 'rel', $attribs);
        }
'@
    $featuredHtmlPath = "$target/site/src/View/Featured/HtmlView.php"
    $featuredHtml = [IO.File]::ReadAllText($featuredHtmlPath).Replace($hiddenFeedLinks.Replace("`n", "`r`n"), '').Replace($hiddenFeedLinks, '')
    [IO.File]::WriteAllText($featuredHtmlPath, $featuredHtml, [Text.UTF8Encoding]::new($false))

    foreach ($feedPath in @("$target/site/src/View/Featured/FeedView.php", "$target/site/src/View/Category/FeedView.php")) {
        $feedView = [IO.File]::ReadAllText($feedPath)
        $feedView = $feedView.Replace('        $params    = $app->getParams();', "        `$params    = `$app->getParams();`r`n        `$this->getDocument()->setGenerator('Genesis $title');")
        $feedView = $feedView.Replace('        $params            = $app->getParams();', "        `$params            = `$app->getParams();`r`n        `$this->getDocument()->setGenerator('Genesis $title');")
        $feedView = $feedView.Replace('($params->get(''feed_summary'', 0) ? $row->introtext . $row->fulltext : $row->introtext)', '($params->get(''feed_summary'', 0) ? $row->introtext . $row->fulltext : HTMLHelper::_(''string.truncateComplex'', $row->introtext, max(100, (int) $params->get(''list_excerpt_length'', 400))))')
        $feedView = $feedView.Replace('($params->get(''feed_summary'', 0) ? $item->introtext . $item->fulltext : $item->introtext)', '($params->get(''feed_summary'', 0) ? $item->introtext . $item->fulltext : HTMLHelper::_(''string.truncateComplex'', $item->introtext, max(100, (int) $params->get(''list_excerpt_length'', 400))))')
        [IO.File]::WriteAllText($feedPath, $feedView, [Text.UTF8Encoding]::new($false))
    }

    # The family components do not expose Joomla's legacy Link A/B/C metadata.
    $linksLayout = "$target/site/tmpl/post/default_links.php"
    if (Test-Path -LiteralPath $linksLayout) { Remove-Item -LiteralPath $linksLayout -Force }

    $postTemplatePath = "$target/site/tmpl/post/default.php"
    $postTemplate = [IO.File]::ReadAllText($postTemplatePath)
    $postTemplate = [regex]::Replace($postTemplate, '(?ms)^\s*<\?php if \(\(int\) \$params->get\(''urls_position'', 0\) === [01]\) : \?>\s*^\s*<\?php echo \$this->loadTemplate\(''links''\); \?>\s*^\s*<\?php endif; \?>\s*', '')
    $postTemplate = [regex]::Replace($postTemplate, '(?m)^\s*<\?php echo LayoutHelper::render\(''joomla\.content\.full_image'', \$this->item\); \?>\s*\r?\n?', '')
    [IO.File]::WriteAllText($postTemplatePath, $postTemplate, [Text.UTF8Encoding]::new($false))

    $frontendEditPath = "$target/site/tmpl/form/edit.php"
    $frontendEdit = [IO.File]::ReadAllText($frontendEditPath)
    $frontendEdit = [regex]::Replace($frontendEdit, '(?ms)^\s*<\?php echo \$this->form->renderField\(''urla'', ''urls''\); \?>.*?^\s*<\?php echo \$this->form->getInput\(''targetc'', ''urls''\); \?>\s*^\s*</div>\s*^\s*</div>\s*', '')
    $frontendEdit = [regex]::Replace($frontendEdit, '(?m)^[^\r\n]*renderField\(''(?:image_fulltext|image_fulltext_alt|image_fulltext_alt_empty|image_fulltext_caption|float_fulltext|image_body|image_body_alt|image_body_alt_empty|image_body_caption|float_body)'', ''(?:images|media)''\)[^\r\n]*\r?\n?', '')
    $frontendEdit = $frontendEdit.Replace("['image-intro', 'image-full', 'jmetadata', 'item_associations']", "['image-featured', 'jmetadata', 'item_associations']")
    $frontendFeaturedPanel = @'

                <?php if ($params->get('show_urls_images_frontend')) : ?>
                    <fieldset id="fieldset-image-featured" class="options-form mt-4">
                        <legend><?php echo Text::_('Featured Image'); ?></legend>
                        <?php echo $this->form->renderField('featured_image', 'media'); ?>
                        <?php echo $this->form->renderField('featured_image_alt', 'media'); ?>
                        <?php echo $this->form->renderField('featured_image_alt_empty', 'media'); ?>
                        <?php echo $this->form->renderField('featured_image_caption', 'media'); ?>
                        <?php echo $this->form->renderField('featured_image_class', 'media'); ?>
                    </fieldset>
                <?php endif; ?>
'@
    $frontendEdit = $frontendEdit.Replace(
        "                <?php echo `$this->form->renderField('post_content'); ?>",
        "                <?php echo `$this->form->renderField('post_content'); ?>" + $frontendFeaturedPanel
    )
    $frontendEdit = [regex]::Replace(
        $frontendEdit,
        '(?ms)\s*<\?php if \(\$params->get\(''show_urls_images_frontend''\)\) : \?>\s*<\?php echo HTMLHelper::_\(''uitab\.addTab'', \$this->tab_name, ''media''.*?<\?php endif; \?>',
        ''
    )
    [IO.File]::WriteAllText($frontendEditPath, $frontendEdit, [Text.UTF8Encoding]::new($false))

    $postModelPath = "$target/admin/src/Model/PostModel.php"
    $postModel = [IO.File]::ReadAllText($postModelPath)
    $postModel = [regex]::Replace($postModel, '(?ms)^\s*if \(isset\(\$data\[''urls''\]\) && \\is_array\(\$data\[''urls''\]\)\) \{.*?^\s*\}\s*\r?\n(?=\s*// Alter the title for save as copy)', '')
    $postModel = [regex]::Replace($postModel, '(?ms)^\s*\$check = \$input->post->get\(''jform'', \[\], ''array''\);.*?^\s*\}\s*\r?\n(?=\s*// Alter the title for save as copy)', '')
    [IO.File]::WriteAllText($postModelPath, $postModel, [Text.UTF8Encoding]::new($false))

    $adminEditPath = "$target/admin/tmpl/post/edit.php"
    $adminEdit = [IO.File]::ReadAllText($adminEditPath)
    $adminEdit = $adminEdit.Replace("`$fieldsetsInLinks = ['linka', 'linkb', 'linkc'];`r`n", '').Replace("`$fieldsetsInLinks = ['linka', 'linkb', 'linkc'];`n", '')
    $adminEdit = $adminEdit.Replace("array_merge(['jmetadata', 'item_associations'], `$fieldsetsInImages, `$fieldsetsInLinks)", "array_merge(['jmetadata', 'item_associations'], `$fieldsetsInImages)")
    $adminEdit = $adminEdit.Replace("`$fieldsetsInImages = ['image-intro', 'image-full'];", "`$fieldsetsInImages = ['image-featured'];")
    $adminEdit = [regex]::Replace($adminEdit, '(?ms)\s*<div class="col-12 col-lg-6">\s*<\?php foreach \(\$fieldsetsInLinks as \$fieldset\) : \?>.*?<\?php endforeach; \?>\s*</div>', '')
    $featuredImagePanel = @'

                <?php if ($params->get('show_urls_images_backend') == 1) : ?>
                    <fieldset id="fieldset-image-featured" class="options-form mt-4">
                        <legend><?php echo Text::_($this->form->getFieldsets()['image-featured']->label); ?></legend>
                        <div><?php echo $this->form->renderFieldset('image-featured'); ?></div>
                    </fieldset>
                <?php endif; ?>
'@
    $adminEdit = [regex]::Replace(
        $adminEdit,
        '(?ms)(<div data-post-block-editor.*?</fieldset>\s*</div>)(\s*</div>\s*<div class="col-lg-3" data-post-global-column>)',
        '$1' + $featuredImagePanel + '$2',
        1
    )
    $adminEdit = [regex]::Replace(
        $adminEdit,
        '(?ms)\s*<\?php // Do not show the images and links options if the edit form is configured not to\. \?>\s*<\?php if \(\$params->get\(''show_urls_images_backend''\) == 1\) : \?>.*?<\?php endif; \?>',
        ''
    )
    [IO.File]::WriteAllText($adminEditPath, $adminEdit, [Text.UTF8Encoding]::new($false))

    $apiViewPath = "$target/api/src/View/Posts/JsonapiView.php"
    $apiView = [IO.File]::ReadAllText($apiViewPath)
    $apiView = [regex]::Replace($apiView, '(?ms)^\s*if \(!empty\(\$item->images\[''image_fulltext''\]\)\) \{.*?^\s*\}\s*', '')
    [IO.File]::WriteAllText($apiViewPath, $apiView, [Text.UTF8Encoding]::new($false))

    Replace-InTree $target @(
        @('image_intro_alt_empty', 'featured_image_alt_empty'),
        @('image_intro_caption', 'featured_image_caption'),
        @('image_intro_alt', 'featured_image_alt'),
        @('image_intro', 'featured_image'),
        @('float_intro', 'featured_image_class'),
        @('link_intro_image', 'link_featured_image'),
        @('intro_image', 'featured_image'),
        @("COM_${upper}_FIELD_INTRO_LABEL", "COM_${upper}_FIELD_FEATURED_LABEL"),
        @("COM_${upper}_IMAGE_INTRO_CLASS_LABEL", "COM_${upper}_IMAGE_FEATURED_CLASS_LABEL"),
        @('JGLOBAL_LINKED_INTRO_IMAGE_LABEL', "COM_${upper}_LINKED_FEATURED_IMAGE_LABEL")
    )

    foreach ($languagePath in @("$target/language/en-GB/com_$name.ini", "$target/site-language/en-GB/com_$name.ini")) {
        $languageText = [IO.File]::ReadAllText($languagePath)
        $languageText = [regex]::Replace($languageText, "(?im)^COM_${upper}_(?:FIELD_URL[ABC](?:_LINK_TEXT)?_LABEL|URL_FIELD_[ABC]_BROWSERNAV_LABEL)=.*\r?\n?", '')
        $languageText = [regex]::Replace($languageText, "(?im)^COM_${upper}_(?:FIELD_FULL_LABEL|IMAGE_FULLTEXT_CLASS_LABEL)=.*\r?\n?", '')
        $languageText = [regex]::Replace($languageText, "(?im)^(COM_${upper}_FIELD_FEATURED_LABEL)=.*$", '$1="Featured Image"')
        $languageText = [regex]::Replace($languageText, "(?im)^(COM_${upper}_IMAGE_FEATURED_CLASS_LABEL)=.*$", '$1="Featured Image Class"')
        $languageText = $languageText.Replace('Intro Image', 'Featured Image').Replace('intro image', 'featured image')
        if ($languageText -notmatch "(?m)^COM_${upper}_LINKED_FEATURED_IMAGE_LABEL=") {
            $languageText += "`r`nCOM_${upper}_LINKED_FEATURED_IMAGE_LABEL=`"Link Featured Image`"`r`n"
        }
        [IO.File]::WriteAllText($languagePath, $languageText, [Text.UTF8Encoding]::new($false))
    }
    Convert-LegacyContentStorage $target
    & "$SourceRoot/extensions/post-nav/apply-excerpt.ps1" -Target $target -Family $name
    & "$SourceRoot/extensions/post-editor/apply-editor.ps1" -Target $target -Family $name
    & "$SourceRoot/extensions/post-tags/apply-tags.ps1" -Target $target -Family $name
    & "$SourceRoot/extensions/post-categories/apply-categories.ps1" -Target $target -Family $name
}

# Fail the build when generated component code refers to a family-specific
# language key that was not delivered in both language locations.
& (Join-Path $PSScriptRoot 'verify-family-parity.ps1')
