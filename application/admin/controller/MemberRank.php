<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class MemberRank extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 会员管理-会员等级列表页面
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where = array();
            $data = Db::name('member_rank')
                ->where($where)
                ->order('rank_sort DESC,id DESC')
                ->limit($page_start, $limit)
                ->select();
            $count = Db::name('member_rank')
                ->where($where)
                ->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            return $this->fetch();
        }
    }
    /**
     * 会员管理-会员等级添加页面
     */
    public function add()
    {
        if (request()->isPost()) {
            $post = $this->request->post();
            $validate = validate('MemberRank');
            $res = $validate->check($post);
            if ($res !== true) {
                $this->ret['msg'] = $validate->getError();
            } else {
                $post['create_time'] = date('Y-m-d H:i:s');
                $db = Db::name('member_rank')->insert($post);
                if ($db) {
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                }
            }
            return json($this->ret);
        } else {
            return $this->fetch();
        }
    }
    /**
     * 会员管理-会员等级编辑页面
     */
    public function edit()
    {
        if (request()->isPost()) {
            $post =  $this->request->post();
            $id = $post['id'];
            unset($post['id']);
            Db::name('member_rank')->where('id', $id)->update($post);
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
            return json($this->ret);
        } else {
            $id  = $this->request->get('id');
            $data  =   Db::name('member_rank')->where('id', $id)->find();
            $this->assign('data', $data);
            return  $this->fetch();
        }
    }
    /**
     * 会员管理-会员等级删除处理
     */
    public function delete()
    {
        $id = $this->request->post('id');
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        $juge = Db::name('member_rank')->where('id', $id)->update($upd);
        if ($juge) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
