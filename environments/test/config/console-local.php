<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/12/29
 * Time: 下午3:21
 */

return [
    'controllerMap' => [
        'migrate' => [
            'db'=>[
                'class' => 'yii\db\Connection',
                'dsn' => 'mysql:host=localhost:3306;dbname=yak',
                'username' => '',
                'password' => '',
            ],
            //'migrationPath' => null, // allows to disable not namespaced migration completely
        ],
    ],
];