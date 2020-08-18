<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class MemberType extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 会员管理-会员分类列表页面
     */
    public function index()
    {
        $data = Db::name('member_type')->where('status', 0)->order(['type_sort' => 'DESC', 'id' => 'ASC'])->select();
        $data = array2Level($data);
        $count = count($data);
        return $this->fetch('index', ['data' => $data, 'count' => $count]);
    }
    /**
     * 会员管理-会员分类添加页面
     */
    public function add()
    {
        if (request()->isPost()) {
            $post = $this->request->post();
            $validate = validate('MemberType');
            $res = $validate->check($post);
            if ($res !== true) {
                $this->ret['msg'] = $validate->getError();
            } else {
                $post['create_time'] = date('Y-m-d H:i:s');
                $cate = Db::name('member_type')->where('id', $post['pid'])->field('level')->find();
                if ($cate) {
                    $post['level'] = (int)$cate['level'] + 1;
                }
                $db = Db::name('member_type')->insert($post);
                if ($db) {
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                }
            }
            return json($this->ret);
        } else {
            $auth = Db::name('member_type')->order(['type_sort' => 'DESC', 'id' => 'ASC'])->select();
            $auth = array2Level($auth);
            $this->assign('auth', $auth);
            return $this->fetch();
        }
    }
    /**
     * 会员管理-会员分类编辑页面
     */
    public function edit()
    {
        if (request()->isPost()) {
            $post = $this->request->post();
            $id = $post['id'];
            unset($post['id']);
            $post['update_time'] = date('Y-m-d H:i:s');
            $db = Db::name('member_type')->where('id', $id)->update($post);
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
            return json($this->ret);
        } else {
            $id  = $this->request->get('id');
            $data  =   Db::name('member_type')->where('id', $id)->find();
            $this->assign('data', $data);
            return $this->fetch();
        }
    }
    /**
     * 会员管理-会员分类删除数据
     */
    public function delete()
    {
        $id = $this->request->post('id');
        $data = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        $res = Db::name('member_type')->where('id', $id)->update($data);
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
