<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Stores the default settings for the ContentSecurityPolicy, if you
 * choose to use it. The values here will be read in and set as defaults
 * for the site. If needed, they can be overridden on a page-by-page basis.
 *
 * Suggested reference for explanations:
 * @see https://www.html5rocks.com/en/tutorials/security/content-security-policy/
 */
class ContentSecurityPolicy extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Report Only
     * --------------------------------------------------------------------------
     *
     * Specifies whether the Content-Security-Policy header should be in
     * "report-only" mode, which allows you to test a policy without enforcing it.
     *
     * Default: false (enforcing mode)
     */
    public bool $reportOnly = false;

    /**
     * --------------------------------------------------------------------------
     * Default Source
     * --------------------------------------------------------------------------
     *
     * The default source list for unspecified directives. This is used as a
     * catch-all for source types that are not explicitly defined.
     */
    public ?string $defaultSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Script Source
     * --------------------------------------------------------------------------
     *
     * Lists allowed sources of JavaScript. This includes not only URLs loaded
     * directly into script src attributes, but also things like inline script
     * event handlers and XSLT stylesheets which can trigger script execution.
     */
    public ?string $scriptSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Style Source
     * --------------------------------------------------------------------------
     *
     * Lists allowed sources of stylesheets.
     */
    public ?string $styleSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Image Source
     * --------------------------------------------------------------------------
     *
     * Defines the origins from which images can be loaded.
     */
    public ?string $imageSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Base URI
     * --------------------------------------------------------------------------
     *
     * Restricts the URLs that can appear in a page's <base> element.
     */
    public ?string $baseURI = null;

    /**
     * --------------------------------------------------------------------------
     * Child Source
     * --------------------------------------------------------------------------
     *
     * Lists the URLs for workers and embedded frame contents.
     */
    public ?string $childSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Connect Source
     * --------------------------------------------------------------------------
     *
     * Limits the origins to which you can connect (via XHR, WebSockets, EventSource).
     */
    public ?string $connectSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Font Source
     * --------------------------------------------------------------------------
     *
     * Specifies the origins that can serve web fonts.
     */
    public ?string $fontSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Form Action
     * --------------------------------------------------------------------------
     *
     * Lists valid endpoints for form submission.
     */
    public ?string $formAction = null;

    /**
     * --------------------------------------------------------------------------
     * Frame Ancestors
     * --------------------------------------------------------------------------
     *
     * Specifies the sources that can embed the current page.
     */
    public ?string $frameAncestors = null;

    /**
     * --------------------------------------------------------------------------
     * Frame Source
     * --------------------------------------------------------------------------
     */
    public ?string $frameSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Media Source
     * --------------------------------------------------------------------------
     *
     * Restricts the origins allowed to deliver video and audio.
     */
    public ?string $mediaSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Manifest Source
     * --------------------------------------------------------------------------
     */
    public ?string $manifestSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Object Source
     * --------------------------------------------------------------------------
     *
     * Allows control over Flash and other plugins.
     */
    public ?string $objectSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Plugin Types
     * --------------------------------------------------------------------------
     */
    public ?string $pluginTypes = null;

    /**
     * --------------------------------------------------------------------------
     * Report URI
     * --------------------------------------------------------------------------
     */
    public ?string $reportURI = null;

    /**
     * --------------------------------------------------------------------------
     * Sandbox
     * --------------------------------------------------------------------------
     */
    public ?string $sandbox = null;

    /**
     * --------------------------------------------------------------------------
     * Upgrade Insecure Requests
     * --------------------------------------------------------------------------
     */
    public bool $upgradeInsecureRequests = false;

    /**
     * --------------------------------------------------------------------------
     * Style Nonce Tag
     * --------------------------------------------------------------------------
     */
    public string $styleNonceTag = '{csp-style-nonce}';

    /**
     * --------------------------------------------------------------------------
     * Script Nonce Tag
     * --------------------------------------------------------------------------
     */
    public string $scriptNonceTag = '{csp-script-nonce}';

    /**
     * --------------------------------------------------------------------------
     * Auto Nonce
     * --------------------------------------------------------------------------
     */
    public bool $autoNonce = true;
}
