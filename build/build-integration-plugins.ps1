param([string]$SourceRoot = (Split-Path (Split-Path $PSScriptRoot -Parent) -Parent))
$ErrorActionPreference = 'Stop'

$components = @(@{n='blog';t='Blog'}, @{n='codex';t='Codex'}, @{n='academy';t='Academy'})
$ports = @(
 @('content','confirmconsent','confirmconsent','ConfirmConsent'),
 @('content','contact','contact','Contact'), @('content','pagebreak','pagebreak','PageBreak'),
 @('content','emailcloak','emailcloak','EmailCloak'), @('content','fields','fields','Fields'),
 @('content','finder','finderindexer','Finder'), @('content','joomla','joomla','Joomla'),
 @('content','loadmodule','loadmodule','LoadModule'),
 @('content','pagenavigation','pagenavigation','PageNavigation'), @('content','vote','vote','Vote'),
 @('finder','content','finder','Content'), @('privacy','content','privacy','Content'),
 @('webservices','content','webservices','Content'),
 @('editors-xtd','article','postbutton','Article'), @('editors-xtd','contact','contactbutton','Contact'),
 @('editors-xtd','fields','fieldsbutton','Fields'), @('editors-xtd','image','imagebutton','Image'),
 @('editors-xtd','menu','menubutton','Menu'), @('editors-xtd','module','modulebutton','Module'),
 @('editors-xtd','pagebreak','pagebreakbutton','PageBreak'), @('editors-xtd','readmore','readmorebutton','ReadMore'),
 @('workflow','featuring','featuring','Featuring'), @('workflow','notification','notification','Notification'),
 @('workflow','publishing','publishing','Publishing'), @('actionlog','joomla','actionlog','Joomla')
)

function Rewrite([string]$path, [array]$pairs) {
 Get-ChildItem $path -Recurse -File | Where-Object Extension -in '.php','.xml','.ini','.js' | ForEach-Object {
  $s=[IO.File]::ReadAllText($_.FullName)
  foreach($p in $pairs){$s=$s.Replace($p[0],$p[1])}
  [IO.File]::WriteAllText($_.FullName,$s,[Text.UTF8Encoding]::new($false))
 }
}

function Convert-ArticleCodeToPosts([string]$path) {
 Get-ChildItem $path -Recurse -File | Where-Object Extension -in '.php','.xml','.ini','.js','.css','.json','.sql' | ForEach-Object {
  $s=[IO.File]::ReadAllText($_.FullName)
  $s=$s.Replace('ARTICLES','POSTS').Replace('ARTICLE','POST').Replace('Articles','Posts').Replace('Article','Post').Replace('articles','posts').Replace('article','post')
  [IO.File]::WriteAllText($_.FullName,$s,[Text.UTF8Encoding]::new($false))
 }
 Get-ChildItem $path -Recurse -Force | Sort-Object {$_.FullName.Length} -Descending | ForEach-Object {
  $new=$_.Name.Replace('ARTICLES','POSTS').Replace('ARTICLE','POST').Replace('Articles','Posts').Replace('Article','Post').Replace('articles','posts').Replace('article','post')
  if($new -ne $_.Name){Rename-Item -LiteralPath $_.FullName -NewName $new}
 }
}

function Convert-LegacyContentStorage([string]$path) {
 Get-ChildItem $path -Recurse -File | Where-Object Extension -in '.php','.xml','.ini','.js','.css','.json','.sql' | ForEach-Object {
  $s=[IO.File]::ReadAllText($_.FullName)
  $s=$s.Replace('POSTTEXT','POST_CONTENT').Replace('Posttext','Post Content').Replace('posttext','post_content')
  $s=$s.Replace('INTROTEXT','SUMMARY').Replace('Introtext','Summary').Replace('introtext','summary')
  $s=$s.Replace('FULLTEXT','BODY').Replace('Fulltext','Body').Replace('fulltext','body')
  $s=$s.Replace('->images','->media').Replace("['images']","['media']").Replace('a.images','a.media')
  $s=$s.Replace('->attribs','->options').Replace("['attribs']","['options']").Replace('a.attribs','a.options')
  $s=$s.Replace('image_body','featured_image')
  [IO.File]::WriteAllText($_.FullName,$s,[Text.UTF8Encoding]::new($false))
 }
}

$coreButtonGuard = @"
        // Article family isolation: custom components provide their own cloned editor buttons.
        if (\in_array(`$this->getApplication()->getInput()->getCmd('option'), ['com_blog', 'com_codex', 'com_academy'], true)) {
            return;
        }
"@

foreach($c in $components){
 $name=$c.n; $title=$c.t; $upper=$name.ToUpperInvariant()
 $pluginsDist="$SourceRoot/dist/$name/plugins"
 $outDist="$SourceRoot/dist/$name/dist"
 New-Item -ItemType Directory $pluginsDist,$outDist -Force | Out-Null
 $root="$SourceRoot/dist/$name/build/plugins"
 if(Test-Path $root){Remove-Item $root -Recurse -Force}
 New-Item -ItemType Directory $root -Force | Out-Null
 $files=@()

 foreach($p in $ports){
  $sg=$p[0]; $se=$p[1]; $el=$p[2]; $class=$p[3]
  $targetClass=if($el -eq 'webservices'){'WebServices'}else{(Get-Culture).TextInfo.ToTitleCase($el)}
  $sgTitle=switch($sg){
   'webservices' {'WebServices'}
   'editors-xtd' {'EditorsXtd'}
   'actionlog' {'Actionlog'}
   default {(Get-Culture).TextInfo.ToTitleCase($sg)}
  }
  $target="$pluginsDist/$el"
  if(Test-Path $target){Remove-Item $target -Recurse -Force}
  New-Item -ItemType Directory $target -Force | Out-Null
  Copy-Item "$SourceRoot/plugins/$sg/$se/*" $target -Recurse
  New-Item -ItemType Directory "$target/language/en-GB" -Force | Out-Null
  foreach($suffix in '.ini','.sys.ini'){
   $lang="$SourceRoot/administrator/language/en-GB/plg_${sg}_${se}${suffix}"
   if(Test-Path $lang){Copy-Item $lang "$target/language/en-GB/plg_${name}_${el}${suffix}"}
  }
  Rename-Item "$target/$se.xml" "$el.xml"
  $pairs=@(
   @('image_intro_alt_empty','featured_image_alt_empty'),@('image_intro_caption','featured_image_caption'),
   @('image_intro_alt','featured_image_alt'),@('image_intro','featured_image'),
   @('float_intro','featured_image_class'),@('link_intro_image','link_featured_image'),@('intro_image','featured_image'),
   @('#__contentitem_tag_map','__TAGMAP__'),@('com_contenthistory','__HISTORY__'),@('COM_CONTENTHISTORY','__HISTORYLANG__'),
   @("Joomla\Plugin\${sgTitle}\${class}","Joomla\Plugin\${title}\${targetClass}"),
   @("plg_${sg}_${se}","plg_${name}_${el}"),@("PLG_$($sg.ToUpperInvariant())_$($se.ToUpperInvariant())","PLG_${upper}_$($el.ToUpperInvariant())"),
   @("plugin=`"$se`"","plugin=`"$el`""),@("PluginHelper::getPlugin('$sg', '$se')","PluginHelper::getPlugin('$name', '$el')"),
   @("PluginHelper::getLayoutPath('$sg', '$se'","PluginHelper::getLayoutPath('$name', '$el'"),
   @('v1/content',"v1/$name"),@('v1/fields/groups/content',"v1/fields/groups/$name"),@('v1/fields/content',"v1/fields/$name"),
   @('#__content_rating',"#__${name}_rating"),@('#__content_frontpage',"#__${name}_frontpage"),@('#__categories',"#__${name}_categories"),@('#__content',"#__${name}"),
   @('com_content',"com_$name"),@('COM_CONTENT',"COM_$upper"),@('Joomla\Component\Content',"Joomla\Component\$title"),
   @('__TAGMAP__',"#__${name}_tag_map"),@('__HISTORY__','com_contenthistory'),@('__HISTORYLANG__','COM_CONTENTHISTORY')
  )
  if($el -eq 'finder'){$pairs+=@(
   @("protected `$context = 'Content';","protected `$context = '$title';"),
   @("protected `$type_title = 'Article';","protected `$type_title = '$title Article';"),
   @("`$taxonomies = `$this->params->get('taxonomies', ['type', 'author', 'category', 'language']);","`$taxonomies = `$this->params->get('taxonomies', ['type', 'author', 'category', 'language']);`r`n        if (is_string(`$taxonomies)) { `$taxonomies = array_filter(array_map('trim', explode(',', `$taxonomies))); }")
  )}
  if($sg -eq 'editors-xtd'){$pairs+=@(
   @($coreButtonGuard.Replace("`n", "`r`n"),''), @($coreButtonGuard,''),
   @("return ['onEditorButtonsSetup' => 'onEditorButtonsSetup'];","return ['onEditorButtonsSetup' => ['onEditorButtonsSetup', -100]];"),
   @("'editor-button.' . `$this->_name","'editor-button.$se'"),
   @("new Button(`r`n            `$this->_name,","new Button(`r`n            '$se',"),
   @("new Button(`n            `$this->_name,","new Button(`n            '$se',"),
   @("new Button(`r`n                `$this->_name,","new Button(`r`n                '$se',"),
   @("new Button(`n                `$this->_name,","new Button(`n                '$se',")
  )}
  if($el -eq 'imagebutton'){
   $pairs+=,@("plg_${name}_imagebutton/button-image.js",'plg_editors-xtd_image/button-image.js')
  }
  Rewrite $target $pairs
  Convert-ArticleCodeToPosts $target
  Convert-LegacyContentStorage $target
  Rewrite $target @(
   @("'com_categories.category'", "'com_${name}.category'"),
   @("'com_categories'", "'com_${name}'"),
   @('com_categories', "com_${name}")
  )
  # Native categories do not expose Joomla's com_categories CRUD API.  Do not
  # register a route that hands requests back to the shared component.
  if ($el -eq 'webservices') {
   $apiPath = Join-Path $target 'src/Extension/Content.php'
   $apiText = [IO.File]::ReadAllText($apiPath)
   $apiText = [regex]::Replace($apiText, "(?s)\s*`$router->createCRUDRoutes\(\s*'v1/$name/categories'.*?\[\s*'component'\s*=>\s*'com_$name'.*?\);", '')
   [IO.File]::WriteAllText($apiPath, $apiText, [Text.UTF8Encoding]::new($false))
  }
  if ($el -eq 'finder') {
   $finderPath = Join-Path $target 'src/Extension/Content.php'
   $finderText = [IO.File]::ReadAllText($finderPath)
   $nativeTags = @'
        // Index native component tags with their visibility restrictions.
        $tagDb = $this->getDatabase();
        $nativeTags = $tagDb->setQuery('SELECT t.title,t.published,t.access,t.language FROM #__FAMILY_tags t INNER JOIN #__FAMILY_tag_map m ON m.tag_id=t.id WHERE m.type_alias=' . $tagDb->quote('com_FAMILY.post') . ' AND m.content_item_id=' . (int) $item->id)->loadObjectList();
        foreach ($nativeTags as $tag) {
            $item->addTaxonomy('Tag', $tag->title, (int) $tag->published, (int) $tag->access, $tag->language);
        }

'@
   $finderText = $finderText.Replace('        // Get content extras.', $nativeTags.Replace('FAMILY', $name) + '        // Get content extras.')
   [IO.File]::WriteAllText($finderPath, $finderText, [Text.UTF8Encoding]::new($false))
  }
  [xml]$xml=Get-Content "$target/$el.xml" -Raw
  $xml.extension.SetAttribute('group',$name)
  $nameNode=$xml.extension.SelectSingleNode('name')
  if($null -eq $nameNode){$nameNode=$xml.CreateElement('name');[void]$xml.extension.AppendChild($nameNode)}
  $nameNode.InnerText="plg_${name}_${el}"
  $descriptionNode=$xml.extension.SelectSingleNode('description')
  if($null -eq $descriptionNode){$descriptionNode=$xml.CreateElement('description');[void]$xml.extension.AppendChild($descriptionNode)}
  $descriptionNode.InnerText="PLG_${upper}_$($el.ToUpperInvariant())_XML_DESCRIPTION"
  $xml.Save("$target/$el.xml")
  $zip="plg_${name}_${el}.zip"; Compress-Archive "$target/*" "$outDist/$zip" -Force; Copy-Item "$outDist/$zip" "$root/$zip" -Force
  $files+="    <file type=`"plugin`" id=`"$el`" group=`"$name`">$zip</file>"
 }

 $target="$pluginsDist/video"; if(Test-Path $target){Remove-Item $target -Recurse -Force}; New-Item -ItemType Directory $target -Force|Out-Null
 Copy-Item "$SourceRoot/dist/plugins/content/video/*" $target -Recurse
 Copy-Item "$SourceRoot/dist/plugins/editors-xtd/video/media" "$target/media" -Recurse
 Rewrite $target @(
  @('Joomla\Plugin\Content\Video',"Joomla\Plugin\${title}\Video"),@("PluginHelper::getPlugin('content', 'video')","PluginHelper::getPlugin('$name', 'video')"),
  @('plg_content_video',"plg_${name}_video"),@('PLG_CONTENT_VIDEO',"PLG_${upper}_VIDEO"),
  @("'insert-video'","'insert-${name}-video'"),@("registerAction('insert-video'","registerAction('insert-${name}-video'")
 )
 Add-Content "$target/language/en-GB/plg_${name}_video.ini" "`r`nPLG_${upper}_VIDEO_BUTTON=`"Video`""
 Convert-ArticleCodeToPosts $target
 Convert-LegacyContentStorage $target
 [xml]$vx=Get-Content "$target/video.xml" -Raw; $vx.extension.group=$name; $vx.extension.name="plg_${name}_video"; $vx.extension.version='1.1.0'
 $vx.extension.version='1.1.1';$media=$vx.CreateElement('media');$media.SetAttribute('destination',"plg_${name}_video");$media.SetAttribute('folder','media');$folder=$vx.CreateElement('folder');$folder.InnerText='js';[void]$media.AppendChild($folder);[void]$vx.extension.AppendChild($media);$vx.Save("$target/video.xml")
 $zip="plg_${name}_video.zip";Compress-Archive "$target/*" "$outDist/$zip" -Force;Copy-Item "$outDist/$zip" "$root/$zip" -Force;$files+="    <file type=`"plugin`" id=`"video`" group=`"$name`">$zip</file>"

 $target="$pluginsDist/blocks"; if(Test-Path $target){Remove-Item $target -Recurse -Force}; New-Item -ItemType Directory $target -Force|Out-Null
 Copy-Item "$SourceRoot/extensions/article-blocks/tinymce-blocks/*" $target -Recurse
 Rewrite $target @(
  @('Joomla\Plugin\ArticleFamily\Blocks',"Joomla\Plugin\${title}\Blocks"),
  @("PluginHelper::getPlugin('articlefamily', 'blocks')","PluginHelper::getPlugin('$name', 'blocks')"),
  @("private const FAMILY = 'articlefamily';","private const FAMILY = '$name';"),
  @('plg_articlefamily_blocks',"plg_${name}_blocks"),
  @('articlefamily articles',"$name articles"),
  @('group="articlefamily"',"group=`"$name`"")
 )
 Convert-ArticleCodeToPosts $target
 Convert-LegacyContentStorage $target
 $zip="plg_${name}_blocks.zip"; Compress-Archive "$target/*" "$outDist/$zip" -Force; Copy-Item "$outDist/$zip" "$root/$zip" -Force
 $files+="    <file type=`"plugin`" id=`"blocks`" group=`"$name`">$zip</file>"

 # NOTE: the mailqueue (task), branding (system), and loader (system) plugins are added
 # to this package's <files> list and the package is zipped to dist/plugins.zip by
 # build-family-installers.ps1, once those plugins (built by other scripts) exist.
 $manifest="<?xml version=`"1.0`" encoding=`"UTF-8`"?>`r`n<extension type=`"package`" method=`"upgrade`"><name>pkg_${name}_integrations</name><packagename>${name}_integrations</packagename><version>3.0.0</version><creationDate>2026-09</creationDate><author>Local Joomla Development</author><license>GNU General Public License version 2 or later</license><description>$title isolated post integration plugins</description><files>`r`n$($files -join "`r`n")`r`n</files></extension>"
 [IO.File]::WriteAllText("$root/pkg_${name}_integrations.xml",$manifest,[Text.UTF8Encoding]::new($false))
}
