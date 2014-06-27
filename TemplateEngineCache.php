<?php

/**
 * TemplateEngineCache
 *
 * Template engines extending from class 'TemplateEngine' that support caching must implement the methods
 * from this interface. The TemplateEngineFactory module takes care of clearing the cache whenever pages
 * are modified or deleted.
 *
 * @author Stefan Wanzenried <stefan.wanzenried@gmail.com>
 *
 * ProcessWire 2.x
 * Copyright (C) 2014 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 *
 */

interface TemplateEngineCache {

    /**
     * Get cached output of template or null if no cache exists
     *
     * @return string|null
     */
    public function getCache();


    /**
     * Cache output of current template
     *
     */
    public function storeCache();


    /**
     * Clear cache of current template
     *
     */
    public function clearCache();


    /**
     * Clear cache completely, also cache of all other templates
     *
     */
    public function clearAllCache();


    /**
     * Returns true if a cache exists for the template
     *
     * @return bool
     */
    public function isCached();
}