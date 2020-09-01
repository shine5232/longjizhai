<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class Designer extends Main
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
                'utype'=> 3,
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
                'utype'=>3,
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
    /**
     * 设计师管理-设计师管理
     */
    public function article(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where['a.cate_id'] = 76;
            $where['a.status']=0;
            $user = session('user');
            if($user['county']){
                $where['a.county'] = $user['county'];
            }
            $data = Db::name('article')->alias('a')
                    ->join('article_cate g','a.cate_id = g.id','INNER')
                    ->join('region d','a.county = d.region_code','LEFT')
                    ->join('mechanic e','a.author = e.id','INNER')
                    ->where($where)
                    ->field('a.*,d.region_name as county_name,g.title as cate_title,e.nickName as author_name')
                    ->order('a.author DESC,a.sort ASC,a.id DESC')
                    ->limit($page_start, $limit)
                    ->select();
            $count = Db::name('article')->alias('a')
                    ->join('article_cate g','a.cate_id = g.id','INNER')
                    ->join('mechanic e','a.author = e.id','INNER')
                    ->join('region d','a.county = d.region_code','LEFT')
                    ->where($where)
                    ->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch('article');
        }
    }
    /**
     * 设计师管理-添加文章页面
     */
    public function articleAdd(){
        if(request()->isPost()){
            $post     = $this->request->post();
            if(isset($post['author']) && $post['author']){
                $designer = Db::name('mechanic')->where('id',$post['author'])->field('province,city,county')->find();
                $post['province'] = $designer['province'];
                $post['city'] = $designer['city'];
                $post['county'] = $designer['county'];
            }
            $post['create_time']   = date('Y-m-d H:i:s');
            $db = Db::name('article')->insert($post);
            if($db){
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        }else{
            $admin_user = session('user');
            if($admin_user['county'] != null){
                $where['county'] = $admin_user['county'];
            }
            $where['type'] = 1;
            $where['status'] = 0;
            $designer = Db::name('mechanic')->where($where)->field('id,nickName')->select();
            $this->assign('designer',$designer);
            return $this->fetch('article_add');
        }
    }
    /**
     * 设计师管理-编辑文章页面
     */
    public function articleEdit(){
        if(request()->isPost()){
            $post     = $this->request->post();
            $id = $post['id'];
            $post['update_time']  = date('Y-m-d H:i:s');
            unset($post['id']);
            if(isset($post['author']) && $post['author']){
                $designer = Db::name('mechanic')->where('id',$post['author'])->field('province,city,county')->find();
                $post['province'] = $designer['province'];
                $post['city'] = $designer['city'];
                $post['county'] = $designer['county'];
            }
            $db = Db::name('article')->where('id',$id)->update($post);
            if($db){
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        }else{
            $id  = $this->request->get('id');
            $article = Db::name('article')->where('id',$id)->find();
            $admin_user = session('user');
            if($admin_user['county'] != null){
                $where['county'] = $admin_user['county'];
            }
            $where['type'] = 1;
            $where['status'] = 0;
            $designer = Db::name('mechanic')->where($where)->field('id,nickName')->select();
            $this->assign('designer',$designer);
            $this->assign('article',$article);
            return $this->fetch('article_edit');
        }
    }
    /**
     * 设计师管理-文章搜索页面
     */
    public function search(){
        $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $this->assign('cate',$cate);
        return $this->fetch();
    }
    /**
     * 设计师管理-文章删除
     */
    public function articleDel(){
        $id = $this->request->post('id');
        $ids = explode(',',$id);
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if(count($ids) > 1){
            $res = Db::name('article')->where('id','in',$id)->update($upd);
        }else{
            $res = Db::name('article')->where('id',$ids[0])->update($upd);
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}