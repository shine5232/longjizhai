<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;
use \think\Session;

class Banner extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 轮播图管理-轮播管理
     */
    public function index(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $user = Session::get('user');
            $where = ' A.status = 0';
            if($user['county']){
                $where .= " AND A.county = ".$user['county'];
            }
            $sql1 = "SELECT A.*,B.region_name as county_name,C.region_name as city_name,D.region_name as province_name 
                    FROM (
                        SELECT A.*,B.name AS position_name FROM lg_banner AS A LEFT JOIN lg_banner_position B ON A.position_id = B.id
                        WHERE $where
                        ORDER BY A.county DESC,A.sort DESC,A.id DESC 
                        limit $page_start, $limit
                    ) A 
                    LEFT JOIN lg_region B ON A.county = B.region_code 
                    LEFT JOIN lg_region C ON B.region_superior_code = C.region_code 
                    LEFT JOIN lg_region D ON C.region_superior_code = D.region_code ";
            $sql2 = "SELECT COUNT(0) AS num FROM lg_banner AS A WHERE $where";
            $data = Db::query($sql1);
            $count = Db::query($sql2);
            if ($data) {
                $this->ret['count'] = $count[0]['num'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch();
        }
    }
    /**
     * 轮播图管理-轮播添加
     */
    public function bannerAdd(){
        if(request()->isPost()){
            $post = $this->request->post();
            $status = isset($post['status'])?$post['status']:0;
            $insert = $post;
            $user = Session::get('user');
            if($user['province']){
                $insert['province'] = $user['province'];
                $insert['city'] = $user['city'];
                $insert['county'] = $user['county'];
            }
            $insert['status']=$status;
            $insert['create_time']=date('Y-m-d H:i:s');
            $res = Db::name('banner')->insert($insert);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            $position = Db::name('banner_position')->where('status',1)->field('id,name')->select();
            $this->assign('position',$position);
            return $this->fetch('banner_add');
        }
    }
    /**
     * 轮播图管理-轮播编辑
     */
    public function bannerEdit(){
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $status = isset($post['status'])?$post['status']:0;
            $upd = $post;
            $upd['status']=$status;
            $upd['update_time']=date('Y-m-d H:i:s');
            unset($upd['id']);
            $res = Db::name('banner')->where('id',$id)->update($upd);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '更新成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $banner = Db::name('banner')->where('id',$id)->find();
            $position = Db::name('banner_position')->where('status',1)->field('id,name')->select();
            $this->assign('position',$position);
            $this->assign('banner',$banner);
            return $this->fetch('banner_edit');
        }
    }
    /**
     * 轮播图管理-轮播删除
     */
    public function bannerDel()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('banner')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('banner')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 轮播图管理-位置管理
     */
    public function position()
    {
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where['status'] = ['neq',2];
            $data = Db::name('banner_position')->where($where)->order('id DESC')->limit($page_start,$limit)->select();
            $count = Db::name('banner_position')->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch('position');
        }
    }
    /**
     * 轮播图管理-添加位置
     */
    public function positionAdd()
    {
        if(request()->isPost()){
            $post = $this->request->post();
            $status = isset($post['status'])?$post['status']:0;
            $insert = $post;
            $insert['status']=$status;
            $insert['create_time']=date('Y-m-d H:i:s');
            $res = Db::name('banner_position')->insert($insert);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            return $this->fetch('position_add');
        }
    }
    /**
     * 轮播图管理-编辑位置
     */
    public function positionEdit(){
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $status = isset($post['status'])?$post['status']:0;
            $upd = $post;
            $upd['status']=$status;
            $upd['update_time']=date('Y-m-d H:i:s');
            unset($upd['id']);
            $res = Db::name('banner_position')->where('id',$id)->update($upd);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '更新成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $position = Db::name('banner_position')->where('id',$id)->find();
            $this->assign('position',$position);
            return $this->fetch('position_edit');
        }
    }
    /**
     * 轮播图管理-位置删除
     */
    public function positionDel()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'status'    =>  2,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('banner_position')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('banner_position')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
