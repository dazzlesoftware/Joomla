<?php
namespace Joomla\Component\Blog\Administrator\Service;
defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;
final class VotesService
{
    public function __construct(private DatabaseInterface $db) {}
    public function save(int $id, int $postId, int $rating, ?int $userId = null): int
    {
        if ($id < 0 || $postId < 1 || $rating < 1 || $rating > 5 || ($userId !== null && $userId < 0)) {
            throw new \InvalidArgumentException('Choose a post and a rating between 1 and 5.');
        }
        $db = $this->db;
        $db->transactionStart();
        try {
            if (!$db->setQuery($db->createQuery()->select('id')->from('#__blog')->where('id=' . $postId))->loadResult()) {
                throw new \InvalidArgumentException('The selected post does not exist.');
            }
            $old = $id ? $db->setQuery($db->createQuery()->select('*')->from('#__blog_rating_votes')->where('id=' . $id))->loadObject() : null;
            if ($id && !$old) { throw new \InvalidArgumentException('The vote no longer exists.'); }
            if ($userId > 0 && !$db->setQuery($db->createQuery()->select('id')->from('#__users')->where('id=' . $userId))->loadResult()) {
                throw new \InvalidArgumentException('The selected user no longer exists.');
            }
            // An omitted assignment preserves the voter. An empty picker preserves guest identities,
            // but removes a registered user assignment when explicitly cleared.
            $voterKey = $old->voter_key ?? ('admin:' . bin2hex(random_bytes(24)));
            if ($userId > 0) {
                $voterKey = 'user:' . $userId;
            } elseif ($userId === 0 && str_starts_with($voterKey, 'user:')) {
                $voterKey = 'admin:' . bin2hex(random_bytes(24));
            }
            $duplicate = $db->setQuery($db->createQuery()->select('id')->from('#__blog_rating_votes')->where('post_id=' . $postId)->where('voter_key=' . $db->quote($voterKey))->where('id!=' . $id))->loadResult();
            if ($duplicate) { throw new \InvalidArgumentException('This voter already has a vote for the selected post.'); }
            if ($old) {
                $row = (object) ['id' => $id, 'post_id' => $postId, 'rating' => $rating, 'voter_key' => $voterKey];
                $db->updateObject('#__blog_rating_votes', $row, 'id');
            } else {
                $row = (object) ['post_id' => $postId, 'rating' => $rating, 'voter_key' => $voterKey, 'created' => gmdate('Y-m-d H:i:s')];
                $db->insertObject('#__blog_rating_votes', $row, 'id');
                $id = (int) $row->id;
            }
            foreach (array_unique([$postId, $old ? (int) $old->post_id : $postId]) as $affected) { $this->recalculate($affected); }
            $db->transactionCommit();
            return $id;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            throw $error;
        }
    }
    private function recalculate(int $postId): void
    {
        $db = $this->db;
        $rating = $db->setQuery($db->createQuery()->select('COALESCE(SUM(rating),0) AS rating_sum, COUNT(*) AS rating_count')->from('#__blog_rating_votes')->where('post_id=' . $postId))->loadObject();
        $db->setQuery($db->createQuery()->delete('#__blog_rating')->where('content_id=' . $postId))->execute();
        if ((int) $rating->rating_count) {
            $row = (object) ['content_id' => $postId, 'rating_sum' => (int) $rating->rating_sum, 'rating_count' => (int) $rating->rating_count, 'lastip' => ''];
            $db->insertObject('#__blog_rating', $row);
        }
    }
    public function delete(array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($id) => $id > 0)));
        if (!$ids) { return 0; }
        $db = $this->db;
        $db->transactionStart();
        try {
            $votes = $db->setQuery($db->createQuery()->select(['id', 'post_id'])->from('#__blog_rating_votes')->whereIn('id', $ids))->loadObjectList();
            if (!$votes) { $db->transactionCommit(); return 0; }
            $db->setQuery($db->createQuery()->delete('#__blog_rating_votes')->whereIn('id', $ids))->execute();
            foreach (array_unique(array_column($votes, 'post_id')) as $postId) {
                $this->recalculate((int) $postId);
            }
            $db->transactionCommit();
            return count($votes);
        } catch (\Throwable $error) {
            $db->transactionRollback();
            throw $error;
        }
    }
}
