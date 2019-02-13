# Template Engine Factory

[![Build Status](https://travis-ci.org/wanze/TemplateEngineFactory.svg?branch=master)](https://travis-ci.org/wanze/TemplateEngineFactory)
[![StyleCI](https://github.styleci.io/repos/21270731/shield?branch=master)](https://github.styleci.io/repos/21270731)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![ProcessWire 3](https://img.shields.io/badge/ProcessWire-3.x-orange.svg)](https://github.com/processwire/processwire)

A ProcessWire module integrating template engines such as _Twig_. It allows to render pages or individual templates
via template engine and encourages to separate logic from markup by implementing a simple _MVC_ pattern. 

* For a quick introduction, please read the [Getting Started](#getting-started) section of this readme.
* More information is available in the official [Documentation](DOCUMENTATION.md).

> Version `2.x` of this module differs from the `1.x` version in many ways. Modules providing template engines _must_ be
installed with Composer. Twig is currently the only template engine implemented for the `2.x` major version. Please
take a look at the [update guide](DOCUMENTATION.md#updating-from-1x-to-2x), as the new version introduces backwards
compatibility breaks. The `1.x` version of the module is available on the [1.x branch](https://github.com/wanze/TemplateEngineFactory/tree/1.x).

## Requirements

* ProcessWire `3.0` or newer
* PHP `7.0` or newer
* Composer

## Installation

Execute the following command in the root directory of your ProcessWire installation:

```
composer require wanze/template-engine-factory:^2.0
```

This installs the module and its bundled template engine called _TemplateEngineProcessWire_. This template engine uses 
ProcessWire's internal `TemplateFile` class for rendering. Other template engines are added to the factory by installing
separate ProcessWire modules.

### Installing Twig and other template engines

Each template engine is a separate ProcessWire module. For example, if you want to use [Twig](https://github.com/wanze/TemplateEngineTwig),
execute the following command:

```
composer require wanze/template-engine-twig:^2.0
```

This will install the _TemplateEngineTwig_ and _TemplateEngineFactory_ modules in one step, no need to install both
separately. It also installs the Twig dependencies for us, pretty neat! ✌️

> ℹ️ This module includes test dependencies. If you are installing it on production with `composer install`, make sure to
pass the `--no-dev` flag to omit autoloading any unnecessary test dependencies!.

## Configuration

The module offers the following configuration options:

* **`Template Engine`** The template engine used to render pages and templates. Any installed engine is listed here.
* **`Path to templates`** Relative path from the site directory where template files are stored. E.g. `templates/views/`
resolves to `/site/templates/views/`.
* **`Enable automatic page rendering`** Check to delegate the rendering of pages to the template engine.
You may enable or disable this behaviour for specific templates.
* **`API variable to interact with the template engine`** Enter a name for the API variable used to pass data from
the ProcessWire template (Controller) to the template engine.
* **`Enabled templates`** Restrict automatic page rendering to the templates selected here.
* **`Disabled templates`** Select templates of pages that should not automatically be rendered via template engine.
Do not use in combination with the _Enabled templates_ configuration,
either enable or disable templates.

> More configuration options might be available in the module providing a template engine, e.g. the
module _TemplateEngineTwig_ offers several configuration related to Twig.

## Available Template Engines

* **ProcessWire** A template engine using ProcessWire's *TemplateFile* class for rendering. This engine is bundled with
this module, but it is not installed automatically. Install the module _TemplateEngineProcessWire_ and select the 
engine in the _TemplateEngineFactory_ module configuration.
* **Twig** See: https://github.com/wanze/TemplateEngineTwig

## Getting Started

> This section assumes that Twig is used as active template engine, but the usage is excatly the same for any other
template engine.

### Using the Template Engine to render templates

Assume the following Twig template exists in `/site/templates/views/foo.html.twig`

```twig
<h1>{{ title }}</h1>
{% if body %}
    <p>{{ body }}</p>
{% endif %}
```

The template can be rendered anywhere with the _Template Engine Factory_ module:

```php
$factory = wire('modules')->get('TemplateEngineFactory');

// Render foo.html.twig with some data.
$factory->render('foo', ['title' => 'Foo', 'body' => 'Hello World']);
```

### Automatic Page Rendering

If enabled, this feature uses the template engine to render ProcessWire pages when calling `Page::render`.
By default, the module tries to find a Twig template matching the same name as the ProcessWire template:

* `/site/templates/views/home.html.twig` corresponds to `/site/templates/home.php`
* `/site/templates/views/about.html.twig` corresponds to `/site/templates/about.php`

ProcessWire templates have access to a `$view` API variable which can be used to pass data to the template engine.
As the template engine is now responsible to output markup, ProcessWire templates can be seen as _Controllers_.
They process the request and pass data to the _View_ layer via the `$view` API variable.

**Examples**

Consider the following ProcessWire template in `/site/templates/home.php`

```php
// Form has been submitted, do some processing, send mail, save data... 
if ($input->post->form) {
  // ...
  $session->redirect('./');
}

// Forward some data to twig
$view->set('nav_items', $pages->get('/')->children());
```

The corresponding Twig template in `/site/templates/views/home.html.twig` might look like this:

```twig
<h1>{{ page.title }}</h1>

<ul class="nav">
{% for item in nav_items %}
    <li><a href="{{ item.url }}">{{ item.title }}</a></li>
{% endfor %}
</ul>

<form name="form">
    <input type="text" name="email">
    <input type="submit" value="Submit">
</form>
```

Note that the ProcessWire template does not echo out any markup. It just contains business logic and uses the `$view` API
variable to pass data to the Twig template. That's it! The most simple _MVC_ pattern available in ProcessWire. 😎
