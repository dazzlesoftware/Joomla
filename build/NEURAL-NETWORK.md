# Neural Network authoring and SEO

Academy, Blog and Codex each have Settings > AI Writing and Images. Enable AI tools, choose the text provider, and enter its API key. Image generation has a separate switch and uses OpenAI even when Claude is selected for text. API billing is separate from chat subscriptions.

Keys are encrypted with the Joomla site secret. Blank key inputs preserve the saved key; the remove checkbox clears it. Server environment variables OPENAI_API_KEY and ANTHROPIC_API_KEY take precedence. Preserve the Joomla secret during migrations or re-enter keys. Keys never appear in browser options.

Grant Use AI tools in component Permissions alongside normal post create/edit permissions. Images also require Media create permission. Requests require a session token and apply session hourly and output-token limits.

AI Rewrite, AI Excerpt and AI SEO preview suggestions before applying them to the unsaved draft. In the block editor select an individual text field to preserve structure. Media > Generate image with AI retains Browse Media. Featured and Open Graph image fields have generation buttons. Save image and use writes images under images/generated/<family>/. Previews expire after 30 minutes. Save the post normally after applying changes.

SEO and Social Sharing includes title, canonical URL, Robots metadata, Open Graph type/title/description/image/alt and social card format, alongside keywords and meta description. Empty social image uses the featured image. Robots changes page metadata, not robots.txt. Canonical and indexing decisions remain manual; alt-text suggestions use the supplied description, not image recognition. Modules render saved content without issuing AI requests.

## Models and plugins

Models are dropdowns. Select Custom to reveal the model-ID textbox. Existing saved IDs remain available even if a catalog plugin is later disabled. The NeuralNetwork file rename preserves ai_* settings, credentials and permissions.

Install dist/plg_neuralnetwork_modelcatalog.zip (also in each full family installer). In System > Manage > Plugins, open Neural Network - Model Catalog, add rows with provider, task, exact model ID and label, and enable it. Choices are shared by all three components. OpenAI supports text and images; Claude supports text. Catalog entries do not grant access or change billing. Models must support the existing Responses, Images or Messages payloads.

Custom Joomla plugins in the neuralnetwork group subscribe to onNeuralNetworkModels. The generic Joomla Event supplies provider (openai/claude), kind (text/image), component and result. Filter by provider and kind, then append entries:

```php
$result = (array) $event->getArgument('result', []);
$result[] = [['id' => 'your-model-id', 'label' => 'Your model']];
$event->setArgument('result', $result);
```

Built-in IDs take precedence over duplicates; invalid IDs are discarded. Plugins extend model choices, not provider endpoints/authentication. See plugins/neuralnetwork/modelcatalog for a complete example. Dropdown loading makes no provider requests.

## Offline validation

```powershell
& C:/wamp64/bin/php/php8.3.28/php.exe build/test-neural-network.php C:/wamp64/www/Joomla
```

The browser fixture build/test-neural-network-editor.html accepts family=academy/blog/codex and mode=blocks. It mocks provider responses without modifying site content.
