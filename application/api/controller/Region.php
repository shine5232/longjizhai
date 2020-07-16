<?php
namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Session;
use think\Validate;

class Region extends Controller
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 获取地区信息
     */
    public function index(){
        $post = $this->request->post();
        if($post['type'] == '1'){
            $data = _getRegion();
        }else{
            if(isset($post['is_open'])){
                $data = _getRegion($post['code'],true);
            }else{
                $data = _getRegion($post['code']);
            }
        }
        $this->ret['data'] = $data;
        return json($this->ret);
    }

}
