<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;
use \think\Session;

class Recommend extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 推荐位管理-推荐位列表
     */
    public function index(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where['status'] = ['neq',2];
            $data = Db::name('recommend')->where($where)->order('id DESC')->limit($page_start,$limit)->select();
            $count = Db::name('recommend')->where($where)->count();
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
     * 推荐位管理-推荐位添加
     */
    public function add(){
        if(request()->isPost()){
            $post = $this->request->post();
            $status = isset($post['status'])?$post['status']:0;
            $insert = $post;
            $insert['status']=$status;
            $insert['create_time']=date('Y-m-d H:i:s');
            $res = Db::name('recommend')->insert($insert);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            return $this->fetch('add');
        }
    }
    /**
     * 推荐位管理-推荐位编辑
     */
    public function edit(){
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $status = isset($post['status'])?$post['status']:0;
            $upd = $post;
            $upd['status']=$status;
            $upd['update_time']=date('Y-m-d H:i:s');
            unset($upd['id']);
            $res = Db::name('recommend')->where('id',$id)->update($upd);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '更新成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $recommend = Db::name('recommend')->where('id',$id)->find();
            $this->assign('recommend',$recommend);
            return $this->fetch('edit');
        }
    }
    /**
     * 推荐位管理-推荐位删除
     */
    public function del()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'status'    =>  2,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('recommend')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('recommend')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 推荐位管理-推荐内容列表
     */
    public function dataLis(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where['A.status'] = ['neq',2];
            $user = session('user');
            if($user['county']){
                $where['A.county'] = ['eq', $user['county']];
            }
            $data = Db::name('recommend_data')->alias('A')
                ->join('recommend B','B.id = A.recommend_id','INNER')
                ->where($where)->order('A.sort DESC,A.id DESC')->limit($page_start,$limit)
                ->field('A.*,B.name AS recommend_name,A.province AS province_name,A.city AS city_name,A.county AS county_name')->select();
            $count = Db::name('recommend_data')->alias('A')
                ->join('recommend B','B.id = A.recommend_id','INNER')
                ->where($where)->count();
            if ($data) {
                foreach($data as &$v){
                    $v['province_name'] = Db::name('region')->where('region_code',$v['province'])->value('region_name');
                    $v['city_name'] = Db::name('region')->where('region_code',$v['city'])->value('region_name');
                    $v['county_name'] = Db::name('region')->where('region_code',$v['county'])->value('region_name');
                }
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch('data_lis');
        }
    }
    /**
     * 推荐位管理-推荐内容编辑
     */
    public function dataEdit(){
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $status = isset($post['status'])?$post['status']:0;
            $upd = $post;
            $upd['status']=$status;
            $upd['update_time']=date('Y-m-d H:i:s');
            unset($upd['id']);
            $res = Db::name('recommend_data')->where('id',$id)->update($upd);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '更新成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $data = Db::name('recommend_data')->where('id',$id)->find();
            $recommend = Db::name('recommend')->where(['status'=>['eq',1]])->select();
            $province = _getRegion(); //获取省份数据
            $city = _getRegion($data['province'], false, true); //获取城市数据
            $county = _getRegion($data['city'], false, true); //获取区县数据
            $this->assign('province', $province);
            $this->assign('city', $city);
            $this->assign('county', $county);
            $this->assign('recommend',$recommend);
            $this->assign('data',$data);
            return $this->fetch('data_edit');
        }
    }
    /**
     * 推荐位管理-推荐内容删除
     */
    public function dataDel()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'status'    =>  2,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('recommend_data')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('recommend_data')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
