<?php

return [
    'signatures' => [
        'enabled' => true,
        'timeout' => 300,
        'apps' => [
            'app_001' => 'secret_key_001',
            'app_002' => 'secret_key_002',
        ],
    ],

    'excludes' => [
        'api/column/list',
        'api/column/tree',
        'api/article/list',
        'api/article/recommend',
        'api/article/hot',
        'api/product/list',
        'api/product/recommend',
        'api/images/list',
        'api/video/list',
        'api/download/list',
        'api/other/slides',
        'api/other/links',
        'api/other/tags',
        'api/other/nav',
        'api/other/config',
        'api/search',
    ],
];