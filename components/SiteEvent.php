<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/21
 * Time: 下午2:32
 */

namespace app\components;

use tsingsun\log\AccessLog;
use Yii;
use yii\base\ActionEvent;
/**
 * the yak site event class
 * @package app\components\events
 */
class SiteEvent
{
    /**
     * @param ActionEvent $event
     */
    public static function beforeAction($event)
    {
        $accessToken = Yii::$app->getRequest()->getQueryParam('access_token');
        if ($accessToken) {
            //存在access_token,表示为api接口请求
            //1.关闭session
            Yii::$app->user->enableSession = false;
        }
    }

    public static function beforeRequest($event){
        $al = new AccessLog();
        Yii::$app->set('accessLog',$al);
        Yii::getLogger()->log($al,AccessLog::LEVEL_ACCESS);
    }
}