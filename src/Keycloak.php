<?php

namespace limefamily\OpenIdConnect;
use yii\authclient\Collection;
use yii\authclient\InvalidResponseException;
use Yii;
use yii\authclient\OAuthToken;
use yii\authclient\OpenIdConnect;
use yii\base\InvalidConfigException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\redis\Connection;


/**
 * Keycloak
 * @package limefamily\OpenIdConnect
 * @property string $logoutUrl
 * @property Connection $redis
 */

class Keycloak extends OpenIdConnect
{
    public $logoutUrl;
    public $redis;
    const SESSION_STATE_KEY_PREFIX = 'session_state_';

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (empty($this->redis)) {
            throw new InvalidConfigException("redis must be set");
        }
        if (empty($this->logoutUrl)) {
            throw new InvalidConfigException('logoutUrl must be set');
        }
        if (!($this->redis instanceof Connection)) {
            $this->redis = Yii::$app->get($this->redis);
        }
        parent::init();
    }

    /**
     * Initializes authenticated user attributes.
     * @return array|null
     * @throws HttpException
     */
    protected function initUserAttributes()
    {
        $token = $this->getAccessToken()->getToken();
        return $this->loadJws($token);
    }

    protected function defaultName()
    {
        return 'keycloak';
    }
    protected function defaultTitle()
    {
        return 'Keycloak';
    }
    public static function refreshToken(){
        if (!Yii::$app->user->getIsGuest()) {
            $client = self::getInstance();
            if ($client->getSsoSession() != null) {
                try {
                    if ($client->getAccessToken()->getIsExpired()) {
                        $client->refreshAccessToken($client->getAccessToken());
                    }
                }catch (InvalidResponseException $e) {
                    $client->removeSsoSession();
                    Yii::$app->user->logout();
                }
            } else {
                Yii::$app->user->logout();
            }
        }
    }
    private static function getSessionStateKey($sessionState) {
        return self::SESSION_STATE_KEY_PREFIX . $sessionState;
    }
    private function removeSsoSession() {
        $state = $this->getSessionState();
        if (!empty($state)) {
            $this->redis->del(self::getSessionStateKey($state));
        }
    }
    private function getSsoSession() {
        $state = $this->getSessionState();
        if (!empty($state)) {
            return $this->redis->get(self::getSessionStateKey($state));
        }
        return null;
    }

    public function setSsoSession() {
        $state = $this->getSessionState();
        if (!empty($state)) {
            $this->redis->set(self::getSessionStateKey($state), Yii::$app->session->getId());
        }
    }

    public function getSessionState(){
        /** @var OAuthToken $token */
        $token = $this->getState('token');
        if (!empty($token)) {
            return $token->getParam('session_state');
        }else{
           return null; 
        }
    }

    /**
     * @return Keycloak
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     */
    public static function getInstance() {
        /* @var $collection Collection */
        $collection = Yii::$app->get('authClientCollection');
        if (!$collection->hasClient('keycloak')) {
            throw new NotFoundHttpException("Unknown auth client 'keycloak");
        }

        /** @var Keycloak $client */
        $client = $collection->getClient('keycloak');
        return $client;
    }

    public static function logout($redirect) {
        if (!Yii::$app->user->getIsGuest()) {
            $client = self::getInstance();
            $client->removeSsoSession();
            $logoutUrl = $client->logoutUrl. "?client_id=".$client->clientId."&redirect_uri=". urlencode($redirect);
            Yii::$app->user->logout();
            return Yii::$app->response->redirect($logoutUrl);
        }
        return Yii::$app->response->redirect($redirect);
    }

    /**
     * @param $token
     * @return array
     * @throws HttpException
     */
    public function loadAndVerifyLogoutToken($token) {
        return $this->loadJws($token);
    }
}
