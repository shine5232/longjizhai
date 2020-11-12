<?php
namespace app\api\controller;

use think\Db;

class Community extends Main
{
    /**
     * 根据城市获取小区数据
     */
    public function getCommunityLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['county']) || !isset($post['village_type'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where = [
                'status' => 0,
                'village_type' => $post['village_type'],
                'county'    => $post['county']
            ];
            $data = Db::name('village')->where($where)->field('id AS value,village_name AS text')->order('id DESC')->select();
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
    /**
     * 新建小区
     */
    public function addCommunity(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['county']) || !isset($post['village_type'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $insert = [
                'village_name' => $post['title'],
                'village_addr'  =>  $post['addr'],
                'village_type' => $post['village_type'],
                'province'    => $post['province'],
                'city'    => $post['city'],
                'county'    => $post['county'],
                'create_time'   =>  date('Y-m-d H:i:s'),
            ];
            $data = Db::name('village')->insert($insert);
            if($data){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }else{
                $this->ret['msg'] = '添加失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
