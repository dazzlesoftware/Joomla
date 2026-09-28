<?php

namespace Joomla\Component\Blog\Administrator\View\Settings;

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
        'general' => ['label' => 'General', 'icon' => 'cog', 'fieldsets' => ['postnav', 'create_post_redirect', 'integration_newsfeed', 'integration_sef', 'integration_customfields']],
        'posts' => ['label' => 'Post Display', 'icon' => 'file-alt', 'fieldsets' => ['posts']],
        'editor' => ['label' => 'Editor & Authoring', 'icon' => 'edit', 'fieldsets' => ['block_editor', 'polls', 'editinglayout']],
        'lists' => ['label' => 'Post Lists & Blog Layouts', 'icon' => 'list', 'fieldsets' => ['list_display', 'automated_truncation', 'listing_filters', 'featured_slider', 'blog_default_parameters', 'compact_posts', 'list_default_parameters', 'shared']],
        'authors' => ['label' => 'COM_BLOG_AUTHORS_HEADING', 'icon_class' => 'fa-solid fa-users', 'fieldsets' => ['authors', 'author_posts', 'author_slider']],
        'tags' => ['label' => 'COM_BLOG_TAGS_LAYOUT', 'icon_class' => 'fa-solid fa-tags', 'fieldsets' => ['tags_directory', 'tag_posts', 'tag_posts_slider', 'tag_posts_compact']],
        'archives' => ['label' => 'Archives', 'icon_class' => 'fa-solid fa-box-archive', 'fieldsets' => ['archive_posts', 'archive_posts_slider', 'archive_posts_compact']],
        'categories' => ['label' => 'Category Layouts', 'icon' => 'folder', 'fieldsets' => ['category', 'categories']],
        'engagement' => ['label' => 'Engagement & Sharing', 'icon' => 'share-alt', 'fieldsets' => ['engagement', 'engagement_appearance']],
        'ai' => ['label' => 'AI Writing and Images', 'icon' => 'brain', 'icon_class' => 'fa-solid fa-brain', 'fieldsets' => ['ai_tools']],
        'comments' => ['label' => 'Comments', 'icon' => 'comments', 'fieldsets' => ['comments', 'comment_safety']],
        'email' => ['label' => 'Email', 'icon' => 'envelope', 'fieldsets' => ['email_delivery', 'email_tracking']],
    ];

    public function display($tpl = null): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.options', 'com_blog')) {
            throw new \RuntimeException('You are not authorised to change these settings.', 403);
        }

        $this->sections['authors']['label'] = \Joomla\CMS\Language\Text::_('COM_BLOG_AUTHORS_HEADING');

        $this->sections['tags']['label'] = \Joomla\CMS\Language\Text::_('COM_BLOG_TAGS_LAYOUT');
        $factory = Factory::getContainer()->get(FormFactoryInterface::class);
        $this->form = $factory->createForm('com_blog.settings', ['control' => 'jform']);
        $this->form->loadFile(JPATH_COMPONENT_ADMINISTRATOR . '/forms/settings.xml');
        $dateParams = clone ComponentHelper::getParams('com_blog');
        [$showDate, $dateType] = \Joomla\Component\Blog\Site\Helper\DateHelper::options($dateParams);
        $dateParams->set('show_date', (int) $showDate);
        $dateParams->set('date_type', $dateType);
        $dateParams->set('posts_per_page', \Joomla\Component\Blog\Site\Helper\ListingSettingsHelper::count($dateParams));
        $this->form->bind(['params' => $dateParams->toArray()]);

        ToolbarHelper::title('Blog Settings', 'cog');
        ToolbarHelper::apply('settings.save');
        ToolbarHelper::save('settings.save2close');
        ToolbarHelper::cancel('settings.cancel', 'JTOOLBAR_CLOSE');

        parent::display($tpl);
    }
}
