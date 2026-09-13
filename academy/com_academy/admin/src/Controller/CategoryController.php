<?php
namespace Joomla\Component\Academy\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\Factory; use Joomla\CMS\MVC\Controller\BaseController; use Joomla\CMS\Router\Route; use Joomla\CMS\Session\Session; use Joomla\Component\Academy\Administrator\Helper\CategoriesHelper; use Joomla\Utilities\ArrayHelper;

final class CategoryController extends BaseController
{
    public function add(): void
    {
        Factory::getApplication()->redirect(Route::_('index.php?option=com_academy&view=category', false));
    }

    public function apply(): void
    {
        $id = $this->doSave();
        Factory::getApplication()->redirect(Route::_('index.php?option=com_academy&view=category&id=' . $id, false));
    }

    public function save(): void
    {
        $this->doSave();
        Factory::getApplication()->redirect(Route::_('index.php?option=com_academy&view=categories', false));
    }

    public function save2new(): void
    {
        $this->doSave();
        Factory::getApplication()->redirect(Route::_('index.php?option=com_academy&view=category', false));
    }

    public function cancel(): void
    {
        Factory::getApplication()->redirect(Route::_('index.php?option=com_academy&view=categories', false));
    }

    private function doSave(): int
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }

        $data = $app->getInput()->post->get('jform', [], 'array');
        $existingImage = (string) ($app->getInput()->post->getString('jform_existing_image', ''));
        $data['default_image'] = $existingImage;

        if ($app->getInput()->post->getInt('jform_remove_image')) {
            CategoriesHelper::deleteDefaultImage($existingImage);
            $data['default_image'] = '';
        }

        $upload = $app->getInput()->files->get('jform_default_image');
        if (is_array($upload) && !empty($upload['name'])) {
            $newImage = CategoriesHelper::saveDefaultImage($upload);
            if ($newImage !== '') {
                CategoriesHelper::deleteDefaultImage($existingImage);
                $data['default_image'] = $newImage;
            }
        }

        $id = CategoriesHelper::save($data);

        $associations = ArrayHelper::toInteger((array) $app->getInput()->post->get('jform_associations', [], 'array'));
        CategoriesHelper::saveAssociations($id, (string) ($data['language'] ?? '*'), $associations);

        $app->enqueueMessage('Category saved.');
        return $id;
    }
}
