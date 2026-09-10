<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_academy
 *
 * @copyright   (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */

use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Content Component HTML Helper
 *
 * @since       1.5
 *
 * @deprecated  4.3 will be removed in 7.0
 *              Use the class \Joomla\Component\Academy\Administrator\Service\HTML\Icon instead
 */
abstract class JHtmlIcon
{
    /**
     * Method to generate a link to the create item page for the given category
     *
     * @param   object    $category  The category information
     * @param   Registry  $params    The item parameters
     * @param   array     $attribs   Optional attributes for the link
     * @param   boolean   $legacy    True to use legacy images, false to use icomoon based graphic
     *
     * @return  string  The HTML markup for the create item link
     *
     * @deprecated  4.3 will be removed in 7.0
     *              Use \Joomla\Component\Academy\Administrator\Service\HTML\Icon::create instead
     *              Example:
     *              use Joomla\Component\Academy\Administrator\Service\HTML\Icon;
     *              Factory::getContainer()->get(Registry::class)->register('icon', new Icon());
     *              echo HTMLHelper::_('icon.create', ...);
     */
    public static function create($category, $params, $attribs = [], $legacy = false)
    {
        return self::getIcon()->create($category, $params, $attribs, $legacy);
    }

    /**
     * Display an edit icon for the post.
     *
     * This icon will not display in a popup window, nor if the post is trashed.
     * Edit access checks must be performed in the calling code.
     *
     * @param   object    $post  The post information
     * @param   Registry  $params   The item parameters
     * @param   array     $attribs  Optional attributes for the link
     * @param   boolean   $legacy   True to use legacy images, false to use icomoon based graphic
     *
     * @return  string  The HTML for the post edit icon.
     *
     * @since   1.6
     *
     * @deprecated  4.3 will be removed in 7.0
     *              Use \Joomla\Component\Academy\Administrator\Service\HTML\Icon::edit instead
     *              Example:
     *              use Joomla\Component\Academy\Administrator\Service\HTML\Icon;
     *              Factory::getContainer()->get(Registry::class)->register('icon', new Icon());
     *              echo HTMLHelper::_('icon.edit', ...);
     */
    public static function edit($post, $params, $attribs = [], $legacy = false)
    {
        return self::getIcon()->edit($post, $params, $attribs, $legacy);
    }

    /**
     * Method to generate a popup link to print an post
     *
     * @param   object    $post  The post information
     * @param   Registry  $params   The item parameters
     * @param   array     $attribs  Optional attributes for the link
     * @param   boolean   $legacy   True to use legacy images, false to use icomoon based graphic
     *
     * @return  string  The HTML markup for the popup link
     *
     * @deprecated  4.3 will be removed in 7.0
     *              No longer used, will be removed without replacement
     */
    public static function print_popup($post, $params, $attribs = [], $legacy = false)
    {
        throw new \Exception(Text::_('COM_ACADEMY_ERROR_PRINT_POPUP'));
    }

    /**
     * Method to generate a link to print an post
     *
     * @param   object    $post  Not used
     * @param   Registry  $params   The item parameters
     * @param   array     $attribs  Not used
     * @param   boolean   $legacy   True to use legacy images, false to use icomoon based graphic
     *
     * @return  string  The HTML markup for the popup link
     *
     * @deprecated  4.3 will be removed in 7.0
     *              Use \Joomla\Component\Academy\Administrator\Service\HTML\Icon::print_screen instead
     *              Example:
     *              use Joomla\Component\Academy\Administrator\Service\HTML\Icon;
     *              Factory::getContainer()->get(Registry::class)->register('icon', new Icon());
     *              echo HTMLHelper::_('icon.print_screen', ...);
     */
    public static function print_screen($post, $params, $attribs = [], $legacy = false)
    {
        return self::getIcon()->print_screen($params, $legacy);
    }

    /**
     * Creates an icon instance.
     *
     * @return  \Joomla\Component\Academy\Administrator\Service\HTML\Icon
     *
     * @deprecated  4.3 will be removed in 7.0 without replacement
     */
    private static function getIcon()
    {
        return new \Joomla\Component\Academy\Administrator\Service\HTML\Icon(Joomla\CMS\Factory::getApplication());
    }
}
