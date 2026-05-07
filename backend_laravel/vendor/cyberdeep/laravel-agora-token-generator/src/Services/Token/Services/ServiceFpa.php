<?php
namespace CyberDeep\LaravelAgoraTokenGenerator\Services\Token\Services;

use CyberDeep\LaravelAgoraTokenGenerator\Services\Token\Util;

class ServiceFpa extends Service
{
    const SERVICE_TYPE = 4;
    const PRIVILEGE_LOGIN = 1;

    public function __construct()
    {
        parent::__construct(self::SERVICE_TYPE);
    }

    public function pack()
    {
        return parent::pack();
    }

    public function unpack(&$data)
    {
        parent::unpack($data);
    }
}
