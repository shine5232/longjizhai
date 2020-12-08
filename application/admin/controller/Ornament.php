<?php

namespace app\admin\controller;

use \think\Db;
use think\Session;

class ornament extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 装修需求-需求列表
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $user = Session::get('user');
            $where['A.status'] = ['neq',2];
            if($user['county']){
                $where['A.province'] = $user['province'];
                $where['A.city'] = $user['city'];
                $where['A.county'] = $user['county'];
            }
            $data = Db::name('ornament')->alias('A')
                ->join('member B','A.uid = B.id','INNER')
                ->where($where)->order('A.id DESC')->limit($page_start, $limit)
                ->field('A.*,B.uname')
                ->select();
            $count = Db::name('ornament')->alias('A')
                ->join('member B','A.uid = B.id','INNER')
                ->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            return $this->fetch('');
        }
    }
    /**
     * 装修需求-查看需求
     */
    public function look(){
        $id  = $this->request->get('id');
        $data = Db::name('ornament')->alias('A')
            ->join('member B','A.uid = B.id','INNER')->where('A.id', $id)
            ->field('A.*,B.uname')
            ->find();
        
        return  $this->fetch('look', ['data' => $data]);
    }
    /**
     * 装修需求-处理需求
     */
    public function optat(){
        if(request()->isPost()){
            $post     = $this->request->post();
            $data = Db::name('ornament')->where('id',$post['id'])->update(['status'=>1]);
            if ($data) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '处理成功';
            }else{
                $this->ret['msg'] = '处理失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
