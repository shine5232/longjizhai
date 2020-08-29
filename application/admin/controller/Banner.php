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
            $where = ' status = 0';
            if($user['county']){
                $where .= " AND county = ".$user['county'];
            }
            $sql1 = "SELECT A.*,B.region_name as county_name,C.region_name as city_name,D.region_name as province_name 
                    FROM (
                        SELECT * FROM lg_banner
                        WHERE $where
                        ORDER BY county DESC,sort DESC,id DESC 
                        limit $page_start, $limit
                    ) A 
                    LEFT JOIN lg_region B ON A.county = B.region_code 
                    LEFT JOIN lg_region C ON B.region_superior_code = C.region_code 
                    LEFT JOIN lg_region D ON C.region_superior_code = D.region_code ";
            $sql2 = "SELECT COUNT(0) AS num FROM lg_banner WHERE $where";
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
}
