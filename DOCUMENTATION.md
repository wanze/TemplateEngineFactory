# Template Engine Factory Documentation

## Table of Contents

1. [Updating from `1.x` to `2.x`](#updating-from-1x-to-2x)
2. [Controllers](#controllers)
3. [Hooks](#hooks)
4. [Best Practices](#best-practices)
5. [Implementing a Template Engine](#implementing-a-template-engine)
6. [Running Tests](#running-tests)


## Updating from `1.x` to `2.x`

The `2.x` major version introduces several backwards compatibility breaks. The amount of required manual update steps
depends on how many features were used. The following list _should_ cover the most important changes: 

* Your template engine must support the `2.x` version. Check the available template engines in the [readme](README.md#available-template-engines).
* The API variable `$factory` is no longer available. Use `$modules->get('TemplateEngineFactory')` to get an instance of
the `\ProcessWire\TemplateEngineFactory` class.
* The factory has a new configuration `templates_path`, indicating where the engine's templates are stored. This config
was previously defined on template engine level. If you have customized this setting, make sure to update the value in
the `TemplateEngineFactory` module's config. Defaults to `templates/views`.
* _Chunks_ are now called _Controllers_. While the semantics remain the same, the public API has changed. Please
read the [Controllers](#controllers) chapter on how to update your code.
* The method `TemplateEngineFactory::instance()`, plus some aliases do no longer exist. Use
`TemplateEngineFactory::render()` to render a given template of the engine.
* On template engine level, the confusing `global_template` setting does no longer exist. Use the introduced hook
`___resolveTemplate` to resolve a custom template when a page gets rendered. The default strategy still looks for a template
with the same name as the ProcessWire template.

> Make sure to clear the compiled files cache after replacing the module files! Press _Clear Compiled Files_ at the
bottom when viewing the site's modules in the ProcessWire admin. Or simply delete anything in `./site/assets/cache/`.

## Controllers

A _controller_ wraps a ProcessWire template executing some logic and a template file of the active engine, rendering the
output. This is how the _Automatic page rendering_ feature works, by using the template file of a page as controller. By
defining custom controllers, you get the same functionality without depending on a page:

```php
$factory = $modules->get('TemplateEngineFactory');

// Get the sidebar controller, responsible to render a sidebar.
$controller = $factory->controller('controllers/sidebar.php', 'partials/sidebar.html.twig');

// Optional: You might pass some data to the controller.
$controller->nPosts = 3;

// Executing the controller renders the associated template via template engine.
$controller->execute();
```

The controller and template file from the above example might look like this:

```php
// site/templates/controllers/sidebar.php
 
$posts = $pages->find("template=blog-post,limit=$nPosts");

// We have access to the $view API variable, connecting us to the template.
$view->posts = $posts;
```

```twig
// site/templates/views/partials/sidebar.twig.html

{% for post in posts %}
    <h3>{{ post.title }}</h3>
{% endfor %}
```

### Updating from `1.x`

* Use the `TemplateEngineFactory::controller` factory method instead of `TemplateEngineFactory::chunk`.
* Always provide the controller file and the template file.
* Call `Controller::execute` to process the logic in the controller and to render the template.

```php
// Version 1.x
$chunk = $factory->chunk('chunks/nav-item');
$chunk->render();

// Version 2.x
$controller = $factory->controller('chunks/nav-item', 'chunks/nav-item');
$controller->execute();
```

## Hooks

The TemplateEngineFactory provides several hooks to influence the automatic page rendering process.

### `___resolveTemplate`

Use this hook to customize the template being used when rendering a page. By default, the module looks for
a template with the same name as the page template's name. For example, when rendering the homepage
with the `home` template, the factory loads the `home.html.twig` twig template.

```php
wire()->addHookAfter('TemplateEngineFactory::resolveTemplate', function (HookEvent $event) {
    $event->return = 'customTemplate';
});
```  

---

### `___resolveTemplateVariables`

This hook allows to customize the template variables available in your template. Normally,
the variables are set in the controllers via `$view` API variable. For example, calling 
`$view->foo = 'bar'` in your controller makes the `foo` variable accessible in the template. If you
need some _global_ variables available for all pages being rendered, hook them up like this:

```php
wire()->addHookAfter('TemplateEngineFactory::resolveTemplateVariables', function (HookEvent $event) {
    $event->return = array_merge(
        $event->return,
        [
            'user' => $this->wire('user'),
        ]
    );
});
```  

> The above example makes the current user available in all templates. However, changes are high that the
template engine already has access to all ProcessWire API variables. If you are using Twig, you can toggle this
behaviour in the _TemplateEngineTwig_ module's configuration.

---

### `___shouldRenderPage`

Use this hook to customize if a page should be rendered by the template engine.

```php
wire()->addHookAfter('TemplateEngineFactory::shouldRenderPage', function (HookEvent $event) {
    $page = $event->arguments('page');
    
    // Never render pages with the api template.
    if ($page->template->name === 'api') {
        $event->return = false;
    }
});
```

## Best Practices

Write me please 😬

## Implementing a Template Engine

Adding your favorite template engine to the factory is really easy.

* Each template engine is added via ProcessWire module. This module provides the template engine's configuration and
registers the engine to the factory.
* The engine itself is implemented using the `TemplateEngineFactory\TemplateEngineInterface` contract.
* The factory provides an abstract base implementation `TemplateEngineFactory\TemplateEngineBase` which your engine
should extend.

For an example, take a look at the [TemplateEngineTwig](https://github.com/wanze/TemplateEngineTwig) implementation. 

## Running Tests

The module includes [PHPUnit](https://phpunit.de/) based tests cases, located in the `./tests` directory.

* Make sure that the dev dependencies are installed by running `composer install` in the ProcessWire root directory.
* The tests will create pages and templates. Everything should get cleaned up properly, but you should not run them
on a production environment 😉.

To run the tests:

```
vendor/bin/phpunit --bootstrap site/modules/TemplateEngineFactory/tests/bootstrap.php site/modules/TemplateEngineFactory/tests/src
```  

