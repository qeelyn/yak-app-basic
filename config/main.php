<?php

use yii\web\Response;

$params = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);
$db = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/db.php'),
    require(__DIR__ . '/db-local.php')
);

$config = [
    'id' => 'yak.web',
    'params' => $params,
    'basePath' => dirname(__DIR__),
    'language'=>'en',
    'sourceLanguage'=>'zh-CN',
    'timeZone'=>'Asia/Shanghai',
    'on beforeAction'=>['app\components\SiteEvent','beforeAction'],
    'on beforeRequest'=> ['app\components\SiteEvent','beforeRequest'],
    'bootstrap' => [
        'log',
        [
            'class' => 'yii\filters\ContentNegotiator',
            'formats' => [
                'text/html' => Response::FORMAT_HTML,
                'application/json' => Response::FORMAT_JSON,
                'application/xml' => Response::FORMAT_XML,
            ],
            'languages' => [
                'en',
                'zh-CN',
            ],
        ],
    ],
    'components' => [
        'session' => [
            'class'=>'yii\web\CacheSession',
            'cache'=>'cache',
        ],
        'db' => $db,
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'enableCookieValidation' => true,
            'enableCsrfValidation' => true,
            'parsers'=>[
                'application/json' => 'yii\web\JsonParser'
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
            'keyPrefix' => 'yak',
        ],
        'response'=> [
            'format'=>\yii\web\Response::FORMAT_HTML,
        ],
        //异常
        'errorHandler' => [
            'errorAction' => !YII_DEBUG ? 'site/error' : null,
        ],
        //发邮件
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
            ],
            'messageConfig'=>[
                'charset'=>'UTF-8',
            ]
        ],
        'schemaCache'=> [
            'class' => 'yii\caching\FileCache',
            'cachePath'=>'@runtime/schema',
            'directoryLevel'=> 0,
        ],
        'user' => [
            'identityClass' => 'app\components\User',
//            'loginUrl' => ['/ucenter/login/index'],
            'on afterLogin' => ['app\components\User', 'onAfterLogin'],
            'on afterLogout' => ['app\components\User', 'onAfterLogout'],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
        ],
        'i18n'=>[
            'translations'=>[
                'app' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'fileMap'=>[
                        '*'=>'app.php',
                    ],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => require (__DIR__ . '/routes.php'),
        ],
        'authManager' => [
            'class' => 'yak\framework\rbac\DbManager',
        ],
        'assetManager' => [
            'appendTimestamp'=>true,
        ],
    ],
    'modules'=>[
    ],
];
return $config;