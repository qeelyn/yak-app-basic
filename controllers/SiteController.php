<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/21
 * Time: 下午2:14
 */

namespace app\controllers;

use app\modules\ucenter\models\TripartiteRegisterForm;
use Yii;
use yii\base\UserException;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\Controller;

/**
 * 站点的主控制器
 * @package app\controllers
 */
class SiteController extends Controller
{
    public function actions()
    {
        return array(
            // captcha action renders the CAPTCHA image displayed on the contact page
            'captcha' => array(
                'class' => 'yii\captcha\CaptchaAction',
                'backColor' => 0xFFFFFF,  //背景颜色
                'minLength' => 4,  //最短为4位
                'maxLength' => 4,   //是长为4位
                'transparent' => true,  //显示为透明
                'fixedVerifyCode' => YII_ENV_DEV ? 'test' : null,
            ),
            'error'=>[
                'class'=> 'app\components\ErrorAction'
            ],
        );
    }
}