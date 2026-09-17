<?php
// Integration regression checks use connection-local temporary tables only.
if (PHP_SAPI !== 'cli') { exit(1); }
define('_JEXEC', 1);
$site = rtrim($argv[1] ?? '', '/\\');
require $site . '/libraries/vendor/autoload.php';
require $site . '/configuration.php';
$config = new JConfig();
$db = (new Joomla\Database\DatabaseFactory())->getDriver('mysqli', ['host'=>$config->host,'user'=>$config->user,'password'=>$config->password,'database'=>$config->db,'prefix'=>'votes_test_'.bin2hex(random_bytes(5)).'_']);
function verify(bool $ok): void { if (!$ok) { throw new RuntimeException('Vote regression failed'); } }
$db->setQuery('CREATE TEMPORARY TABLE #__users (id INT PRIMARY KEY) ENGINE=InnoDB')->execute();
$db->setQuery('INSERT INTO #__users VALUES (71),(72)')->execute();
foreach (['academy','blog','codex'] as $family) {
    require __DIR__ . '/../' . $family . '/com_' . $family . '/admin/src/Service/VotesService.php';
    $db->setQuery("CREATE TEMPORARY TABLE #__{$family} (id INT PRIMARY KEY) ENGINE=InnoDB")->execute();
    $db->setQuery("INSERT INTO #__{$family} VALUES (11),(12)")->execute();
    $db->setQuery("CREATE TEMPORARY TABLE #__{$family}_rating_votes (id BIGINT AUTO_INCREMENT PRIMARY KEY,post_id INT,rating INT,voter_key CHAR(64),created DATETIME,UNIQUE(post_id,voter_key)) ENGINE=InnoDB")->execute();
    $db->setQuery("CREATE TEMPORARY TABLE #__{$family}_rating (content_id INT PRIMARY KEY,rating_sum INT,rating_count INT,lastip VARCHAR(50)) ENGINE=InnoDB")->execute();
    $class = 'Joomla\\Component\\'.ucfirst($family).'\\Administrator\\Service\\VotesService';
    $service = new $class($db);
    $get = static fn($id) => $db->setQuery("SELECT * FROM #__{$family}_rating WHERE content_id=".(int)$id)->loadObject();
    $id = $service->save(0,11,2);
    verify($id > 0 && (int)$get(11)->rating_sum === 2 && (int)$get(11)->rating_count === 1);
    $original = $db->setQuery("SELECT * FROM #__{$family}_rating_votes WHERE id=$id")->loadObject();
    $service->save($id,11,5);
    verify((int)$get(11)->rating_sum === 5);
    $edited = $db->setQuery("SELECT * FROM #__{$family}_rating_votes WHERE id=$id")->loadObject();
    verify($original->voter_key === $edited->voter_key && $original->created === $edited->created);
    $service->save($id,12,4);
    verify($get(11) === null && (int)$get(12)->rating_sum === 4);
    foreach ([[0,11,6],[0,999,3],[999,11,3]] as $bad) {
        try { $service->save(...$bad); throw new RuntimeException('Invalid save accepted'); } catch (InvalidArgumentException $expected) {}
    }
    $row=(object)['post_id'=>11,'rating'=>1,'voter_key'=>$original->voter_key,'created'=>$original->created];
    $db->insertObject('#__'.$family.'_rating_votes',$row);
    try { $service->save($id,11,3); throw new RuntimeException('Duplicate accepted'); } catch (InvalidArgumentException $expected) {}
    verify((int)$get(12)->rating_sum === 4);
    verify($service->delete([$id]) === 1 && $get(12) === null);
    $assigned = $service->save(0,12,3,71);
    $key = static fn($vote) => $db->setQuery("SELECT voter_key FROM #__{$family}_rating_votes WHERE id=".(int)$vote)->loadResult();
    verify($key($assigned) === 'user:71');
    $service->save($assigned,12,5,72);
    verify($key($assigned) === 'user:72' && (int)$get(12)->rating_sum === 5);
    foreach ([[0,12,2,72],[$assigned,12,2,999]] as $bad) {
        try { $service->save(...$bad); throw new RuntimeException('Invalid assignment accepted'); } catch (InvalidArgumentException $expected) {}
    }
    verify($key($assigned) === 'user:72' && (int)$get(12)->rating_sum === 5);
    $service->save($assigned,12,4,0);
    verify(str_starts_with($key($assigned), 'admin:'));
    $guestKey = str_repeat('a',64);
    $db->setQuery("UPDATE #__{$family}_rating_votes SET voter_key=".$db->quote($guestKey)." WHERE id=$assigned")->execute();
    $service->save($assigned,12,2,0);
    verify($key($assigned) === $guestKey);
    $service->save($assigned,12,3,71);
    verify($key($assigned) === 'user:71');
    echo "$family: assignment, reassignment, clearing, guest preservation and duplicate validation passed\n";
    echo "$family: create, edit, move, validation, duplicate rejection and delete passed\n";
}
