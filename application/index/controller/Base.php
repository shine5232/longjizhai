<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2020-03-03
 * Time: 08:39
 */
namespace app\index\controller;

use think\Controller;
use think\Request;

class Base extends Controller {

    function __construct()
    {
        parent::__construct();
    }
    public function checkLogin()
    {

        $token = Request::instance()->header('token');
        $model = new \app\index\model\Base();
        $isLogin = $model->checkUserLogin($token);
        if(empty($isLogin))
        {
            die(json_encode(array('code'=>'-2','message'=>'登录失效')));
        }
    }
    function create_guid() {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $uuid = strtolower(substr($charid, 0, 8)
            .substr($charid, 8, 4)
            .substr($charid,12, 4)
            .substr($charid,16, 4)
            .substr($charid,20,12));
        return $uuid;
    }
    
}