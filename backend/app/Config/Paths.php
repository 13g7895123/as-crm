<?php

namespace Config;

/**
 * Paths Configuration
 *
 * Holds the paths that are used by the system to
 * locate the main directories, app, system, etc.
 *
 * Modifying these allows you to re-structure your application,
 * share a system folder between multiple applications, and more.
 */
class Paths
{
    /**
     * ---------------------------------------------------------------
     * SYSTEM DIRECTORY NAME
     * ---------------------------------------------------------------
     *
     * This variable must contain the name of your "system" directory.
     * Set the path if it is not in the same directory as this file.
     */
    public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    /**
     * ---------------------------------------------------------------
     * APPLICATION DIRECTORY NAME
     * ---------------------------------------------------------------
     *
     * If you want this front controller to use a different "app"
     * directory than the default one you can set its name here. The directory
     * can also be renamed or relocated anywhere on your server. If you do,
     * use an absolute (full) server path.
     * For more info please see the user guide:
     *
     * https://codeigniter.com/user_guide/general/managing_apps.html
     */
    public string $appDirectory = __DIR__ . '/..';

    /**
     * ---------------------------------------------------------------
     * WRITABLE DIRECTORY NAME
     * ---------------------------------------------------------------
     *
     * This variable must contain the name of your "writable" directory.
     * The writable directory allows you to group all directories that
     * need write permission to a single place that can be tucked away
     * for maximum security, keeping it out of the app and/or
     * system directories.
     */
    public string $writableDirectory = __DIR__ . '/../../writable';

    /**
     * ---------------------------------------------------------------
     * TESTS DIRECTORY NAME
     * ---------------------------------------------------------------
     *
     * This variable must contain the name of your "tests" directory.
     */
    public string $testsDirectory = __DIR__ . '/../../tests';

    /**
     * ---------------------------------------------------------------
     * VIEW DIRECTORY NAME
     * ---------------------------------------------------------------
     *
     * This variable must contain the name of the directory that
     * contains the view files used by your application. By
     * default, this is in `app/Views`. This value
     * is used when no value is provided to `Services::renderer()`.
     */
    public string $viewDirectory = __DIR__ . '/../Views';
}
