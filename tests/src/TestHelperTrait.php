<?php

namespace TemplateEngineFactory\Test;

use ProcessWire\HookEvent;
use ProcessWire\Page;
use ProcessWire\ProcessWire;
use ProcessWire\Template;

/**
 * Provides some useful methods used by different tests.
 */
trait TestHelperTrait
{
    /**
     * @param string $templateName
     *
     * @return \ProcessWire\Page
     */
    private function getPage($templateName)
    {
        $template = new Template();
        $template->name = $templateName;

        return new Page($template);
    }

    /**
     * @param ProcessWire $wire
     * @param string $hookedMethod
     *   The method being hooked, e.g. Page::render.
     * @param $fromObject
     *   Class name of the object attaching the hook.
     * @param $type
     *   after or before
     *
     * @return bool
     */
    private function hookExists(ProcessWire $wire, $hookedMethod, $fromObject, $type)
    {
        list($class, $method) = explode('::', $hookedMethod);

        $regexHookId = "/^${class}:.*:{$method}$/";

        $hooks = array_filter($wire->getHooks('*'),
            function ($hook) use ($regexHookId, $fromObject, $type) {
                return preg_match($regexHookId, $hook['id'])
                    && $hook['toObject'] instanceof $fromObject
                    && $hook['options'][$type] === true;
            });

        return count($hooks) > 0;
    }

    /**
     * @throws \ProcessWire\WireException
     * @throws \ProcessWire\WirePermissionException
     *
     * @return \ProcessWire\ProcessWire
     */
    private function bootstrapProcessWire()
    {
        $rootPath = __DIR__ . '../../../../../../';
        $config = ProcessWire::buildConfig($rootPath);
        $wire = new ProcessWire($config);

        // Make sure that the module's ready() method is not called by ProcessPageView::execute().
        // We manually call this method to increase testability.
        $wire->addHookBefore('ProcessWire::ready', function (HookEvent $event) {
            $event->replace = true;
        });

        $process = $wire->modules->get('ProcessPageView');
        $wire->wire('process', $process);
        $process->execute(false);

        return $wire;
    }
}
