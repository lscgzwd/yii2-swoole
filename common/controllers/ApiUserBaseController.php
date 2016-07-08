<?php

namespace common\controllers;

use common\constants\ModelConst;
use common\controllers\ApiBaseController;
use common\service\api\OauthService;
use common\service\api\UserService;
use Yii;

class ApiUserBaseController extends ApiBaseController
{

    private $_companyInfo = [];
    protected $companyId  = 0; //企业id
    protected $memberId   = 0; //用户id

    public function beforeAction($action)
    {
        $request  = Yii::$app->request;
        $response = Yii::$app->getResponse();
        //访问类型校验
        if (!$request->isPost) {
            $err    = ModelConst::getError(ModelConst::G_REQUEST_ERR, '', '');
            $result = [
                'error' => $err,
                'data'  => null,
            ];
            $response->data = $result;
            $this->logResponse($result);
            Yii::$app->getResponse()->format = $this->outContentType;
            return false;
        }
        //基础参数校验
        $oauthService = new OauthService();
        if (!$oauthService->vaildBaseParam($request->post())) {
            $err    = ModelConst::getError(ModelConst::G_PARAM, '', '');
            $result = [
                'error' => $err,
                'data'  => null,
            ];
            $response->data = $result;
            $this->logResponse($result);
            Yii::$app->getResponse()->format = $this->outContentType;
            return false;
        }
        //验证用户是否登录
        if (!$oauthService->vaildAppLogin($request->post())) {
            $err    = ModelConst::getError(ModelConst::G_NO_LOGIN, '', '');
            $result = [
                'error' => $err,
                'data'  => null,
            ];
            $response->data = $result;
            $this->logResponse($result);
            Yii::$app->getResponse()->format = $this->outContentType;
            return false;
        }
        $this->memberId      = $request->post('memberID');
        $this->companyId     = $request->post('companyID');
        $this->needCheckSign = false;
        return parent::beforeAction($action);
    }

    protected function isAdmin()
    {
        if (!empty($this->_companyInfo)) {
            return $this->companyInfo['admin_user_id'] === Yii::$app->request->post('memberID');
        }
        $this->_setCompanyInfo();
        if (empty($this->_companyInfo)) {
            return false;
        }

        if (empty($this->_companyInfo['admin_user_id'])) {
            return false;
        }

        return $this->companyInfo['admin_user_id'] === Yii::$app->request->post('memberID');
    }
    protected function isOper()
    {
        if (!empty($this->_companyInfo)) {
            return $this->_companyInfo['oper_user_id'] === Yii::$app->request->post('memberID');
        }
        $this->_setCompanyInfo();
        if (empty($this->_companyInfo)) {
            return false;
        }

        if (empty($this->_companyInfo['oper_user_id'])) {
            return false;
        }

        return $this->companyInfo['oper_user_id'] === Yii::$app->request->post('memberID');
    }
    protected function getCompanyInfo()
    {

        if (empty($this->_companyInfo)) {
            $this->_setCompanyInfo();
        }

        return $this->_companyInfo;
    }
    private function _setCompanyInfo()
    {

        $companyID = Yii::$app->request->post('companyID');
        if (empty($companyID)) {
            return false;
        }

        $userService        = new UserService();
        $params             = ['user_id' => Yii::$app->request->post('companyID')];
        $this->_companyInfo = $userService->getCompanyInfo($params);
    }

}
