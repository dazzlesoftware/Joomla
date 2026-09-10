<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2005 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Blog\Administrator\Table;
use Joomla\CMS\Table\Table;
use Joomla\Component\Blog\Administrator\Helper\TagsHelper;

use Joomla\CMS\Access\Rules;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;


use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Content table
 *
 * @since  1.5
 */
class NativePostTable extends Table implements CurrentUserInterface
{

    use CurrentUserTrait;

    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    boolean
     * @since  4.0.0
     */
    protected $_supportNullValue = true;
    public $language = '*';
    protected ?array $pendingTagIds = null;

    /**
     * Constructor
     *
     * @param   DatabaseInterface     $db          Database connector object
     * @param   ?DispatcherInterface  $dispatcher  Event dispatcher for this table
     *
     * @since   1.5
     */
    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        $this->typeAlias = 'com_blog.post';

        parent::__construct('#__blog', 'id', $db, $dispatcher);

        // Set the alias since the column is called state
        $this->setColumnAlias('published', 'state');
    }

    /**
     * Method to compute the default name of the asset.
     * The default name is in the form table_name.id
     * where id is the value of the primary key of the table.
     *
     * @return  string
     *
     * @since   1.6
     */
    protected function _getAssetName()
    {
        $k = $this->_tbl_key;

        return 'com_blog.post.' . (int) $this->$k;
    }

    /**
     * Method to return the title to use for the asset table.
     *
     * @return  string
     *
     * @since   1.6
     */
    protected function _getAssetTitle()
    {
        return $this->title;
    }

    /**
     * Method to get the parent asset id for the record
     *
     * @param   ?Table    $table  A Table object (optional) for the asset parent
     * @param   ?integer  $id     The id (optional) of the content.
     *
     * @return  integer
     *
     * @since   1.6
     */
    protected function _getAssetParentId(?Table $table = null, $id = null)
    {
        $assetId = null;

        // This is an article under a category.
        if ($this->catid) {
            $catId = (int) $this->catid;

            // Build the query to get the asset id for the parent category.
            $db    = $this->getDatabase();
            $query = $db->createQuery()
                ->select($db->quoteName('asset_id'))
                ->from($db->quoteName('#__blog_categories'))
                ->where($db->quoteName('id') . ' = :catid')
                ->bind(':catid', $catId, ParameterType::INTEGER);

            // Get the asset id from the database.
            $db->setQuery($query);

            if ($result = $db->loadResult()) {
                $assetId = (int) $result;
            }
        }

        // Return the asset id.
        if ($assetId) {
            return $assetId;
        }

        return parent::_getAssetParentId($table, $id);
    }

    /**
     * Overloaded bind function
     *
     * @param   array  $array   Named array
     * @param   mixed  $ignore  An optional array or space separated list of properties
     *                          to ignore while binding.
     *
     * @return  mixed  Null if operation was satisfactory, otherwise returns an error string
     *
     * @see     Table::bind()
     * @since   1.6
     */
    public function bind($array, $ignore = '')
    {
        if (array_key_exists('catid', (array) $array) && (int) $array['catid'] > 0) {
            $exists = (int) $this->getDatabase()->setQuery('SELECT id FROM #__blog_categories WHERE id=' . (int) $array['catid'])->loadResult();
            if (!$exists) throw new \InvalidArgumentException('Native category does not exist.');
        }
        if (array_key_exists('tags', (array) $array)) {
            $values = (array) $array['tags'];
            $new = trim((string) ($array['new_tag_titles'] ?? ''));
            $create = Factory::getApplication()->getIdentity()->authorise('core.create', 'com_blog');
            if ($new !== '') {
                if (!$create) throw new \RuntimeException('Not authorised to create tags', 403);
                $values = array_merge($values, array_map(static fn($title) => '#new#' . trim($title), explode(',', $new)));
            }
            $this->pendingTagIds = TagsHelper::resolve($values, $create);
            unset($array['tags'], $array['new_tag_titles']);
        }
        // Search for the {readmore} tag and split the text up accordingly.
        if (isset($array['post_content'])) {
            $pattern = '#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i';
            $tagPos  = preg_match($pattern, $array['post_content']);

            if ($tagPos == 0) {
                $this->summary = $array['post_content'];
                $this->body  = '';
            } else {
                [$this->summary, $this->body] = preg_split($pattern, $array['post_content'], 2);
            }
        }

        if (isset($array['options']) && \is_array($array['options'])) {
            $registry         = new Registry($array['options']);
            $array['options'] = (string) $registry;
        }

        if (isset($array['metadata']) && \is_array($array['metadata'])) {
            $registry          = new Registry($array['metadata']);
            $array['metadata'] = (string) $registry;
        }

        // Bind the rules.
        if (isset($array['rules']) && \is_array($array['rules'])) {
            $rules = new Rules($array['rules']);
            $this->setRules($rules);
        }

        return parent::bind($array, $ignore);
    }

    /**
     * Overloaded check function
     *
     * @return  boolean  True on success, false on failure
     *
     * @see     Table::check()
     * @since   1.5
     */
    public function check()
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        if (trim($this->title) == '') {
            $this->setError(Text::_('COM_BLOG_WARNING_PROVIDE_VALID_NAME'));

            return false;
        }

        if (trim($this->alias) == '') {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language);

        if (trim(str_replace('-', '', $this->alias)) == '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        // Check for a valid category.
        if (!$this->catid = (int) $this->catid) {
            $this->setError(Text::_('JLIB_DATABASE_ERROR_CATEGORY_REQUIRED'));

            return false;
        }

        if (trim(str_replace('&nbsp;', '', $this->body)) == '') {
            $this->body = '';
        }

        /**
         * Ensure any new items have compulsory fields set. This is needed for things like
         * frontend editing where we don't show all the fields or using some kind of API
         */
        if (!$this->id) {
            // Images can be an empty json string
            if (!isset($this->media)) {
                $this->media = '{}';
            }

            // URLs can be an empty json string
            if (!isset($this->urls)) {
                $this->urls = '{}';
            }

            // Attributes (article params) can be an empty json string
            if (!isset($this->options)) {
                $this->options = '{}';
            }

            // Metadata can be an empty json string
            if (!isset($this->metadata)) {
                $this->metadata = '{}';
            }

            // Hits must be zero on a new item
            $this->hits = 0;
        }

        // Set publish_up to null if not set
        if (!$this->publish_up) {
            $this->publish_up = null;
        }

        // Set publish_down to null if not set
        if (!$this->publish_down) {
            $this->publish_down = null;
        }

        // Check the publish down date is not earlier than publish up.
        if (!\is_null($this->publish_up) && !\is_null($this->publish_down) && $this->publish_down < $this->publish_up) {
            // Swap the dates.
            $temp               = $this->publish_up;
            $this->publish_up   = $this->publish_down;
            $this->publish_down = $temp;
        }

        // Clean up keywords -- eliminate extra spaces between phrases
        // and cr (\r) and lf (\n) characters from string
        if (!empty($this->metakey)) {
            // Only process if not empty

            // Array of characters to remove
            $badCharacters = ["\n", "\r", "\"", '<', '>'];

            // Remove bad characters
            $afterClean = StringHelper::str_ireplace($badCharacters, '', $this->metakey);

            // Create array using commas as delimiter
            $keys = explode(',', $afterClean);

            $cleanKeys = [];

            foreach ($keys as $key) {
                if (trim($key)) {
                    // Ignore blank keywords
                    $cleanKeys[] = trim($key);
                }
            }

            // Put array back together delimited by ", "
            $this->metakey = implode(', ', $cleanKeys);
        } else {
            $this->metakey = '';
        }

        if ($this->metadesc === null) {
            $this->metadesc = '';
        }

        return true;
    }

    /**
     * Overrides Table::store to set modified data and user id.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean  True on success.
     *
     * @since   1.6
     */
    public function store($updateNulls = true)
    {
        $date = Factory::getDate()->toSql();
        $user = $this->getCurrentUser();

        // Set created date if not set.
        if (!(int) $this->created) {
            $this->created = $date;
        }

        if ($this->id) {
            // Existing item
            $this->modified_by = $user->id;
            $this->modified    = $date;
            if (empty($this->created_by)) {
                $this->created_by = 0;
            }
        } else {
            // Field created_by can be set by the user, so we don't touch it if it's set.
            if (empty($this->created_by)) {
                $this->created_by = $user->id;
            }

            // Set modified to created date if not set
            if (!(int) $this->modified) {
                $this->modified = $this->created;
            }

            // Set modified_by to created_by user if not set
            if (empty($this->modified_by)) {
                $this->modified_by = $this->created_by;
            }
        }

        // Verify that the alias is unique
        $table = new self($this->getDatabase(), $this->getDispatcher());

        if ($table->load(['alias' => $this->alias, 'catid' => $this->catid]) && ($table->id != $this->id || $this->id == 0)) {
            // Is the existing article trashed?
            $this->setError(Text::_('COM_BLOG_ERROR_UNIQUE_ALIAS'));

            if ($table->state === -2) {
                $this->setError(Text::_('COM_BLOG_ERROR_UNIQUE_ALIAS_TRASHED'));
            }

            return false;
        }

        $db = $this->getDatabase();
        $db->transactionStart(true);
        try {
            $result = parent::store($updateNulls);
            if (!$result) { $db->transactionRollback(true); return false; }
            if ($this->pendingTagIds !== null) TagsHelper::assign((int) $this->id, $this->pendingTagIds);
            $this->pendingTagIds = null;
            $db->transactionCommit(true);
            return true;
        } catch (\Throwable $e) { $db->transactionRollback(true); throw $e; }
    }

    /**
     * Get the type alias for UCM features
     *
     * @return  string  The alias as described above
     *
     * @since   4.0.0
     */
    public function getTypeAlias()
    {
        return $this->typeAlias;
    }
    public function load($keys = null, $reset = true)
    {
        $this->pendingTagIds = null;
        $result = parent::load($keys, $reset);
        if ($result && !empty($this->id)) {
            $helper = new TagsHelper();
            $this->pendingTagIds = array_values(array_filter(array_map('intval', explode(',', $helper->getTagIds($this->id)))));
        }
        return $result;
    }

    public function delete($pk = null)
    {
        $id = (int) ($pk ?? $this->id);
        $db = $this->getDatabase();
        $db->transactionStart(true);
        try {
            if (!parent::delete($pk)) { $db->transactionRollback(true); return false; }
            $db->setQuery('DELETE FROM #__blog_tag_map WHERE content_item_id=' . $id . ' AND type_alias=' . $db->quote('com_blog.post'))->execute();
            $db->transactionCommit(true);
            return true;
        } catch (\Throwable $e) { $db->transactionRollback(true); throw $e; }
    }
}
