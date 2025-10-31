<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Base Controller
 *
 * Parent controller for all API controllers
 * Provides common response methods and utilities
 */
class BaseController extends ResourceController
{
    /**
     * Response format
     *
     * @var string
     */
    protected $format = 'json';

    /**
     * Initialize controller
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
    }
}
