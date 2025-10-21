<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    /**
     * Base Site URL
     */
    public string $baseURL = 'http://localhost:8080/';

    /**
     * Allowed Hostname
s
     */
    public array $allowedHostnames = [];

    /**
     * Index File
     */
    public string $indexPage = '';

    /**
     * URI Protocol
     */
    public string $uriProtocol = 'REQUEST_URI';

    /**
     * Default Locale
     */
    public string $defaultLocale = 'zh-TW';

    /**
     * Negotiate Locale
     */
    public bool $negotiateLocale = false;

    /**
     * Supported Locales
     */
    public array $supportedLocales = ['zh-TW', 'en'];

    /**
     * Application Timezone
     */
    public string $appTimezone = 'Asia/Taipei';

    /**
     * Default Character Set
     */
    public string $charset = 'UTF-8';

    /**
     * Force Global Secure Requests
     */
    public bool $forceGlobalSecureRequests = false;

    /**
     * Session Variables
     */
    public array $proxyIPs = [];

    /**
     * CSRF Protection Configuration
     */
    public string $CSRFTokenName = 'csrf_token';
    public string $CSRFHeaderName = 'X-CSRF-TOKEN';
    public string $CSRFCookieName = 'csrf_cookie';
    public int    $CSRFExpire = 7200;
    public bool   $CSRFRegenerate = true;
    public bool   $CSRFRedirect = false;
    public string $CSRFSameSite = 'Lax';

    /**
     * Cookie Settings
     */
    public string $cookiePrefix = '';
    public string $cookieDomain = '';
    public string $cookiePath = '/';
    public bool   $cookieSecure = false;
    public bool   $cookieHTTPOnly = true;
    public ?string $cookieSameSite = 'Lax';

    /**
     * Reverse Proxy IPs
     */
    public bool $proxyFilterByIPAddress = false;

    /**
     * --------------------------------------------------------------------------
     * Security Header Configuration
     * --------------------------------------------------------------------------
     */
    public bool $CSPEnabled = false;
}
