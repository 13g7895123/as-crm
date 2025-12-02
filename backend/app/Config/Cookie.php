<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use DateTimeInterface;

/**
 * Cookie Configuration
 */
class Cookie extends BaseConfig
{
    /**
     * Cookie Prefix
     *
     * @var string
     */
    public string $prefix = '';

    /**
     * Cookie Domain
     *
     * @var string
     */
    public string $domain = '';

    /**
     * Cookie Path
     *
     * @var string
     */
    public string $path = '/';

    /**
     * Cookie Secure
     *
     * @var bool
     */
    public bool $secure = false;

    /**
     * Cookie HTTPOnly
     *
     * @var bool
     */
    public bool $httponly = true;

    /**
     * Cookie SameSite
     *
     * @var string
     */
    public string $samesite = 'Lax';

    /**
     * Cookie Raw
     *
     * @var bool
     */
    public bool $raw = false;

    /**
     * Cookie Expires
     *
     * @var int|DateTimeInterface
     */
    public int|DateTimeInterface $expires = 0;
}
