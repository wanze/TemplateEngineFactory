TemplateEngineFactory
=====================
ProcessWire module helping to separate logic from markup. It turns ProcessWire templates into *controllers* which can interact over a new API variable with various template engines like Smarty or Twig. Any template engine can be added to the factory as separate module.

##Implemented engines
* **ProcessWire** Default engine using the class *TemplateFile* of ProcessWire. This engine ships with this module.
* **Smarty** See: https://github.com/wanze/TemplateEngineSmarty
* **Twig** See: https://github.com/wanze/TemplateEngineTwig

##Installation
Install the module just like any other ProcessWire module. Check out the following guide: http://modules.processwire.com/install-uninstall/

##Configuration
* **Template Engine** The template engine that is used to render your templates. Any installed engine is listed here. By default, you can choose *ProcessWire*, the engine that ships with this module.
* **API variable** This is the variable you can use in the controllers (ProcessWire templates) to access the template of the current page.

Any configurations related to the engines are set in the config options of the engine itself, e.g. *TemplateEngineProcesswire*. One setting configurable for each engine is where the template files are stored.

##How does it work?
For each controller that should output markup a corresponding template file should exist (in the directory configured per engine). The convention is that the template file *must* have the same name as the controller (aka ProcessWire template):

* Template `/site/templates/views/home.php` corresponds to controller `/site/templates/home.php`
* Template `/site/templates/views/product.php` corresponds to controller `/site/templates/product.php`

The factory tries to load the template file of the current page's controller. If a template file is found, an instance of it is accessible over the API variable. If no template file is found, the factory assumes that the controller does not output markup over the template engine.

The following example uses the ProcessWire template engine:
```php
// In controller file: /site/templates/home.php

if ($input->post->form) {
  // Do some processing, send mail, save data...
  $session->redirect('./');
}

$view->set('foo', 'bar');
$view->set('show_nav', true);
$view->set('nav_pages', $pages->get('/')->children());
```
In the example above, some logic is processed if a form was sent. Note that there is no markup generated, because this should now be done by the corresponding template file! Over the new API variable *$view*, key/value pairs are passed to the template. Here is an example how the template file could look like:
```php
// In template file: /site/templates/view/home.php

<h1><?= $page->title ?></h1>
<p>Foo: <?= $foo ?></p>

<?php if ($show_nav): ?>
  <ul>
  <?php foreach ($nav_pages as $p): ?>
    <li><a href="<?= $p->url ?>"><?= $p->title ?></a></li>
  <?php endforeach; ?>
  </ul>
<?php endif; ?>
```
Assume there is installed the module TemplateEngineSmarty and Smarty is chosen as the active template engine. The template file could look like this:
```php
// In template file: /site/templates/smarty/home.tpl

<h1>{$page->title}</h1>
<p>Foo: {$foo}</p>

{if $show_nav}
  <ul>
  {foreach $nav_pages as $p}
    <li><a href="{$p->url}">{$p->title}</a></li>
  {/foreach}
  </ul>
{/if}
```
The introduced API variable acts as a gateway to the active template engine. This means that the template engine can be switched at any time without the need to change the controller logic! In the previous example, the controller logic is still the same but the template engine was switched from ProcessWire to Smarty. 

### Load and output markup of other template files
Use the TemplateEngineFactory module to load any template file and output it's markup:
```php
// In controller file: /site/templates/product.php

$factory = $modules->get('TemplateEngineFactory');
$chunk = $factory->load('chunks/product_chunk.tpl');
$chunk->set('product_title', $page->title);
$chunk->set('date', date('d.m.Y'));
$chunk_output = $chunk->render();
$view->set('chunk', $chunk_output);
```
The example above loads a template file called *product_chunk.tpl* and sets some variables. Calling *render()* returns the rendered markup.

## Important: Caching
Since former ProcessWire templates are now controllers that generally do not output any markup, the ProcessWire template cache should *NOT* be active. Deactivate cache in the settings of your template under the section "Cache".

It is possible for any template engine to support additional caching. At the moment only Smarty supports caching of it's own templates. In this case, the following methods are available for you (advanced usage):
```php
// These methods are only available if the selected engine supports caching!!

// Do only process logic if no cache file is existing
if (!$view->isCached()) {
  $view->txt = "No cache exists, I pass this variable";
}

// Clear cache of current template file
$view->clearCache();

// Clear cache of all template files
$view->clearAllCache();
```
If caching is supported by the engine, the TemplateEngineFactory module takes care of clearing the cache whenever pages are saved or deleted.

# Implementing a template engine
Implementing another template engine is straightforward. Please take a look at the implemented engines like Smarty or Twig to see some examples. Your engine needs to extend the abstract class TemplateEngine and implement some methods.
```php
class TemplateEngineMyEngine extends TemplateEngine implements Module, ConfigurableModule
{
  
  public function initEngine()
  {
    // This method is called by the TemplateEngineFactory after creating an instance. Setup the engine here.
  }
  
  public function set($key, $value)
  {
    // Pass a key/value pair to your engine
  }
  
  public function render()
  {
    // Output the markup of the loaded template file
  }
}
```
After installing TemplateEngineMyEngine module, the engine should be recognized by the TemplateEngineFactory and can be used to render the markup.
