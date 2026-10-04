<?php
namespace Joomla\Component\Blog\Administrator\Field;
defined('_JEXEC') or die;
/** Preserve the selected author when editing a legacy single-author menu. */
class AuthorfilterField extends PostauthorsField
{
    protected $type = 'Authorfilter';
    protected function getInput()
    {
        if ($this->fieldname === 'listing_exclude_authors' && !$this->form->getData()->exists('params.listing_exclude_authors')) {
            $this->value = $this->form->getValue('author_listing_exclude_authors', 'params', []);
        } elseif ($this->fieldname === 'listing_authors' && !$this->form->getData()->exists('params.listing_authors')) {
            parse_str((string) parse_url((string) $this->form->getValue('link'), PHP_URL_QUERY), $route);
            if (($route['option'] ?? '') === 'com_blog' && ($route['view'] ?? '') === 'author' && !empty($route['id'])) {
                $this->value = [(int) $route['id']];
            }
        }
        return parent::getInput();
    }
}
