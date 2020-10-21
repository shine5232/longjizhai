<?php

namespace app\admin\controller;

use \think\Db;
use think\Session;

class thirdparty extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 第三方机构管理-机构列表页面
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where = ['status'=>['neq',2]];
            $data = Db::name('thirdparty')->where($where)->order('id DESC')->limit($page_start, $limit)->select();
            $count = Db::name('thirdparty')->where($where)->count();
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
     * 第三方机构-机构添加页面
     */
    public function add()
    {
        if (request()->isPost()) {
            $post = $this->request->post();
            $post['create_time'] =  date('Y-m-d H:i:s');
            $post['status'] = isset($post['status'])?$post['status']:0;
            $res = Db::name('thirdparty')->insert($post);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        } else {
            return  $this->fetch('add');
        }
    }
    /**
     * 第三方机构-机构编辑页面
     */
    public function edit()
    {
        if (request()->isPost()) {
            $post =  $this->request->post();
            $id = $post['id'];
            $upd = [
                'status'=>isset($post['status'])?$post['status']:0,
                'name'=>$post['name'],
                'update_time'=>date('Y-m-d H:i:s')
            ];
            $res = Db::name('thirdparty')->where('id', $id)->update($upd);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        } else {
            $id  = $this->request->get('id');
            $data = Db::name('thirdparty')->where('id', $id)->find();
            return  $this->fetch('edit', ['data' => $data]);
        }
    }

    /**
     * 第三方机构-机构删除处理
     */
    public function del()
    {
        $id = $this->request->post('id');
        $upd = [
            'status'=>2,
            'delete_time'=>date('Y-m-d H:i:s')
        ];
        $res = Db::name('thirdparty')->where('id', $id)->update($upd);
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 第三方机构-机构更新状态
     */
    public function updateStatus()
    {
        $id = $this->request->param('id');
        $thirdparty = Db::name('thirdparty')->where('id', $id)->find();
        $data = [];
        if ($thirdparty) {
            $data = [
                'status'        =>  $thirdparty['status'] == 0 ? 1 : 0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('thirdparty')->where('id', $id)->update($data);
        if ($result) {
            $this->ret['code'] = 200;
        }
        $this->ret['data'] = $data['status'];
        return json($this->ret);
    }
    /**
     * 第三方机构管理-机构成员列表页面
     */
    public function item()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where = ['A.status'=>['neq',2]];
            $data = Db::name('thirdparty_item')->alias('A')
                ->join('thirdparty B','A.party_id = B.id','INNER')
                ->where($where)->order('A.id DESC')->limit($page_start, $limit)
                ->field('A.*,B.name')
                ->select();
            $count = Db::name('thirdparty_item')->alias('A')
                ->join('thirdparty B','A.party_id = B.id','INNER')
                ->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            return $this->fetch('item');
        }
    }
    /**
     * 第三方机构-机构成员添加页面
     */
    public function itemAdd()
    {
        if (request()->isPost()) {
            $post = $this->request->post();
            $post['create_time'] =  date('Y-m-d H:i:s');
            $post['status'] = isset($post['status'])?$post['status']:0;
            $res = Db::name('thirdparty_item')->insert($post);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        } else {
            $where = ['status'=>['neq',2]];
            $party = Db::name('thirdparty')->where($where)->order('id DESC')->select();
            return  $this->fetch('item_add',['party'=>$party]);
        }
    }
    /**
     * 第三方机构-机构成员编辑页面
     */
    public function itemEdit()
    {
        if (request()->isPost()) {
            $post =  $this->request->post();
            $id = $post['id'];
            $upd = [
                'status'=>isset($post['status'])?$post['status']:0,
                'item_name'=>$post['item_name'],
                'item_url'=>$post['item_url'],
                'sort'=>$post['sort'],
                'update_time'=>date('Y-m-d H:i:s')
            ];
            $res = Db::name('thirdparty_item')->where('id', $id)->update($upd);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        } else {
            $id  = $this->request->get('id');
            $data = Db::name('thirdparty_item')->where('id', $id)->find();
            $where = ['status'=>['neq',2]];
            $party = Db::name('thirdparty')->where($where)->order('id DESC')->select();
            return  $this->fetch('item_edit', ['data' => $data,'party'=>$party]);
        }
    }

    /**
     * 第三方机构-机构成员删除处理
     */
    public function itemDel()
    {
        $id = $this->request->post('id');
        $upd = [
            'status'=>2,
            'delete_time'=>date('Y-m-d H:i:s')
        ];
        $res = Db::name('thirdparty_item')->where('id', $id)->update($upd);
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 第三方机构-机构成员更新状态
     */
    public function itemStatus()
    {
        $id = $this->request->param('id');
        $thirdparty = Db::name('thirdparty_item')->where('id', $id)->find();
        $data = [];
        if ($thirdparty) {
            $data = [
                'status'        =>  $thirdparty['status'] == 0 ? 1 : 0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('thirdparty_item')->where('id', $id)->update($data);
        if ($result) {
            $this->ret['code'] = 200;
        }
        $this->ret['data'] = $data['status'];
        return json($this->ret);
    }
    /**
     * 第三方机构-访问统计
     */
    public function count()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $uname = $this->request->param('uname','');
            $party_id = $this->request->param('party_id','');
            $item_id = $this->request->param('item_id','');
            $page_start = ($page - 1) * $limit;
            $where['B.status'] = ['neq',2];
            $where['C.status'] = ['neq',2];
            if($uname){
                $where['D.uname'] = ['eq',$uname];
            }
            if($party_id){
                $where['A.party_id'] = ['eq',$party_id];
            }
            if($item_id){
                $where['A.item_id'] = ['eq',$item_id];
            }
            $data = Db::name('thirdparty_view')->alias('A')
                ->join('thirdparty_item B','A.item_id = B.id','INNER')
                ->join('thirdparty C','A.party_id = C.id','INNER')
                ->join('member D','A.uid = D.uid','INNER')
                ->where($where)->order('A.id DESC')->limit($page_start, $limit)
                ->field('A.*,B.item_name,C.name,D.uname')
                ->select();
            $count = Db::name('thirdparty_view')->alias('A')
                ->join('thirdparty_item B','A.item_id = B.id','INNER')
                ->join('thirdparty C','A.party_id = C.id','INNER')
                ->join('member D','A.uid = D.uid','INNER')
                ->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            return $this->fetch('item_count');
        }
    }
    /**
     * 第三方机构-访问搜索
     */
    public function searchCount()
    {
        $where = ['status'=>['neq',2]];
        $party = Db::name('thirdparty')->where($where)->order('id DESC')->select();
        return $this->fetch('search_count',['party'=>$party]);
    }
    /**
     * 第三方机构-根据机构查询成员数据
     */
    public function getitem()
    {
        $party_id = $this->request->post('party_id');
        $data = Db::name('thirdparty_item')->where('party_id',$party_id)->select();
        if ($data) {
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
}
