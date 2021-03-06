<?php
namespace app\ucenter\controller;

use think\Controller;
use think\Db;
use think\Session;


class Main extends Controller
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 解决跨域问题
     */
    public function _initialize()
    {
        header('content-type:text/html;charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET, POST, PUT');
    }
}
