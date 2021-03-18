<?php

namespace limefamily\OpenIdConnect\example\controllers;

use limefamily\OpenIdConnect\example\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use limefamily\OpenIdConnect\Keycloak;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'except' => ['auth', 'error',],
                'rules' => [
                    [
                        'actions' => ['index','logout', 'about',],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'auth' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'onAuthSuccess'],
            ],
        ];
    }

    /**
     * @param Keycloak $client
     * @throws ForbiddenHttpException
     */
    public function onAuthSuccess($client)
    {
        $attributesMap = [
            'preferred_username' => 'username',
            'email' => 'email'
        ];
        $attributes = $client->getUserAttributes();

        $userAttributes= [];
        foreach ($attributesMap as $key => $value) {
            if (!isset($attributes[$key])) {
                throw new ForbiddenHttpException("token缺少必要的属性：{$key}" );
            }
            $userAttributes[$value] = $attributes[$key];
        }

        $user = new User($userAttributes);
        Yii::$app->user->login($user);

        $client->setSsoSession();
    }
    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
    public function actionAbout() {
        return $this->render('about');
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        return Keycloak::logout(Yii::$app->urlManager->createAbsoluteUrl(Yii::$app->getHomeUrl()));
    }
}
