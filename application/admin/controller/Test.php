<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class Test extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 测评管理-试卷管理
     */
    public function index(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $data = Db::name('test')->where('deleted',0)->order('id DESC')->limit($page_start, $limit)->select();
            $count = Db::name('test')->where('deleted',0)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch();
        }
    }
    /**
     * 测评管理-试卷添加
     */
    public function testAdd(){
        if(request()->isPost()){
            $post = $this->request->post();
            $status = isset($post['status'])?$post['status']:0;
            $insert = [
                'title'=>$post['title'],
                'status'=>$status,
                'create_time'=>date('Y-m-d H:i:s')
            ];
            $res = Db::name('test')->insert($insert);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            return $this->fetch('test_add');
        }
    }
    /**
     * 测评管理-试卷编辑
     */
    public function testEdit(){
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $status = isset($post['status'])?$post['status']:0;
            $upd = [
                'title'=>$post['title'],
                'status'=>$status,
                'update_time'=>date('Y-m-d H:i:s')
            ];
            $res = Db::name('test')->where('id',$id)->update($upd);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '更新成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $test = Db::name('test')->where('id',$id)->find();
            $this->assign('test',$test);
            return $this->fetch('test_edit');
        }
    }
    /**
     * 测评管理-试卷修改状态
     */
    public function updateStatus(){
        $id = $this->request->param('id');
        $test = Db::name('test')->where('id', $id)->find();
        $data = [];
        if ($test) {
            $data = [
                'status'        =>  $test['status'] == 0 ? 1 : 0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('test')->where('id', $id)->update($data);
        if ($result) {
            $this->ret['code'] = 200;
        }
        return json($this->ret);
    }
    /**
     * 测评管理-试卷删除
     */
    public function deleteTest()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'deleted'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('test')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('test')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 测评管理-题目列表
     */
    public function option(){
        $test_id = $this->request->get('test_id');
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where['deleted'] = ['eq','0'];
            $where['test_id'] = ['eq',$test_id];
            $data = Db::name('test_option')->where($where)->order('sort ASC,id ASC')->limit($page_start,$limit)->select();
            $count = Db::name('test_option')->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            $this->assign('test_id',$test_id);
            return $this->fetch('option');
        }
    }
    /**
     * 测评管理-题目添加
     */
    public function optionAdd(){
        if(request()->isPost()){
            $post = $this->request->post();
            $post['answer'] = strtoupper($post['answer']);
            $post['status'] = isset($post['status'])?$post['status']:0;
            $post['create_time'] = date('Y-m-d H:i:s');
            $post['option'] = isset($post['option'])?serialize($post['option']):'';
            $res = Db::name('test_option')->insert($post);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            $test_id = $this->request->get('test_id');
            $this->assign('test_id',$test_id);
            return $this->fetch('option_add');
        }
    }
    /**
     * 测评管理-题目编辑
     */
    public function optionEdit(){
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $post['answer'] = strtoupper($post['answer']);
            $post['status'] = isset($post['status'])?$post['status']:0;
            $post['create_time'] = date('Y-m-d H:i:s');
            $post['option'] = isset($post['option'])?serialize($post['option']):'';
            unset($post['id']);
            $res = Db::name('test_option')->where('id',$id)->update($post);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '修改成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $option = Db::name('test_option')->where('id',$id)->find();
            $option['option'] = unserialize($option['option']);
            $this->assign('option',$option);
            return $this->fetch('option_edit');
        }
    }
    /**
     * 测评管理-状态改变
     */
    public function changeStatus(){
        $id = $this->request->param('id');
        $option = Db::name('test_option')->where('id', $id)->find();
        $data = [];
        if ($option) {
            $data = [
                'status'        =>  $option['status'] == 0 ? 1 : 0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('test_option')->where('id', $id)->update($data);
        if ($result) {
            $this->ret['code'] = 200;
        }
        return json($this->ret);
    }
    /**
     * 测评管理-选项删除
     */
    public function deleteOption()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'deleted'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('test_option')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('test_option')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = '删除成功';
        }
        return json($this->ret);
    }
}
