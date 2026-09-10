<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.confirmconsent
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Codex\Confirmconsent\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\CheckboxesField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Router\Route;
use Joomla\Component\Codex\Site\Helper\RouteHelper;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Consentbox Field class for the Confirm Consent Plugin.
 *
 * @since  3.9.1
 */
class ConsentBoxField extends CheckboxesField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  3.9.1
     */
    protected $type = 'ConsentBox';

    /**
     * Flag to tell the field to always be in multiple values mode.
     *
     * @var    boolean
     * @since  3.9.1
     */
    protected $forceMultiple = false;

    /**
     * The post ID.
     *
     * @var    integer
     * @since  3.9.1
     */
    protected $postid;

    /**
     * The menu item ID.
     *
     * @var    integer
     * @since  4.0.0
     */
    protected $menuItemId;

    /**
     * Type of the privacy policy.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $privacyType;

    /**
     * Method to set certain otherwise inaccessible properties of the form field object.
     *
     * @param   string  $name   The property name for which to set the value.
     * @param   mixed   $value  The value of the property.
     *
     * @return  void
     *
     * @since   3.9.1
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'postid':
                $this->postid = (int) $value;
                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Method to get certain otherwise inaccessible properties from the form field object.
     *
     * @param   string  $name  The property name for which to get the value.
     *
     * @return  mixed  The property value or null.
     *
     * @since   3.9.1
     */
    public function __get($name)
    {
        if ($name == 'postid') {
            return $this->$name;
        }

        return parent::__get($name);
    }

    /**
     * Method to attach a Form object to the field.
     *
     * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed              $value    The form field value to validate.
     * @param   string             $group    The field name group control value. This acts as an array container for the field.
     *                                       For example if the field has name="foo" and the group value is set to "bar" then the
     *                                       full field name would end up being "bar[foo]".
     *
     * @return  boolean  True on success.
     *
     * @see     \Joomla\CMS\Form\FormField::setup()
     * @since   3.9.1
     */
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        $return = parent::setup($element, $value, $group);

        if ($return) {
            $this->postid   = (int) $this->element['postid'];
            $this->menuItemId  = (int) $this->element['menu_item_id'];
            $this->privacyType = (string) $this->element['privacy_type'];
        }

        return $return;
    }

    /**
     * Method to get the field label markup.
     *
     * @return  string  The field label markup.
     *
     * @since   3.9.1
     */
    protected function getLabel()
    {
        if ($this->hidden) {
            return '';
        }

        $data = $this->getLayoutData();

        // Forcing the Alias field to display the tip below
        $position = $this->element['name'] == 'alias' ? ' data-bs-placement="bottom" ' : '';

        // When we have an post let's add the modal and make the title clickable
        $hasLink = ($data['privacyType'] === 'post' && $data['postid'])
            || ($data['privacyType'] === 'menu_item' && $data['menuItemId']);

        if ($hasLink) {
            $attribs['data-bs-toggle'] = 'modal';

            $data['label'] = HTMLHelper::_(
                'link',
                '#modal-' . $this->id,
                $data['label'],
                $attribs
            );
        }

        // Here mainly for B/C with old layouts. This can be done in the layouts directly
        $extraData = [
            'text'     => $data['label'],
            'for'      => $this->id,
            'classes'  => explode(' ', $data['labelclass']),
            'position' => $position,
        ];

        return $this->getRenderer($this->renderLabelLayout)->render(array_merge($data, $extraData));
    }

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   4.0.0
     */
    protected function getInput()
    {
        $modalHtml  = '';
        $layoutData = $this->getLayoutData();

        $hasLink = ($this->privacyType === 'post' && $this->postid)
            || ($this->privacyType === 'menu_item' && $this->menuItemId);

        if ($hasLink) {
            $modalParams['title']      = $layoutData['label'];
            $modalParams['url']        = ($this->privacyType === 'menu_item') ? $this->getAssignedMenuItemUrl() : $this->getAssignedPostUrl();
            $modalParams['height']     = '100%';
            $modalParams['width']      = '100%';
            $modalParams['bodyHeight'] = 70;
            $modalParams['modalWidth'] = 80;
            $modalHtml                 = HTMLHelper::_('bootstrap.renderModal', 'modal-' . $this->id, $modalParams);
        }

        return $modalHtml . parent::getInput();
    }

    /**
     * Method to get the data to be passed to the layout for rendering.
     *
     * @return  array
     *
     * @since   3.9.1
     */
    protected function getLayoutData()
    {
        $data = parent::getLayoutData();

        $extraData = [
            'postid'   => (int) $this->postid,
            'menuItemId'  => (int) $this->menuItemId,
            'privacyType' => (string) $this->privacyType,
        ];

        return array_merge($data, $extraData);
    }

    /**
     * Return the url of the assigned post based on the current user language
     *
     * @return  string  Returns the link to the post
     *
     * @since   3.9.1
     */
    private function getAssignedPostUrl()
    {
        $db = $this->getDatabase();

        // Get the info from the post
        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'catid', 'language']))
            ->from($db->quoteName('#__codex'))
            ->where($db->quoteName('id') . ' = ' . (int) $this->postid);
        $db->setQuery($query);

        try {
            $post = $db->loadObject();
        } catch (ExecutionFailureException) {
            // Something at the database layer went wrong
            return Route::_(
                'index.php?option=com_codex&view=post&id='
                . $this->postid . '&tmpl=component'
            );
        }

        if (!\is_object($post)) {
            // We have not found the post object lets show a 404 to the user
            return Route::_(
                'index.php?option=com_codex&view=post&id='
                . $this->postid . '&tmpl=component'
            );
        }

        if (!Associations::isEnabled()) {
            return Route::_(
                RouteHelper::getPostRoute(
                    $post->id,
                    $post->catid,
                    $post->language
                ) . '&tmpl=component'
            );
        }

        $associatedPosts = Associations::getAssociations('com_codex', '#__codex', 'com_codex.item', $post->id);
        $currentLang        = Factory::getLanguage()->getTag();

        if (isset($associatedPosts) && $currentLang !== $post->language && \array_key_exists($currentLang, $associatedPosts)) {
            return Route::_(
                RouteHelper::getPostRoute(
                    $associatedPosts[$currentLang]->id,
                    $associatedPosts[$currentLang]->catid,
                    $associatedPosts[$currentLang]->language
                ) . '&tmpl=component'
            );
        }

        // Association is enabled but this post is not associated
        return Route::_(
            'index.php?option=com_codex&view=post&id='
                . $post->id . '&catid=' . $post->catid
                . '&tmpl=component&lang=' . $post->language
        );
    }

    /**
     * Get privacy menu item URL. If the site is a multilingual website and there is associated menu item for the
     * current language, the URL of the associated menu item will be returned.
     *
     * @return  string
     *
     * @since   4.0.0
     */
    private function getAssignedMenuItemUrl()
    {
        $itemId         = $this->menuItemId;
        $languageSuffix = '';

        if ($itemId > 0 && Associations::isEnabled()) {
            $privacyAssociated = Associations::getAssociations('com_menus', '#__menu', 'com_menus.item', $itemId, 'id', '', '');
            $currentLang       = Factory::getLanguage()->getTag();

            if (isset($privacyAssociated[$currentLang])) {
                $itemId = $privacyAssociated[$currentLang]->id;
            }

            if (Multilanguage::isEnabled()) {
                $db    = $this->getDatabase();
                $query = $db->createQuery()
                    ->select($db->quoteName(['id', 'language']))
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('id') . ' = :id')
                    ->bind(':id', $itemId, ParameterType::INTEGER);
                $db->setQuery($query);
                $menuItem = $db->loadObject();

                $languageSuffix = '&lang=' . $menuItem->language;
            }
        }

        return Route::_(
            'index.php?Itemid=' . (int) $itemId . '&tmpl=component' . $languageSuffix
        );
    }
}
