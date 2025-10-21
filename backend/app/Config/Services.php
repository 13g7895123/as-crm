<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use App\Libraries\JWT;

/**
 * Services Configuration
 *
 * 註冊自訂服務
 */
class Services extends BaseService
{
    /**
     * JWT Service
     *
     * @param bool $getShared
     *
     * @return JWT
     */
    public static function jwt(bool $getShared = true): JWT
    {
        if ($getShared) {
            return static::getSharedInstance('jwt');
        }

        return new JWT();
    }
}
