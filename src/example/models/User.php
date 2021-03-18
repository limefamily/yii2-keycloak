<?php

namespace limefamily\OpenIdConnect\example\models;
use yii\base\BaseObject;
use yii\web\IdentityInterface;

/**
 * @property integer $id
 * @property string $username
 * @property string $sys_code
 */
class User extends BaseObject implements IdentityInterface
{
    private static $users = [
        '100' => [
            'id' => '100',
            'username' => 'alice',
            'password' => 'alice',
            'authKey' => 'test100key',
            'accessToken' => '100-token',
        ],
        '101' => [
            'id' => '101',
            'username' => 'bob',
            'password' => 'bob',
            'authKey' => 'test101key',
            'accessToken' => '101-token',
        ],
    ];
    public $id;
    public $username;
    public $authKey;
    public $token;
    public $email;


    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return isset(self::$users[$id]) ? new static(self::$users[$id]) : null;
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return User|null
     */
    public static function findByUsername($username)
    {
        foreach (self::$users as $user) {
            if (strcasecmp($user['username'], $username) === 0) {
                return new static($user);
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        // TODO: Implement findIdentityByAccessToken() method.
    }
}
