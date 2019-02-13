# Changelog

## [Unreleased]

## [2.0.0] - 2019-02-13

* First release of the new major `2.x` version 🐣
* Check out the [documentation](DOCUMENTATION.md), especially the [update guide](DOCUMENTATION.md#updating-from-1x-to-2x)
if you like  to update from `1.x`.

### Added

* Add possibility to enable or disable automatic page rendering.
* Add a configuration to enable or disable automatic page rendering for templates.
* Add hooks allowing you to customize the module's behaviour. See [docs](DOCUMENTATION.md#hooks).
* Add unit tests 🎉

### Changed

* A lot of things! They are summarized in the [update guide](DOCUMENTATION.md#updating-from-1x-to-2x).

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

[2.0.0]: https://github.com/wanze/TemplateEngineFactory/releases/tag/v2.0.0
[1.1.3]: https://github.com/wanze/TemplateEngineFactory/releases/tag/v1.1.3
[1.1.2]: https://github.com/wanze/TemplateEngineFactory/releases/tag/v1.1.2
[1.1.1]: https://github.com/wanze/TemplateEngineFactory/releases/tag/v1.1.1
[Unreleased]: https://github.com/wanze/TemplateEngineFactory/compare/v2.0.0...HEAD
