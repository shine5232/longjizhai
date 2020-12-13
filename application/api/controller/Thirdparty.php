<?php

namespace app\api\controller;

use \think\Db;
use think\Session;

class Thirdparty extends Main
{
    /**
     * 第三方机构管理-机构成员列表页面
     */
    public function getThirdPartyLis()
    {
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['party_id']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $where = [
                'A.status'=>['eq',1],
                'A.party_id'=>['eq',$post['party_id']],
            ];
            $data = Db::name('thirdparty_item')->alias('A')
                ->join('thirdparty B','A.party_id = B.id','INNER')
                ->where($where)->order('A.id DESC')->limit($page_start, $limit)
                ->field('A.*,B.name')
                ->select();
            if($data){
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 第三方机构-添加人员浏览记录
     */
    public function addThirdartyView()
    {
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['party_id']) || !isset($post['uid']) || !isset($post['item_id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $insert = $post;
            $insert['create_time'] = date('Y-m-d H:i:s');
            $data = Db::name('thirdparty_view')->insert($insert);
            if($data){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '操作成功';
            }else{
                $this->ret['msg'] = '操作失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
