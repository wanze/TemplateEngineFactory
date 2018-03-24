# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.3] - 2018-03-18

### Fixed
* Fix `Notice: Undefined index: TemplateEngineNull` if no template is available for the current page
and debug mode is enabled.

## [1.1.2] - 2018-03-18

### Fixed
* Add a hook before `ProcessPageView::pageNotFound` to handle `Wire404Exception` thrown by controllers.
The current active template engine now receives the configured page displaying a 404 rather
than the page throwing the 404 exception.

## [1.1.1] - 2018-03-18

### Added
* Template engines can now pass multiple key/value data via new method `TemplateEngine::setArray()`.

### Fixed
* Lower hook priority for the hook executed after `Page::render`. This makes sure that the current active
template engine returns the markup *before* other modules hooking after `Page::render`.

[1.1.3]: https://github.com/wanze/TemplateEngineFactory/releases/tag/v1.1.3
[1.1.2]: https://github.com/wanze/TemplateEngineFactory/releases/tag/v1.1.2
[1.1.1]: https://github.com/wanze/TemplateEngineFactory/releases/tag/v1.1.1