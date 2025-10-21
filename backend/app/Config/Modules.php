<?php

namespace Config;

use CodeIgniter\Modules\Modules as BaseModules;

class Modules extends BaseModules
{
    /**
     * --------------------------------------------------------------------------
     * Enable Auto-Discovery?
     * --------------------------------------------------------------------------
     *
     * If true, then auto-discovery will happen across all elements listed in
     * $activeExplorers below. If false, no auto-discovery will happen at all,
     * giving a slight performance boost.
     */
    public $discoverInComposer = true;

    /**
     * --------------------------------------------------------------------------
     * Auto-Discovery Rules
     * --------------------------------------------------------------------------
     *
     * Lists the aliases of all discovery classes that will be active
     * and used during the current application request.
     *
     * If it is not listed, only the base application elements will be used.
     */
    public $aliases = [
        'events',
        'filters',
        'registrars',
        'routes',
        'services',
    ];

    /**
     * --------------------------------------------------------------------------
     * Composer Packages Discovery
     * --------------------------------------------------------------------------
     *
     * This setting allows you to enable or disable discovery for specific
     * Composer packages. By default, this is disabled for performance reasons.
     *
     * Example:
     *   $composerPackages = [
     *       'only' => [
     *          // List the packages to discover
     *           'codeigniter4/shield',
     *       ],
     *   ];
     */
    public $composerPackages = [];
}
