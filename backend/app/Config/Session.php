<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\BaseHandler;
use CodeIgniter\Session\Handlers\FileHandler;

/**
 * Session Configuration
 */
class Session extends BaseConfig
{
    /**
     * Session Driver
     *
     * @var string
     */
    public string $driver = FileHandler::class;

    /**
     * Session Cookie Name
     *
     * @var string
     */
    public string $cookieName = 'ci_session';

    /**
     * Session Expiration
     *
     * @var int
     */
    public int $expiration = 7200;

    /**
     * Session Save Path
     *
     * @var string
     */
    public string $savePath = WRITEPATH . 'session';

    /**
     * Session Match IP
     *
     * @var bool
     */
    public bool $matchIP = false;

    /**
     * Session Time to Update
     *
     * @var int
     */
    public int $timeToUpdate = 300;

    /**
     * Session Regenerate Destroy
     *
     * @var bool
     */
    public bool $regenerateDestroy = false;

    /**
     * Session Database Group
     *
     * @var string|null
     */
    public ?string $DBGroup = null;
}
