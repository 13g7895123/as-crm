<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Routing configuration
 */
class Routing extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Default Namespace
     * --------------------------------------------------------------------------
     *
     * Default namespace to use for controllers if none is specified.
     */
    public string $defaultNamespace = 'App\Controllers';

    /**
     * --------------------------------------------------------------------------
     * Default Controller
     * --------------------------------------------------------------------------
     *
     * When no controller or method is explicitly specified in a route, this
     * controller will be used as the fallback.
     */
    public string $defaultController = 'Home';

    /**
     * --------------------------------------------------------------------------
     * Default Method
     * --------------------------------------------------------------------------
     *
     * If a controller is found but the method is not, this method will be
     * called instead.
     */
    public string $defaultMethod = 'index';

    /**
     * --------------------------------------------------------------------------
     * Translate URI Dashes
     * --------------------------------------------------------------------------
     *
     * When this option is true, underscores in controller and method URI
     * segments will automatically be replaced by dashes when determining
     * which method to use.
     */
    public bool $translateURIDashes = false;

    /**
     * --------------------------------------------------------------------------
     * Use Auto Routing (Improved)
     * --------------------------------------------------------------------------
     *
     * If true, the system will attempt to match the URI against controllers
     * by matching each segment against folders/files in APPPATH/Controllers.
     *
     * NOTE: This option is only available for backward compatibility.
     * AutoRouting(Legacy) will be removed in a future release.
     */
    public bool $autoRoute = false;

    /**
     * --------------------------------------------------------------------------
     * Auto Routing (Improved)
     * --------------------------------------------------------------------------
     *
     * If true, you can use auto-routing (improved version). This is the default.
     */
    public bool $autoRouteImproved = false;

    /**
     * --------------------------------------------------------------------------
     * Use 404 Override
     * --------------------------------------------------------------------------
     *
     * Determines what should happen when the requested page is not found.
     * When true, the system will display a generic 404 view.
     * When false, a RuntimeException will be thrown.
     */
    public bool $override404 = false;

    /**
     * --------------------------------------------------------------------------
     * 404 Override Controller
     * --------------------------------------------------------------------------
     */
    public ?string $override404Controller = null;

    /**
     * --------------------------------------------------------------------------
     * 404 Override Method
     * --------------------------------------------------------------------------
     */
    public ?string $override404Method = null;

    /**
     * --------------------------------------------------------------------------
     * Priority List
     * --------------------------------------------------------------------------
     *
     * Defines route priority. When defined, routes in these files will be
     * loaded in the order they appear before other routes from the 'routes'
     * file.
     */
    public array $prioritize = [];

    /**
     * --------------------------------------------------------------------------
     * Modules List
     * --------------------------------------------------------------------------
     */
    public array $moduleRoutes = [];

    /**
     * --------------------------------------------------------------------------
     * Route Files
     * --------------------------------------------------------------------------
     *
     * The route files to be automatically loaded by the system. By default,
     * the system will load the routes file located at app/Config/Routes.php.
     */
    public array $routeFiles = [
        APPPATH . 'Config/Routes.php',
    ];
}
