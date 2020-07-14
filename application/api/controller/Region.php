<?php
namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Session;
use think\Validate;

class Region extends Controller
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    public function index(){
        $post = $this->request->post();
        if($post['type'] == '1'){
            $data = _getRegion();
        }else{
            $data = _getRegion($post['code']);
        }
        $this->ret['data'] = $data;
        return json($this->ret);
    }

}
