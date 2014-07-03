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

Any configurations related to the engines are set in the config options of the engine itself, e.g. *TemplateEngineProcesswire*. One thing you can configure for each engine is where to store the template files.

##How does it work?
For each controller that should output markup, create a corresponding template file in the directory where all the template files are stored (this is configurable per engine).

* /site/templates/home.php      => /site/templates/views/home.php
* /site/templates/product.php   => /site/templates/views/product.php

The factory tries to load the template file of the current page's template. If a template file is found, an instance of it is accessible over the API variable. If no template file is found, the factory assumes that you don't want to output any markup. 

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
In the example above, we process some logic if a form was sent. Note that we do not output any markup here, because this should now be done by the corresponding template file! Over the new API variable *$view* some key/value pairs are passed to the template. Here is an example how the template could look like:
```php
// In template file: /site/templates/view/home.php

<h1><?= $page->title ?></h1>
<p>Foo: <?= $bar ?></p>

<?php if ($show_nav): ?>
  <ul>
  <?php foreach ($nav_pages as $p): ?>
    <li><a href="<?= $p->url ?>"><?= $p->title ?></a></li>
  <?php endforeach; ?>
  </ul>
<?php endif; ?>
```
Assume that we'd have installed the module *TemplateEngineSmarty* and chosen *Smarty* as the active template engine. The template file could look like this:
```php
// In template file: /site/templates/smarty/home.tpl

<h1>{$page->title}</h1>
<p>Foo: {$bar}</p>

{if $show_nav}
  <ul>
  {foreach $nav_pages as $p}
    <li><a href="{$p->url}">{$p->title}</a></li>
  {/foreach}
  </ul>
{/if}
```
(It is possible to switch the template engine without changing any logic in the controller files!)

### Load and output markup of other template files
Use the TemplateEngineFactory module to load any template file and output it's markup:
```php
// In controller file: /site/templates/product.php

$factory = $modules->get('TemplateEngineFactory');
$chunk = $factory->load('chunks/product_chunk.tpl');
$chunk->set('product_title', $page->title');
$chunk->set('date', date('d.m.Y'));
$chunk_output = $chunk->render();
$view->set('chunk', $chunk_output);
```
The example above loads a template file called *product_chunk.tpl* and sets some variables. Calling *render()* returns the rendered markup.

## Important: Caching
Since former ProcessWire templates are now controllers that generally do not output any markup, the ProcessWire template cache should *NOT* be active. Deactivate cache in the settings of your template under the section *Cache*.

It is possible for any template engine to support additional caching. Right now, only *Smarty* supports caching of it's own templates. In this case, the following methods are available for you (advanced usage):
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
If caching is supported by the engine, the *TemplateEngineFactory* module takes care of clearing the cache whenever pages are saved or deleted.

# Implementing a template engine
Implementing another template engine is straightforward. For examples, please take a look at some implemented engines like *Smarty* or *Twig*. Your engine needs to extend the abstract class *TemplateEngine* and implement some methods.
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
After installing your module, the engine should be recognized by the factory.
