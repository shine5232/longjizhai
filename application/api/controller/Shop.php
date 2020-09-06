<?php
namespace app\api\controller;

use think\Db;

class Shop extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 根据城市获取推荐商家
     */
    public function getShopHot(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['county'])){
                $this->ret['msg'] = '缺少参数county';
                return json($this->ret);
            }
            if($post['county'] == '0'){
                $where['zong'] = 1;
            }else{
                $where['county'] = $post['county'];
            }
            $where['status'] = 0;
            $where['hot'] = 1;
            $data = Db::name('shop')->where($where)->field('id,rectangle_logo')->order('sort DESC,id DESC')->select();
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
