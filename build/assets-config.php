<?php
/**
 * Configuration file for the "yii asset" console command.
 * 
 *  php yii asset build/assets.php config/assets-dev.php

 */

// In the console environment, some path aliases may not exist. Please define these:
Yii::setAlias('@web', __DIR__ . '/../web');

return [
    // Adjust command/callback for JavaScript files compressing:
    'jsCompressor' => 'gulp compress-js --gulpfile gulpfile.js --src {from} --dist {to}',
    // Adjust command/callback for CSS files compressing:
    'cssCompressor' => 'gulp compress-css --gulpfile gulpfile.js --src {from} --dist {to}',
    // Whether to delete asset source after compression:
    'deleteSource' => false,
    // The list of asset bundles to compress:
    'bundles' => [
        'app\assets\AppAsset',
    ],
    // Asset bundle for compression output:
    'targets' => [
        'all' => [
            'class' => 'yii\web\AssetBundle',
            'basePath' => '@web/assets',
            'baseUrl' => '@web/assets',
            'js' => 'scripts.js',
            'css' => 'styles.css',
            'depends' => [],
        ],
    ],
    // Asset manager configuration:
    'assetManager' => [
        'basePath' => '@web/assets',
        'baseUrl' => '@web/assets',
        'bundles' => [
            'yii\web\JqueryAsset' => [
                'sourcePath' => null,   // do not publish the bundle
                'js' => [
                    'https://cdn.bootcss.com/jquery/3.2.1/jquery.js',
                ]
            ],
            //在此处配置静态资源具体配置
        ],
    ],
    //在此处配置静态资源的资源类
    'statics'=>[
    ],
];