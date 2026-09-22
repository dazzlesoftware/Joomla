<?php
/** @var array $displayData */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$company = '<a href="https://dazzlesoftware.org/" target="_blank" rel="nofollow noopener noreferrer">'
    . $escape(Text::_('COM_BLOG_CREDIT_COMPANY')) . '</a>';
?>
<div class="dazzle-credit">
    <div class="dazzle-credit-product"><?php echo $escape(Text::_('COM_BLOG_POWERED_BY')); ?>
    <a href="https://dazzlecms.org/" target="_blank" rel="nofollow noopener noreferrer"><?php echo $escape(Text::_('COM_BLOG_CREDIT_PRODUCT')); ?></a>
    </div>
    <div class="dazzle-credit-copyright"><?php echo Text::sprintf('COM_BLOG_CREDIT_COPYRIGHT', $company); ?></div>
</div>
