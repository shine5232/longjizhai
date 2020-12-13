<?php

namespace app\admin\controller;

use \think\Db;

class Question extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 信息管理-信息列表页面
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $keywords = $this->request->param('keywords', '');
            $page_start = ($page - 1) * $limit;
            $where = [];
            $user = session('user');
            if($user['county']){
                $where['A.county'] = $user['county'];
            }
            $where['A.status'] = ['neq',2];
            $data = Db::name('question')->alias('A')
                ->join('member_weixin B', 'B.id = A.uid', 'INNER')
                ->join('member C','C.uid = B.id','LEFT')
                ->where($where)
                ->field('A.*,B.nickname,B.avatar,C.uname,C.id AS member_id,C.type')
                ->order('A.id DESC')
                ->limit($page_start, $limit)
                ->select();
            $count = Db::name('question')->alias('A')
                ->where($where)
                ->count();
            foreach ($data as $k => $v) {
                if ($v['county'] == '' && $v['city'] == '') {
                    $data[$k]['area'] = '总站';
                }
            }
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
     * 信息管理-信息删除处理
     */
    public function deleteNotice()
    {
        $id = $this->request->post('id');
        $res = Db::name('question')
            ->where('id', $id)
            ->delete();
        // var_dump($res);die;
        // var_dump(Db::name('notice')->getLastSql());die;
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }

    /**
     * 信息管理-信息删除处理(批量)
     */
    public function delAll()
    {
        // var_dump($method);die;
        $delList = $this->request->post('delList');
        $delList = json_decode($delList, true);
        foreach ($delList as $k => $v) {
            $res = Db::name('question')->delete($v);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }


    /**
     * 信息管理-信息更新状态
     */
    public function updateStatus()
    {
        $id = $this->request->param('id');
        $notice = Db::name('question')->where('id', $id)->find();
        $data = [];
        if ($notice) {
            $data = [
                'status'        =>  $notice['status'] == 0 ? 1 : 0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('question')->where('id', $id)->update($data);
        if ($result) {
            $this->ret['code'] = 200;
        }
        $this->ret['data'] = $data['status'];
        return json($this->ret);
    }

    /**
     * 信息管理-信息查询
     */
    public function search()
    {
        return $this->fetch();
    }
}
