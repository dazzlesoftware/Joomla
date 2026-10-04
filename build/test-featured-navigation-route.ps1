param(
 [string]$FeaturedUrl = 'http://localhost/Joomla/index.php?option=com_academy&view=featured&Itemid=9973',
 [string]$PostTitle = 'Audit Post 15',
 [bool]$ExpectedNavigation = $false
)
$ErrorActionPreference = 'Stop'
$page = Invoke-WebRequest $FeaturedUrl
$links = @($page.Links | Where-Object { $_.outerHTML -match [regex]::Escape($PostTitle) })
if (!$links.Count) { throw 'No matching post links in Featured page.' }
$urls = @($links | ForEach-Object { [System.Net.WebUtility]::HtmlDecode($_.href) } | Select-Object -Unique)
foreach ($url in $urls) {
 if ($url -match '&amp;') { throw "Double-escaped route: $url" }
 $absolute = [uri]::new([uri]$FeaturedUrl, $url)
 $post = Invoke-WebRequest $absolute.AbsoluteUri
 $heading = [regex]::Match($post.Content, '<h1\b[^>]*>(.*?)</h1>', 'Singleline').Groups[1].Value
 if ($heading -notmatch [regex]::Escape($PostTitle)) { throw "Link did not open the individual post: $url" }
 $visible = $post.Content -match 'class="pagenavigation"'
 if ($visible -ne $ExpectedNavigation) { throw "Wrong Previous/Next visibility on $url" }
}
Write-Output "Passed: $($links.Count) Featured title/slider/read-more links open the post with expected navigation visibility."
