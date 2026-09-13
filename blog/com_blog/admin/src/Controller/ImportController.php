<?php

namespace Joomla\Component\Blog\Administrator\Controller;

defined('_JEXEC') or die;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Component\Blog\Administrator\Helper\TagsHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Blog\Administrator\Table\PostTable;
use Joomla\Database\DatabaseInterface;
use Joomla\Http\HttpFactory;

final class ImportController extends BaseController
{
    public function run(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.create', 'com_blog')) {
            throw new \RuntimeException('Not authorised', 403);
        }$files = (array)$app->getInput()->files->get('files', [], 'array');
        $catid = $app->getInput()->post->getInt('catid');
        $forced = $app->getInput()->post->getCmd('state', 'source');
        $duplicate = $app->getInput()->post->getCmd('duplicates', 'skip');
        $preview = $app->getInput()->post->getInt('preview') === 1;
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $batchId = 0;
        if (!$preview) {
            $source = implode(', ', array_map(static fn ($file) => basename((string)($file['name'] ?? 'file')), $files));
            $batch = (object)['source' => mb_substr($source, 0, 255),'created' => Factory::getDate()->toSql(),'created_by' => (int)$app->getIdentity()->id,'imported_count' => 0];
            $db->insertObject('#__blog_import_batches', $batch, 'id');
            $batchId = (int)$batch->id;
        }$added = 0;
        $skipped = 0;
        $failed = 0;
        $titles = [];
        foreach ($files as $file) {
            try {
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    throw new \RuntimeException('Upload failed');
                }$extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
                $posts = in_array($extension, ['xml','wxr'], true) ? $this->wordpress((string)$file['tmp_name']) : [$this->markdown((string)$file['tmp_name'], (string)$file['name'])];
                foreach ($posts as $post) {
                    if ($forced !== 'source') {
                        $post['state'] = (int)$forced;
                    }if ($preview) {
                        $alias = ApplicationHelper::stringURLSafe($post['alias'] ?: $post['title']);
                        $exists = (int)$db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__blog')->where('alias='.$db->quote($alias)))->loadResult();
                        if ($exists && $duplicate === 'skip') {
                            $skipped++;
                            continue;
                        }$added++;
                        if (count($titles) < 20) {
                            $titles[] = $post['title'];
                        }continue;
                    }$postId = $this->store($post, $catid, $duplicate);
                    if ($postId) {
                        $importItem = (object)['batch_id' => $batchId,'post_id' => $postId];
                        $db->insertObject('#__blog_import_items', $importItem);
                        $added++;
                    } else {
                        $skipped++;
                    }
                }
            } catch (\Throwable $error) {
                $failed++;
                $app->enqueueMessage(($file['name'] ?? 'File').': '.$error->getMessage(), 'warning');
            }
        }if ($preview) {
            $app->enqueueMessage('Preview: '.$added.' post(s) ready; '.$skipped.' duplicate(s) skipped; '.$failed.' file error(s).'.($titles ? ' Posts: '.implode(', ', $titles) : ''));
        } else {
            $db->setQuery($db->createQuery()->update('#__blog_import_batches')->set('imported_count='.$added)->where('id='.$batchId))->execute();
            $app->enqueueMessage("Imported $added post(s); skipped $skipped; failed $failed. Batch #$batchId can be rolled back below.");
        }$app->redirect(Route::_('index.php?option=com_blog&view=import', false));
    }
    public function database(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.create', 'com_blog')) {
            throw new \RuntimeException('Not authorised', 403);
        }$source = $app->getInput()->post->getCmd('source');
        $catid = $app->getInput()->post->getInt('catid');
        $forced = $app->getInput()->post->getCmd('state', 'source');
        $duplicate = $app->getInput()->post->getCmd('duplicates', 'skip');
        $preview = $app->getInput()->post->getInt('preview') === 1;
        if (!$catid) {
            throw new \RuntimeException('Select a destination category.', 400);
        }$labels = ['joomla' => 'Joomla Posts','easyblog' => 'EasyBlog','k2' => 'K2','rsblog' => 'RSBlog'];
        if (!isset($labels[$source])) {
            throw new \RuntimeException('Select a supported source.', 400);
        }$db = Factory::getContainer()->get(DatabaseInterface::class);
        $posts = $this->databasePosts($source);
        $batchId = 0;
        if (!$preview) {
            $batch = (object)['source' => $labels[$source].' database migration','created' => Factory::getDate()->toSql(),'created_by' => (int)$app->getIdentity()->id,'imported_count' => 0];
            $db->insertObject('#__blog_import_batches', $batch, 'id');
            $batchId = (int)$batch->id;
        }$added = 0;
        $skipped = 0;
        $failed = 0;
        $titles = [];
        foreach ($posts as $post) {
            try {
                if ($forced !== 'source') {
                    $post['state'] = (int)$forced;
                }if ($preview) {
                    $alias = ApplicationHelper::stringURLSafe($post['alias'] ?: $post['title']);
                    $exists = (int)$db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__blog')->where('alias='.$db->quote($alias)))->loadResult();
                    if ($exists && $duplicate === 'skip') {
                        $skipped++;
                        continue;
                    }$added++;
                    if (count($titles) < 20) {
                        $titles[] = $post['title'];
                    }continue;
                }$postId = $this->store($post, $catid, $duplicate);
                if (!$postId) {
                    $skipped++;
                    continue;
                }$item = (object)['batch_id' => $batchId,'post_id' => $postId];
                $db->insertObject('#__blog_import_items', $item);
                $added++;
            } catch (\Throwable $error) {
                $failed++;
                $app->enqueueMessage(($post['title'] ?: 'Source item').': '.$error->getMessage(), 'warning');
            }
        }if ($preview) {
            $app->enqueueMessage('Preview: '.$added.' post(s) ready; '.$skipped.' duplicate(s) skipped; '.$failed.' item error(s).'.($titles ? ' Posts: '.implode(', ', $titles) : ''));
        } else {
            $db->setQuery($db->createQuery()->update('#__blog_import_batches')->set('imported_count='.$added)->where('id='.$batchId))->execute();
            $app->enqueueMessage("Imported $added post(s) from {$labels[$source]}; skipped $skipped; failed $failed. Batch #$batchId can be rolled back below.");
        }$app->redirect(Route::_('index.php?option=com_blog&view=import', false));
    }
    private function databasePosts(string $source): array
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $candidates = ['joomla' => ['content'],'easyblog' => ['easyblog_post','easyblog_posts'],'k2' => ['k2_items'],'rsblog' => ['rsblog_posts']][$source] ?? [];
        $tables = array_map('strtolower', $db->getTableList());
        $prefix = strtolower($db->getPrefix());
        $table = '';
        foreach ($candidates as $candidate) {
            if (in_array($prefix.$candidate, $tables, true)) {
                $table = $candidate;
                break;
            }
        }
        if ($table === '') {
            throw new \RuntimeException('The selected extension database table is not installed.');
        }
        $rows = $db->setQuery('SELECT * FROM '.$db->quoteName('#__'.$table).' ORDER BY '.$db->quoteName('id'))->loadAssocList();
        $out = [];
        foreach ($rows as $row) {
            $title = $this->first($row, ['title','post_title','name']);
            if ($title === '') {
                continue;
            }
            $intro = $this->first($row, ['summary','intro','excerpt','post_intro','summary']);
            $full = $this->first($row, ['body','content','post_content','text','description']);
            if ($intro === '') {
                $intro = $full;
                $full = '';
            }
            $text = $intro . ($full !== '' ? '<hr id="system-readmore">' . $full : '');
            $category = $this->sourceCategory($source, (int)$this->first($row, ['catid','category_id','categoryid']));
            $out[] = [
                'title' => $title,
                'alias' => $this->first($row, ['alias','permalink','slug']),
                'text' => $text,
                'created' => $this->first($row, ['created','created_date','creation_date','publish_up','date_created']) ?: null,
                'state' => $this->sourceState($source, $row),
                'categories' => $category !== '' ? [$category] : [],
                'tags' => $this->listValue($this->first($row, ['tags','keywords','metakey'])),
            ];
        }
        return $out;
    }
    private function first(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null) {
                return trim((string)$row[$key]);
            }
        }return '';
    }
    private function listValue(string $value): array
    {
        if ($value === '') {
            return[];
        }$decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $value = implode(',', array_map(static fn ($item) => is_scalar($item) ? (string)$item : '', $decoded));
        }return array_values(array_unique(array_filter(array_map('trim', preg_split('/[,;]+/', $value)))));
    }
    private function sourceState(string $source, array $row): int
    {
        if ($source === 'k2' && !empty($row['trash'])) {
            return -2;
        }$value = strtolower($this->first($row, ['state','published','status','publish']));
        if (in_array($value, ['1','publish','published','active','enabled'], true)) {
            return 1;
        }if (in_array($value, ['2','archive','archived'], true)) {
            return 2;
        }if (in_array($value, ['-2','trash','trashed'], true)) {
            return -2;
        }if (in_array($value, ['-3','pending','review'], true)) {
            return -3;
        }return 0;
    }
    private function sourceCategory(string $source, int $id): string
    {
        if (!$id) {
            return'';
        }$db = Factory::getContainer()->get(DatabaseInterface::class);
        $candidates = ['joomla' => ['categories'],'easyblog' => ['easyblog_category','easyblog_categories'],'k2' => ['k2_categories'],'rsblog' => ['rsblog_categories']][$source] ?? [];
        $tables = array_map('strtolower', $db->getTableList());
        $prefix = strtolower($db->getPrefix());
        foreach ($candidates as $table) {
            if (!in_array($prefix.$table, $tables, true)) {
                continue;
            }$columns = array_change_key_case($db->getTableColumns('#__'.$table), CASE_LOWER);
            $titleColumn = isset($columns['title']) ? 'title' : (isset($columns['name']) ? 'name' : '');
            if ($titleColumn !== '') {
                return(string)$db->setQuery($db->createQuery()->select($db->quoteName($titleColumn))->from($db->quoteName('#__'.$table))->where($db->quoteName('id').'='.(int)$id))->loadResult();
            }
        }return'';
    }
    private function wordpress(string $path): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
        if (!$xml) {
            throw new \RuntimeException('Invalid XML file.');
        }
        if ($xml->getName() === 'post-export' && (string)$xml['format'] === 'joomla-posts') {
            return $this->nativeXml($xml);
        }
        if ($xml->getName() !== 'rss' || !isset($xml->channel)) {
            throw new \RuntimeException('The XML file is not a supported Posts or WordPress export.');
        }
        $out = [];
        foreach ($xml->channel->item as $item) {
            $wp = $item->children('http://wordpress.org/export/1.2/');
            if ((string)$wp->post_type !== 'post') {
                continue;
            }
            $content = $item->children('http://purl.org/rss/1.0/modules/content/');
            $status = (string)$wp->status;
            $categories = [];
            $tags = [];
            foreach ($item->category as $term) {
                $domain = (string)$term['domain'];
                $value = trim((string)$term);
                if ($domain === 'category' && $value !== '') {
                    $categories[] = $value;
                }
                if ($domain === 'post_tag' && $value !== '') {
                    $tags[] = $value;
                }
            }
            $out[] = ['title' => (string)$item->title,'alias' => (string)$wp->post_name,'text' => (string)$content->encoded,'created' => (string)$wp->post_date_gmt ?: null,'state' => $status === 'publish' ? 1 : ($status === 'pending' ? -3 : 0),'categories' => array_values(array_unique($categories)),'tags' => array_values(array_unique($tags))];
        }
        return $out;
    }
    private function nativeXml(\SimpleXMLElement $xml): array
    {
        $media = [];
        $preview = Factory::getApplication()->getInput()->post->getInt('preview') === 1;
        foreach ($xml->media->file ?? [] as $file) {
            $original = trim((string)$file['path']);
            $encoded = preg_replace('/\s+/', '', (string)$file);
            if ($original === '' || $encoded === '') {
                continue;
            }
            $binary = base64_decode($encoded, true);
            if ($binary === false || strlen($binary) > 52428800) {
                continue;
            }
            $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg','jpeg','png','gif','webp','avif','svg','mp3','mp4','webm','ogg','wav','pdf','doc','docx','zip'], true)) {
                continue;
            }
            $relative = 'images/blog-imports/package/'.hash('sha256', $binary).'.'.$extension;
            if (!$preview) {
                $absolute = JPATH_ROOT.'/'.$relative;
                $folder = dirname($absolute);
                if (!Folder::exists($folder)) {
                    Folder::create($folder);
                }if (!File::exists($absolute)) {
                    File::write($absolute, $binary);
                }
            }
            $media[$original] = $relative;
            $media[ltrim($original, '/')] = $relative;
        }
        $out = [];
        foreach ($xml->posts->post ?? [] as $node) {
            $text = (string)$node->content;
            foreach ($media as $old => $new) {
                $text = str_replace([$old,htmlspecialchars($old, ENT_QUOTES, 'UTF-8')], [$new,htmlspecialchars($new, ENT_QUOTES, 'UTF-8')], $text);
            }
            $categories = [];
            $tags = [];
            foreach ($node->categories->category ?? [] as $value) {
                if (trim((string)$value) !== '') {
                    $categories[] = trim((string)$value);
                }
            }
            foreach ($node->tags->tag ?? [] as $value) {
                if (trim((string)$value) !== '') {
                    $tags[] = trim((string)$value);
                }
            }
            $out[] = ['title' => (string)$node->title,'alias' => (string)$node->alias,'text' => $text,'created' => (string)$node->created ?: null,'state' => (int)$node->state,'categories' => $categories,'tags' => $tags];
        }
        return $out;
    }
    private function markdown(string $path, string $name): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException('Could not read Markdown file.');
        }$meta = [];
        if (preg_match('/\A---\s*\R(.*?)\R---\s*\R/s', $raw, $m)) {
            foreach (preg_split('/\R/', $m[1]) as $line) {
                if (str_contains($line, ':')) {
                    [$k,$v] = array_map('trim', explode(':', $line, 2));
                    $meta[strtolower($k)] = trim($v, " \t\"'");
                }
            }$raw = substr($raw, strlen($m[0]));
        }$list = static function (string $value): array {
            $value = trim($value, " []");
            if ($value === '') {
                return[];
            }return array_values(array_filter(array_map(static fn ($item) => trim($item, " \t\"'"), explode(',', $value))));
        };
        $title = $meta['title'] ?? pathinfo($name, PATHINFO_FILENAME);
        return ['title' => $title,'alias' => $meta['slug'] ?? $meta['alias'] ?? '','text' => $this->markdownHtml($raw),'created' => $meta['date'] ?? $meta['created'] ?? null,'state' => in_array(strtolower($meta['status'] ?? ''), ['publish','published'], true) ? 1 : (strtolower($meta['status'] ?? '') === 'pending' ? -3 : 0),'categories' => $list($meta['categories'] ?? $meta['category'] ?? ''),'tags' => $list($meta['tags'] ?? '')];
    }
    private function markdownHtml(string $text): string
    {
        $inline = static function (string $value): string {
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $value = preg_replace('/!\[([^]]*)\]\((https?:\/\/[^ )]+)\)/', '<img src="$2" alt="$1" loading="lazy">', $value);
            $value = preg_replace('/\[([^]]+)\]\((https?:\/\/[^ )]+)\)/', '<a href="$2">$1</a>', $value);
            $value = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $value);
            $value = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $value);
            $value = preg_replace('/`([^`]+)`/', '<code>$1</code>', $value);
            return $value;
        };
        $lines = preg_split('/\R/', $text);
        $html = [];
        $paragraph = [];
        $list = [];
        $code = [];
        $inCode = false;
        $flush = function () use (&$html, &$paragraph, &$list, $inline) {
            if ($paragraph) {
                $html[] = '<p>'.$inline(implode(' ', $paragraph)).'</p>';
                $paragraph = [];
            }if ($list) {
                $html[] = '<ul><li>'.implode('</li><li>', array_map($inline, $list)).'</li></ul>';
                $list = [];
            }
        };
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '```')) {
                if ($inCode) {
                    $html[] = '<pre><code>'.htmlspecialchars(implode("\n", $code), ENT_QUOTES, 'UTF-8').'</code></pre>';
                    $code = [];
                    $inCode = false;
                } else {
                    $flush();
                    $inCode = true;
                }continue;
            }if ($inCode) {
                $code[] = $line;
                continue;
            }if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
                $flush();
                $level = strlen($m[1]);
                $html[] = "<h$level>".$inline($m[2])."</h$level>";
            } elseif (preg_match('/^[-*+]\s+(.+)$/', $line, $m)) {
                $paragraph && $flush();
                $list[] = $m[1];
            } elseif (preg_match('/^>\s?(.*)$/', $line, $m)) {
                $flush();
                $html[] = '<blockquote>'.$inline($m[1]).'</blockquote>';
            } elseif (trim($line) === '') {
                $flush();
            } else {
                $list && $flush();
                $paragraph[] = trim($line);
            }
        }$flush();
        if ($inCode) {
            $html[] = '<pre><code>'.htmlspecialchars(implode("\n", $code), ENT_QUOTES, 'UTF-8').'</code></pre>';
        }return implode("\n", $html);
    }
    private function store(array $post, int $catid, string $duplicates): int
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        if (Factory::getApplication()->getInput()->post->getInt('download_media')) {
            $post['text'] = $this->downloadImages((string)$post['text']);
        }$alias = ApplicationHelper::stringURLSafe($post['alias'] ?: $post['title']);
        $exists = (int)$db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__blog')->where('alias='.$db->quote($alias)))->loadResult();
        if ($exists && $duplicates === 'skip') {
            return 0;
        }if ($exists) {
            $alias .= '-'.substr(bin2hex(random_bytes(4)), 0, 8);
        }$targetCatid = $catid;
        foreach ((array)($post['categories'] ?? []) as $category) {
            $match = (int)$db->setQuery($db->createQuery()->select('id')->from('#__blog_categories')->where('LOWER(title)='.$db->quote(mb_strtolower($category))))->loadResult();
            if ($match) {
                $targetCatid = $match;
                break;
            }
        }$now = Factory::getDate()->toSql();
        $table = new PostTable($db);
        $tags = array_values(array_filter(array_map('trim', (array)($post['tags'] ?? []))));
        $table->bind(['title' => $post['title'] ?: 'Untitled','alias' => $alias,'summary' => $post['text'],'body' => '','state' => (int)$post['state'],'catid' => $targetCatid,'created' => $post['created'] ?: $now,'created_by' => (int)Factory::getApplication()->getIdentity()->id,'modified' => $now,'media' => '{}','options' => '{}','access' => 1,'metadata' => json_encode(['imported_tags' => $tags,'imported_categories' => (array)($post['categories'] ?? [])]),'language' => '*','editor_mode' => 'classic','block_data' => '','tags' => array_map(static fn ($tag) => '#new#'.$tag, $tags)]);
        if (!$table->check() || !$table->store()) {
            throw new \RuntimeException($table->getError());
        }return (int)$table->id;
    }
    private function downloadImages(string $html): string
    {
        return preg_replace_callback('#(<img\b[^>]*\bsrc=["\'])(https?://[^"\']+)(["\'])#i', function (array $match): string {
            try {
                $local = $this->downloadImage(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                return $local ? $match[1].$local.$match[3] : $match[0];
            } catch (\Throwable $error) {
                Factory::getApplication()->enqueueMessage('Image kept remote: '.$error->getMessage(), 'warning');
                return $match[0];
            }
        }, $html);
    }
    private function downloadImage(string $url): ?string
    {
        $parts = parse_url($url);
        $host = strtolower((string)($parts['host'] ?? ''));
        if (!in_array(strtolower((string)($parts['scheme'] ?? '')), ['http','https'], true) || $host === '' || !$this->publicHost($host)) {
            throw new \RuntimeException('unsafe image URL');
        }$response = (new HttpFactory())->getHttp()->get($url, [], 10);
        $status = $response->getStatusCode();
        if ($status !== 200) {
            throw new \RuntimeException('HTTP '.$status);
        }$body = (string)$response->getBody();
        if ($body === '' || strlen($body) > 10485760) {
            throw new \RuntimeException('image is empty or larger than 10 MB');
        }$contentType = '';
        foreach ($response->getHeaders() as $key => $value) {
            if (strtolower((string)$key) === 'content-type') {
                $contentType = strtolower(trim(explode(';', is_array($value) ? reset($value) : $value)[0]));
                break;
            }
        }$extensions = ['image/jpeg' => 'jpg','image/png' => 'png','image/gif' => 'gif','image/webp' => 'webp','image/avif' => 'avif'];
        if (!isset($extensions[$contentType])) {
            throw new \RuntimeException('unsupported image type');
        }$folder = 'images/blog-imports/'.gmdate('Y/m');
        $absolute = JPATH_ROOT.'/'.$folder;
        if (!Folder::exists($absolute) && !Folder::create($absolute)) {
            throw new \RuntimeException('could not create media folder');
        }$filename = hash('sha256', $url).'.'.$extensions[$contentType];
        if (!File::exists($absolute.'/'.$filename) && !File::write($absolute.'/'.$filename, $body)) {
            throw new \RuntimeException('could not save image');
        }return $folder.'/'.$filename;
    }
    private function publicHost(string $host): bool
    {
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : array_values(array_unique(array_merge(gethostbynamel($host) ?: [], array_map(static fn ($row) => $row['ipv6'] ?? '', dns_get_record($host, DNS_AAAA) ?: []))));
        if (!$ips) {
            return false;
        }foreach ($ips as $ip) {
            if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }return true;
    }
    public function export(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_blog')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $state = $app->getInput()->post->getCmd('state', 'all');
        $query = $db->createQuery()->select(['a.*','c.title AS category_title'])->from('#__blog AS a')->join('LEFT', '#__blog_categories AS c ON c.id=a.catid')->order('a.id');
        if ($state !== 'all' && in_array((int)$state, [-2,0,1,2], true)) {
            $query->where('a.state='.(int)$state);
        }
        $posts = $db->setQuery($query)->loadObjectList();
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $root = $document->createElement('post-export');
        $root->setAttribute('format', 'joomla-posts');
        $root->setAttribute('version', '1');
        $root->setAttribute('component', 'blog');
        $root->setAttribute('created', Factory::getDate()->toSql());
        $document->appendChild($root);
        $postsNode = $document->createElement('posts');
        $root->appendChild($postsNode);
        $media = [];
        foreach ($posts as $post) {
            $node = $document->createElement('post');
            $node->setAttribute('source-id', (string)$post->id);
            foreach (['title' => (string)$post->title,'alias' => (string)$post->alias,'state' => (string)$post->state,'created' => (string)$post->created] as $key => $value) {
                $child = $document->createElement($key);
                $child->appendChild($document->createCDATASection($value));
                $node->appendChild($child);
            }$content = (string)$post->summary.((string)$post->body !== '' ? '<hr id="system-readmore">'.(string)$post->body : '');
            $contentNode = $document->createElement('content');
            $contentNode->appendChild($document->createCDATASection($content));
            $node->appendChild($contentNode);
            $categories = $document->createElement('categories');
            if ((string)$post->category_title !== '') {
                $category = $document->createElement('category');
                $category->appendChild($document->createCDATASection((string)$post->category_title));
                $categories->appendChild($category);
            }$node->appendChild($categories);
            $tagsNode = $document->createElement('tags');
            foreach ((new TagsHelper())->getItemTags('com_blog.post', (int)$post->id) as $tag) {
                $tagNode = $document->createElement('tag');
                $tagNode->appendChild($document->createCDATASection((string)$tag->title));
                $tagsNode->appendChild($tagNode);
            }$node->appendChild($tagsNode);
            $postsNode->appendChild($node);
            $this->collectMedia($content, $media);
            $this->collectMedia((string)$post->media, $media);
        }
        $mediaNode = $document->createElement('media');
        $root->appendChild($mediaNode);
        foreach ($media as $relative => $absolute) {
            $binary = file_get_contents($absolute);
            if ($binary === false) {
                continue;
            }$file = $document->createElement('file', base64_encode($binary));
            $file->setAttribute('path', $relative);
            $file->setAttribute('encoding', 'base64');
            $file->setAttribute('size', (string)strlen($binary));
            $mediaNode->appendChild($file);
        }
        $xml = $document->saveXML();
        $app->setHeader('Content-Type', 'application/xml; charset=utf-8', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="blog-posts-'.gmdate('Y-m-d-His').'.xml"', true);
        $app->setHeader('Content-Length', (string)strlen($xml), true);
        $app->sendHeaders();
        echo$xml;
        $app->close();
    }
    private function collectMedia(string $content, array &$media): void
    {
        if ($content === '') {
            return;
        }
        $root = realpath(JPATH_ROOT);
        $basePath = trim((string)Uri::root(true), '/');
        $urls = [];
        preg_match_all('#(?:src|href)=["\']([^"\']+)["\']#i', $content, $attributeMatches);
        $urls = array_merge($urls, $attributeMatches[1]);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            array_walk_recursive($decoded, static function ($value) use (&$urls): void {
                if (is_string($value)) {
                    $urls[] = $value;
                }
            });
        }
        foreach (array_unique($urls) as $url) {
            $original = html_entity_decode((string)$url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $path = rawurldecode((string)(parse_url($original, PHP_URL_PATH) ?? ''));
            $path = ltrim($path, '/');
            if ($basePath !== '' && str_starts_with($path, $basePath.'/')) {
                $path = substr($path, strlen($basePath) + 1);
            }
            if ($path === '' || str_contains($path, '..')) {
                continue;
            }
            $absolute = realpath(JPATH_ROOT.'/'.$path);
            if ($absolute === false || !str_starts_with(strtolower($absolute), strtolower($root.DIRECTORY_SEPARATOR)) || !is_file($absolute) || filesize($absolute) > 52428800) {
                continue;
            }
            $media[$original] = $absolute;
        }
    }
    public function rollback(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.delete', 'com_blog')) {
            throw new \RuntimeException('Not authorised', 403);
        }$batchId = $app->getInput()->post->getInt('batch_id');
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $ids = array_map('intval',$db->setQuery($db->createQuery()->select('post_id')->from('#__blog_import_items')->where('batch_id='.$batchId))->loadColumn());
        $table = new PostTable($db);
        foreach ($ids as $id) {
            $table->delete($id);
        }$db->setQuery($db->createQuery()->delete('#__blog_import_items')->where('batch_id='.$batchId))->execute();
        $db->setQuery($db->createQuery()->delete('#__blog_import_batches')->where('id='.$batchId))->execute();
        $app->enqueueMessage('Rolled back '.count($ids).' imported post(s) from batch #'.$batchId.'.');
        $app->redirect(Route::_('index.php?option=com_blog&view=import',false));
    }
}
