<?php

return [
    'default' => [
        'limit' => 60,
        'window' => 60,
        'message' => 'Rate limit exceeded, please try again later'
    ],

    'search' => [
        'limit' => 30,
        'window' => 60,
        'message' => 'Search rate limit exceeded'
    ],

    'detail' => [
        'limit' => 100,
        'window' => 60,
        'message' => 'Too many requests'
    ],

    'list' => [
        'limit' => 60,
        'window' => 60,
        'message' => 'List rate limit exceeded'
    ],
];