<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/21
 * Time: 上午11:46
 */

namespace yakunit;

use ArrayAccess;
use Yii;
use yii\web\IdentityInterface;

/**
 * 用户上下文信息
 * @property array $organizations 顶级组织信息,一个用户可能属于多个顶级组织
 * @property array $currentOrganization 当前顶级组织信息,当用户发生组织切换时,需要变更
 * @property array $departOrganization 部门级别组织信息
 * @package yak\platform\components
 */
class ContextUser implements IdentityInterface, ArrayAccess
{
    /**
     * ContextUser constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->attributes = $data;
    }


    protected $attributes = [];

    /**
     * 根据给到的ID查询身份。
     *
     * @param string|integer $id 被查询的ID
     * @return IdentityInterface|null 通过ID匹配到的身份对象
     */
    public static function findIdentity($id)
    {
        $user = Yii::$app->cache->get(Yii::$app->params['identity_prefix'] . $id);
        if ($user) {
            return new ContextUser($user);
        }
        return null;
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
        if ($attrData) {
            return new ContextUser($attrData);
        }
        return null;
    }

    /**
     * @return int|string 当前用户ID
     */
    public function getId()
    {
        return $this->attributes['id'];
    }

    /**
     * @return string 当前用户的（cookie）认证密钥
     */
    public function getAuthKey()
    {
        return $this->attributes['auth_key'];
    }

    /**
     * @param string $authKey
     * @return boolean if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * 缓存用户上下文数据
     */
    public function cacheIdentity($key)
    {
        Yii::$app->cache->set($key, $this->attributes);
    }

    /**
     * 获取当前组织id;
     * @return mixed
     */
    public function getOrganizations()
    {
        return $this->attributes['organizations'] ?? [];
    }

    /**
     * 获取当前组织id;
     * @return mixed
     */
    public function getCurrentOrganization()
    {
        return $this->attributes['currentOrganization'] ?? [];
    }

    /**
     * 获取用户角色
     */
    public function getRoles()
    {
        return $this->attributes['roles'];
    }

    //below is implement method as array
    public function getAttributes()
    {
        return $this->attributes;
    }

    public function __get($key)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$key]) ? $attributes[$key] : null;
    }

    public function __isset($name)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$name]);
    }


    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function __unset($name)
    {
        unset($this->attributes[$name]);
    }

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }


}