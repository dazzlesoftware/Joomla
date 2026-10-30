<?php
// CLI-only, read-only Joomla menu form construction, inheritance and binding checks.
if (PHP_SAPI !== 'cli') { exit(1); }
require __DIR__ . '/settings-audit/forms.php';
$app->getInput()->set('option', 'com_menus');
$checked = $globals = 0;
$views = ['archive', 'author', 'authors', 'categories', 'category', 'featured', 'form', 'myposts', 'post', 'tags'];
foreach (['academy', 'blog', 'codex'] as $family) {
    $component = 'com_' . $family;
    $params = Joomla\CMS\Component\ComponentHelper::getParams($component);
    $settings = simplexml_load_file(__DIR__ . '/../' . $family . '/' . $component . '/admin/forms/settings.xml');
    $defaults = [];
    foreach ($settings->xpath('//fields[@name="params"]//field[@default]') as $field) {
        $defaults[(string) $field['name']] = (string) $field['default'];
    }
    foreach ($views as $view) {
        foreach (glob(__DIR__ . '/../' . $family . '/' . $component . '/site/tmpl/' . $view . '/*.xml') as $path) {
            if (basename($path) === 'metadata.xml') { continue; }
            $xml = simplexml_load_file($path);
            if ($view === 'categories') {
                $expected = ['id', 'categories_style', 'categories_listing_layout', 'categories_column_style', 'categories_columns', 'show_base_description', 'categories_description', 'maxLevelcat', 'show_empty_categories_cat', 'show_subcat_desc_cat', 'show_cat_num_posts_cat', 'show_postnav'];
                $actual = array_map(static fn ($field) => (string) $field['name'], $xml->xpath('//field'));
                if ($actual !== $expected) { throw new RuntimeException('Categories must expose only directory controls: ' . $path); }
            }
            $reference = file_get_contents(str_replace([$family . '/' . $component, '\\' . $family . '\\' . $component], ['blog/com_blog', '\\blog\\com_blog'], $path));
            $normalized = str_replace([$family, strtoupper($family), ucfirst($family)], ['blog', 'BLOG', 'Blog'], file_get_contents($path));
            if (simplexml_load_string($normalized)->asXML() !== simplexml_load_string($reference)->asXML()) {
                throw new RuntimeException('Family metadata drift: ' . $path);
            }
            $names = [];
            $form = new Joomla\CMS\Form\Form('menu.audit.' . $family . '.' . $view, ['control' => 'jform']);
            $form->loadFile($path, true, '/metadata');
            $values = [];
            foreach ($xml->fields as $group) {
                foreach ($group->fieldset as $fieldset) {
                    foreach ($fieldset->field as $definition) {
                        $name = (string) $definition['name'];
                        if (isset($names[$name])) { throw new RuntimeException('Duplicate field ' . $path . ': ' . $name); }
                        $names[$name] = true;
                        if ((string) $definition['type'] === 'componentlayout' && !str_contains((string) $definition['class'], 'form-select')) {
                            throw new RuntimeException('Unstyled layout control: ' . $path);
                        }
                        // Bind an explicit option, preferring zero to catch accidental truthy fallback.
                        $options = $definition->xpath('./option[@value]');
                        if (!$options) { continue; }
                        $value = (string) end($options)['value'];
                        foreach ($options as $option) { if ((string) $option['value'] === '0') { $value = '0'; break; } }
                        $values[(string) $group['name']][$name] = $value;
                    }
                }
            }
            $form->bind($values);
            foreach ($values as $group => $fields) {
                foreach ($fields as $name => $value) {
                    $field = $form->getField($name, $group);
                    $bound = is_array($field->value) ? implode(',', $field->value) : (string) $field->value;
                    if ($bound !== $value) { throw new RuntimeException('Lost bound value: ' . $path . ': ' . $name); }
                    ++$checked;
                }
            }
            foreach ($xml->xpath('//field[@type="globaldefaultlist"]') as $definition) {
                $name = (string) $definition['name'];
                $field = $form->getField($name, 'params');
                $original = $params->get($name);
                $options = $definition->xpath('./option[@value]');
                try {
                    foreach ([null, (string) $options[0]['value'], (string) end($options)['value']] as $saved) {
                        $params->set($name, $saved);
                        $expectedValue = $saved ?? ($defaults[$name] ?? null);
                        $expectedText = null;
                        foreach ($options as $option) {
                            if ((string) $option['value'] === (string) $expectedValue && $expectedValue !== null && $expectedValue !== '') {
                                $expectedText = Joomla\CMS\Language\Text::sprintf('JGLOBAL_USE_GLOBAL_VALUE', Joomla\CMS\Language\Text::_((string) $option));
                            }
                        }
                        $actual = (new ReflectionMethod($field, 'getOptions'))->invoke($field);
                        // Joomla omits options requiring disabled features (for example voting).
                        $available = array_filter($actual, static fn ($option) => $option->value !== '' && (string) $option->value === (string) $expectedValue);
                        if ($available && $expectedText !== null && $actual[0]->text !== $expectedText) {
                            throw new RuntimeException('Incorrect global label: ' . $path . ': ' . $name . ': ' . $actual[0]->text);
                        }
                        ++$globals;
                    }
                } finally { $params->set($name, $original); }
            }
            echo $family . ': ' . $view . '/' . basename($path) . " bindings and global labels passed.\n";
        }
    }
}
echo "$checked option bindings and $globals inherited default/saved option labels passed.\n";
