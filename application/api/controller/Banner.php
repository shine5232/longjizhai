<?php
namespace app\api\controller;

use think\Db;

class Banner extends Main
{
    /**
     * 根据城市获取轮播图
     */
    public function getBanner(){
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
            $where['status'] = 0;
            $data = Db::name('banner')->where($where)->field('id,title,url,thumb')->order('sort DESC,id DESC')->select();
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
