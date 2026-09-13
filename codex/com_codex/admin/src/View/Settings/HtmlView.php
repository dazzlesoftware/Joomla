<?php

namespace Joomla\Component\Codex\Administrator\View\Settings;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public $form;

    public array $sections = [
        'general' => ['label' => 'General', 'icon' => 'cog', 'fieldsets' => ['integration_newsfeed', 'integration_sef', 'integration_customfields']],
        'posts' => ['label' => 'Post Display', 'icon' => 'file-alt', 'fieldsets' => ['posts']],
        'editor' => ['label' => 'Editor & Authoring', 'icon' => 'edit', 'fieldsets' => ['block_editor', 'quote_settings', 'tab_settings', 'accordion_settings', 'column_settings', 'polls', 'poll_access', 'editinglayout']],
        'lists' => ['label' => 'Post Lists & Blog Layouts', 'icon' => 'list', 'fieldsets' => ['list_display', 'blog_default_parameters', 'list_default_parameters', 'shared']],
        'categories' => ['label' => 'Category Layouts', 'icon' => 'folder', 'fieldsets' => ['category', 'categories']],
        'engagement' => ['label' => 'Engagement & Sharing', 'icon' => 'share-alt', 'fieldsets' => ['engagement', 'engagement_appearance']],
        'comments' => ['label' => 'Comments', 'icon' => 'comments', 'fieldsets' => ['comments', 'comment_safety']],
        'email' => ['label' => 'Email', 'icon' => 'envelope', 'fieldsets' => ['email_delivery', 'email_tracking']],
    ];

    public function display($tpl = null): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.options', 'com_codex')) {
            throw new \RuntimeException('You are not authorised to change these settings.', 403);
        }

        $factory = Factory::getContainer()->get(FormFactoryInterface::class);
        $this->form = $factory->createForm('com_codex.settings', ['control' => 'jform']);
        $this->form->loadFile(JPATH_COMPONENT_ADMINISTRATOR . '/forms/settings.xml');
        $this->form->bind(['params' => ComponentHelper::getParams('com_codex')->toArray()]);

        ToolbarHelper::title('Codex Settings', 'cog');
        ToolbarHelper::apply('settings.save');
        ToolbarHelper::save('settings.save2close');
        ToolbarHelper::cancel('settings.cancel', 'JTOOLBAR_CLOSE');

        parent::display($tpl);
    }
}
