<?php
namespace limefamily\OpenIdConnect\controllers;

use limefamily\OpenIdConnect\Keycloak;
use yii\base\Action;
use yii\web\BadRequestHttpException;
use yii\web\Controller;

class BaseController extends Controller
{
    /**
     * @param Action $action
     * @return bool
     * @throws BadRequestHttpException
     */
    public function beforeAction($action)
    {
        Keycloak::refreshToken();
        return parent::beforeAction($action);
    }
}