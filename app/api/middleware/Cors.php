<?php

declare(strict_types=1);

namespace app\api\middleware;

use think\middleware\AllowCrossDomain;

class Cors extends AllowCrossDomain
{
    protected $header = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With, Accept, Origin',
        'Access-Control-Expose-Headers' => 'Content-Length, Content-Type',
        'Access-Control-Max-Age' => '86400',
        'Access-Control-Allow-Credentials' => 'true'
    ];
}