<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/21
 * Time: 上午11:46
 */

namespace app\components;

use ArrayAccess;
use yak\framework\base\BaseUser;
use yak\oauth2server\components\OAuthServer;
use yak\oauth2server\helper\TokenHelper;
use yak\oauth2server\models\AccessToken;
use yak\oauth2server\Module;
use Yii;
use yii\web\IdentityInterface;
use yii\web\UserEvent;

/**
 * 用户上下文信息
 * @property array $organizations 顶级组织信息,一个用户可能属于多个顶级组织
 * @property array $currentOrganization 当前顶级组织信息,当用户发生组织切换时,需要变更
 * @property array $departOrganization 部门级别组织信息
 * @package yak\platform\components
 */
class User extends BaseUser
{
    /**
     * 在cookie环境中根据给到的ID查询身份。
     *
     * @param string|integer $id 被查询的ID
     * @return IdentityInterface|null 通过ID匹配到的身份对象
     */
    public static function findIdentity($id)
    {
        $key = Yii::$app->params['identity_prefix'] . $id;
        $user = Yii::$app->cache->get($key);
        if($user){
            return self::createUser($user);
        }
        $userInfo = \yak\ucenter\models\ar\User::findOne(['id'=>$id]);
        if(!$userInfo){
            return null;
        }
        $user = [
            'id' => $userInfo['id'],
            'avatar' => $userInfo['avatar'],
            'nickname' => $userInfo['nickname'],
            'auth_key' => '',
        ];
        //角色分配
        $user['assignments'] = Yii::$app->getAuthManager()->getAssignments($userInfo['id']);
        /**
         * @var $orgs Organization[]
         */
        $orgs = Yii::$app->getAuthManager()->getOrganizations($userInfo['id']);
        $user['organizations'] = $orgs;
        if (count($orgs) == 1) {
            $user['currentOrganizationId'] = reset($orgs)['id'];
        }
        $duration = Yii::$app->params['identity_cache_duration'] ?? 3600;
        Yii::$app->cache->set($key, $user,$duration);
        return self::createUser($user);
    }

    /**
     * 根据 token 查询身份。
     *
     * @param string $token 被查询的 token
     * @return IdentityInterface|null 通过 token 得到的身份对象
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $attrData = Yii::$app->cache->get($token);
        if($attrData && !isset($attrData['token_type'])){
            return self::createUser($attrData);
        }
        /** @var Module $module */
        $module = Yii::$app->getModule('oauth2server');
        if ($module) {
            /** @var Module $module */
            $module = Yii::$app->getModule('oauth2server');
            $reqeust = $module->getOAuthServer()->getResourceServer()->validateAuthenticatedRequest($module->getOAuthServer()->getRequest());
            $userId = $reqeust->getAttribute('oauth_user_id');
            return self::findIdentity($userId);
        }
        return null;
    }

    private static function createUser($data = [],$config = [])
    {
        return new static($config,$data);
    }

    /**
     *
     * 登陆成功后设置auth_key及缓存
     *
     * @param UserEvent $userEvent
     * BaseUser $identity the user identity information
     * bool $cookieBased whether the login is cookie-based
     * int $duration number of seconds that the user can remain in logged-in status.
     * If 0, it means login till the user closes the browser or the session is manually destroyed.
     */
    public static function onAfterLogin($userEvent)
    {
        /** @var BaseUser $identity */
        $identity = $userEvent->identity;
        $uid = $identity->getId();
        $duration = Yii::$app->params['identity_cache_duration'] ?? 3600;
        $identity['auth_key'] = md5(Yii::$app->params['identity_prefix'] . $uid . time());
        $key = $identity['auth_key'];
        Yii::$app->cache->set($key, $identity->getAttributes(),$duration);
    }

    public static function onAfterLogout($userEvent)
    {
        /** @var BaseUser $identity */
        $identity = $userEvent->identity;
        $key = Yii::$app->params['identity_prefix'] . $identity->getId();
        Yii::$app->cache->delete($key);
    }

}