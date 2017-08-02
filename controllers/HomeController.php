<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/12/21
 * Time: 上午11:27
 */

namespace app\controllers;


use yii\web\Controller;

class HomeController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
}