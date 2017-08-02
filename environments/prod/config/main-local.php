<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/12/2
 * Time: 下午1:58
 */
$config = [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '',
        ],
        'cache' => [
            'class' => 'yii\caching\MemCache',
            'useMemcached' => true,
            'servers' => [
                [
                    'host' => '',
                    'port' => '',
                    'weight' => 100,
                ]
            ],
            'keyPrefix' => 'yak',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'maxFileSize'=> 200,
                    'levels' => ['error', 'warning'],
                    'logVars' => [],
                    'logFile' => '@runtime/logs/'.date('ymd').'.log',
                ],
            ],
        ],
    ]
];

return $config;