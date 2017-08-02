<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/2/22
 * Time: 下午5:03
 */
return [
    'yak' => [
        'class'=>'yii\swoole\server\HttpServer',
        'setting' => [
//            'daemonize'=>1,
            'reactor_num'=>1,
            'worker_num'=>1,
            'task_worker_num'=>1,
            'pid_file' => __DIR__ . '/../runtime/yak.pid',
            'log_file' => __DIR__.'/../runtime/logs/swoole.log',
            'debug_mode'=> 1,
            'user'=>'tsingsun',
            'group'=>'staff',
        ],
    ],
];