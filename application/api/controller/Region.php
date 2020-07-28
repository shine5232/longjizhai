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
            if(isset($post['is_open']) && $post['is_open'] == '1'){
                $data = _getRegion($post['code'],true);
            }elseif(isset($post['is_open']) && $post['is_open'] == '2'){
                $data = _getRegion($post['code'],false,true);
            }else{
                $data = _getRegion($post['code'],false,false);
            }
        }
        $this->ret['data'] = $data;
        return json($this->ret);
    }

}
