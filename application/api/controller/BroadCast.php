<?php
namespace app\api\controller;

use think\Db;

class BroadCast extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 根据城市获取公告信息
     */
    public function getBroadCast(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['county'])){
                $this->ret['msg'] = '缺少参数county';
                return json($this->ret);
            }
            if($post['county'] == '0'){
                $where['county'] = ['=','null'];
            }else{
                $where['county'] = $post['county'];
            }
            $where['status'] = 1;
            $data = Db::name('notice')->where($where)->field('id,title')->select();
            if($data){
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '暂无数据';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}