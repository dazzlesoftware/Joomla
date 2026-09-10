<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Webservices.content
 *
 * @copyright   (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Codex\WebServices\Extension;

use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\ApiRouter;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Web Services adapter for com_codex.
 *
 * @since  4.0.0
 */
final class Content extends CMSPlugin implements SubscriberInterface
{
    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   5.1.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeApiRoute' => 'onBeforeApiRoute',
        ];
    }

    /**
     * Registers com_codex's API's routes in the application
     *
     * @param   BeforeApiRouteEvent  $event  The event object
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
    {
        $router = $event->getRouter();

        $router->createCRUDRoutes(
            'v1/codex/posts',
            'posts',
            ['component' => 'com_codex']
        );

        $router->createCRUDRoutes(
            'v1/codex/categories',
            'categories',
            ['component' => 'com_codex', 'extension' => 'com_codex']
        );

        $this->createFieldsRoutes($router);

        $this->createContentHistoryRoutes($router);
    }

    /**
     * Create fields routes
     *
     * @param   ApiRouter  &$router  The API Routing object
     *
     * @return  void
     *
     * @since   4.0.0
     */
    private function createFieldsRoutes(&$router): void
    {
        $router->createCRUDRoutes(
            'v1/fields/codex/posts',
            'fields',
            ['component' => 'com_fields', 'context' => 'com_codex.post']
        );

        $router->createCRUDRoutes(
            'v1/fields/codex/categories',
            'fields',
            ['component' => 'com_fields', 'context' => 'com_codex.categories']
        );

        $router->createCRUDRoutes(
            'v1/fields/groups/codex/posts',
            'groups',
            ['component' => 'com_fields', 'context' => 'com_codex.post']
        );

        $router->createCRUDRoutes(
            'v1/fields/groups/codex/categories',
            'groups',
            ['component' => 'com_fields', 'context' => 'com_codex.categories']
        );
    }

    /**
     * Create contenthistory routes
     *
     * @param   ApiRouter  &$router  The API Routing object
     *
     * @return  void
     *
     * @since   4.0.0
     */
    private function createContentHistoryRoutes(&$router): void
    {
        $defaults    = [
            'component'  => 'com_contenthistory',
            'type_alias' => 'com_codex.post',
            'type_id'    => 1,
        ];
        $getDefaults = array_merge(['public' => false], $defaults);

        $routes = [
            new Route(['GET'], 'v1/codex/posts/:id/contenthistory', 'history.displayList', ['id' => '(\d+)'], $getDefaults),
            new Route(['PATCH'], 'v1/codex/posts/:id/contenthistory/keep', 'history.keep', ['id' => '(\d+)'], $defaults),
            new Route(['DELETE'], 'v1/codex/posts/:id/contenthistory', 'history.delete', ['id' => '(\d+)'], $defaults),
        ];

        $router->addRoutes($routes);
    }
}
