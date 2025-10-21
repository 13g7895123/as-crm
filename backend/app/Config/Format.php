<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Format\FormatterInterface;

/**
 * Format configuration
 */
class Format extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Available Response Formats
     * --------------------------------------------------------------------------
     *
     * When you perform content negotiation with the request, these are the
     * available formats that your application supports. This is currently
     * only used with the API\ResponseTrait.
     */
    public array $supportedResponseFormats = [
        'application/json',
        'application/xml', // machine-readable XML
        'text/xml',        // human-readable XML
    ];

    /**
     * --------------------------------------------------------------------------
     * Formatters
     * --------------------------------------------------------------------------
     *
     * Lists the class names of handlers that can be used to format responses.
     * These are the built-in handlers, but you can easily add your own by
     * using the array syntax, as shown in the comments below.
     */
    public array $formatters = [
        'application/json' => 'CodeIgniter\Format\JSONFormatter',
        'application/xml'  => 'CodeIgniter\Format\XMLFormatter',
        'text/xml'         => 'CodeIgniter\Format\XMLFormatter',
    ];

    /**
     * --------------------------------------------------------------------------
     * Formatters Options
     * --------------------------------------------------------------------------
     *
     * Additional Options for the individual formatters.
     */
    public array $formatterOptions = [
        'application/json' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        'application/xml'  => 0,
        'text/xml'         => 0,
    ];
}
