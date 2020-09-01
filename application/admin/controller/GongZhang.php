<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class GongZhang extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 设计师管理-属性管理
     */
    public function attr(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where = [
                'utype'=> 2,
                'deleted'=>0
            ];
            $data = Db::name('member_attr')->where($where)->order('type ASC,sort ASC,id DESC')->limit($page_start, $limit)->select();
            $count = Db::name('member_attr')->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch('attr');
        }
    }
    /**
     * 设计师管理-属性添加
     */
    public function attrAdd(){
        if(request()->isPost()){
            $post = $this->request->post();
            $status = isset($post['status'])?0:1;
            $insert = [
                'title'=>$post['title'],
                'utype'=>2,
                'type'=>$post['type'],
                'sort'=>$post['sort'],
                'status'=>$status,
                'create_time'=>date('Y-m-d H:i:s')
            ];
            $res = Db::name('member_attr')->insert($insert);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            return $this->fetch('attr_add');
        }
    }
    /**
     * 设计师管理-属性编辑
     */
    public function attrEdit(){
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $status = isset($post['status'])?0:1;
            $upd = [
                'title'=>$post['title'],
                'status'=>$status,
                'sort'=>$post['sort'],
                'type'=>$post['type'],
                'update_time'=>date('Y-m-d H:i:s')
            ];
            $res = Db::name('member_attr')->where('id',$id)->update($upd);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '更新成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $test = Db::name('member_attr')->where('id',$id)->find();
            $this->assign('attr',$test);
            return $this->fetch('attr_edit');
        }
    }
    /**
     * 设计师管理-属性修改状态
     */
    public function updateStatus(){
        $id = $this->request->param('id');
        $test = Db::name('member_attr')->where('id', $id)->find();
        $data = [];
        if ($test) {
            $data = [
                'status'        =>  $test['status'] == 0 ? 1 : 0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('member_attr')->where('id', $id)->update($data);
        if ($result) {
            $this->ret['code'] = 200;
        }
        return json($this->ret);
    }
    /**
     * 设计师管理-属性删除
     */
    public function attrDel()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'deleted'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('member_attr')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('member_attr')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}