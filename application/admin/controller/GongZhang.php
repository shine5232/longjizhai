<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class GongZhang extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 工长管理-数据列表
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $keywords = $this->request->param('keywords', '');
            $city = $this->request->param('city', '');
            $province = $this->request->param('province', '');
            $county = $this->request->param('county', '');
            $where = '';
            if ($keywords) {
                $where .= " AND (A.name LIKE '%$keywords%' OR A.mobile = '$keywords')";
            }
            if ($city) {
                $where .= " AND A.city =" . $city;
            }
            if ($province) {
                $where .= " AND  A.province = " . $province;
            }
            if ($county) {
                $where .= " AND A.county =" . $county;
            }
            $user = session('user');
            if($user['county']){
                $where .= " AND A.county =" . $user['county'];
            }
            $page_start = ($page - 1) * $limit;
            $sql = "SELECT A.*,
                        concat(B.region_name,'-',C.region_name,'-',D.region_name) AS city,
                        E.uname,F.type_title
                FROM (
                    SELECT A.*,
                        CASE WHEN A.is_zong = 1 THEN '是' ELSE '否' END AS zong,B.name AS company_name,
                        CASE WHEN A.checked = 0 THEN '未审' WHEN A.checked = 1 THEN '通过' ELSE '未过' END AS checked_title
                        FROM lg_gongzhang A LEFT JOIN lg_company B ON A.company_id = B.id
                    WHERE A.deleted = 0 " . $where . "
                    ORDER BY id DESC
                    limit $page_start,$limit
                ) A 
                INNER JOIN lg_region B ON A.province = B.region_code
                INNER JOIN lg_region C ON A.city = C.region_code
                INNER JOIN lg_region D ON A.county = D.region_code
                INNER JOIN lg_member E ON A.uid = E.id
                INNER JOIN lg_member_type F ON A.level = F.id";
            // var_dump($sql);die;
            $data = Db::query($sql);
            $sql1 = "SELECT COUNT(1) AS count FROM lg_gongzhang A 
                    INNER JOIN lg_member B ON A.uid = B.uid
                    WHERE A.deleted = 0 " . $where;
            $count = Db::query($sql1);
            // var_dump($count);die;
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            return $this->fetch('index');
        }
    }
    /**
     * 工长管理-添加工长
     */
    public function gongZhangAdd(){
        $user = session('user');
        if(request()->isPost()){
            $post = $this->request->post();
            $has = Db::name('gongzhang')->where('uid',$post['uid'])->find();
            if($has){
                $this->ret['msg'] = '用户已关联';
                return json($this->ret);
            }
            $insert = $post;
            if(isset($post['uid']) && $post['uid']){
                $gongzhang = Db::name('member')->where('id',$post['uid'])->field('province,city,county')->find();
                $insert['province'] = $gongzhang['province'];
                $insert['city'] = $gongzhang['city'];
                $insert['county'] = $gongzhang['county'];
            }
            $insert['create_time'] = date('Y-m-d H:i:s');
            $res = Db::name('gongzhang')->insert($insert);
            if($res){
                Db::name('member')->where('id',$post['uid'])->update(array('locked'=>1));
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            $region = _getRegion();
            $wheres['subscribe'] = 1;
            $wheres['locked'] = 0;
            if($user['county']){
                $wheres['county'] = $user['county'];
            }
            $member = Db::name('member')->where($wheres)->field('id,uid,uname,realname')->select();
            $rank = Db::name('member_type')->where('pid',14)->field('id,type_title')->select();
            $ages_where = ['utype'=>2,'type'=>2,'status'=>0,'deleted'=>0];
            $ages_attr = Db::name('member_attr')->where($ages_where)->field('id,title')->select();

            $this->assign('regin', $region);
            $this->assign('member',$member);
            $this->assign('rank',$rank);
            $this->assign('ages_attr',$ages_attr);
            $this->assign('user',$user);
            return $this->fetch('add');
        }
    }
    /**
     * 工长管理-编辑工长
     */
    public function gongZhangEdit(){
        $user = session('user');
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $gongzhang = Db::name('gongzhang')->where('id',$id)->find();
            if($gongzhang['uid'] != $post['uid']){
                $has = Db::name('gongzhang')->where('uid',$post['uid'])->find();
                if($has){
                    $this->ret['msg'] = '用户已关联';
                    return json($this->ret);
                }
            }
            unset($post['imgFile']);
            unset($post['id']);
            $insert = $post;
            if(isset($post['uid']) && $post['uid']){
                $gongzhangs = Db::name('member')->where('id',$post['uid'])->field('province,city,county')->find();
                $insert['province'] = $gongzhangs['province'];
                $insert['city'] = $gongzhangs['city'];
                $insert['county'] = $gongzhangs['county'];
            }
            $insert['update_time'] = date('Y-m-d H:i:s');
            $res = Db::name('gongzhang')->where('id',$id)->update($insert);
            if($res){
                if($gongzhang['uid'] != $post['uid']){
                    Db::name('member')->where('id',$gongzhang['uid'])->update(array('locked'=>0));
                }
                Db::name('member')->where('id',$post['uid'])->update(array('locked'=>1));
                $this->ret['code'] = 200;
                $this->ret['msg'] = '修改成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $gongzhang = Db::name('gongzhang')->alias('A')->join('member B','B.id = A.uid','LEFT')->where('A.id',$id)->field('A.*,B.uname')->find();
            $region = _getRegion();
            $wheres['subscribe'] = 1;
            $wheres['locked'] = 0;
            if($user['county']){
                $wheres['county'] = $user['county'];
            }
            $member = Db::name('member')->where($wheres)->field('id,uid,uname,realname')->select();
            $rank = Db::name('member_type')->where('pid',14)->field('id,type_title')->select();
            $ages_where = ['utype'=>2,'type'=>2,'status'=>0,'deleted'=>0];
            $ages_attr = Db::name('member_attr')->where($ages_where)->field('id,title')->select();

            $this->assign('regin', $region);
            $this->assign('gongzhang',$gongzhang);
            $this->assign('member',$member);
            $this->assign('rank',$rank);
            $this->assign('ages_attr',$ages_attr);
            $this->assign('user',$user);
            return $this->fetch('edit');
        }
    }
    /**
     * 工长管理-推荐/撤销 总站
     */
    public function topOrDown(){
        $post = $this->request->post();
        $id = $post['id'];
        $ids = explode(',', $id);
        $upd['update_time'] = date('Y-m-d H:i:s');
        if($post['type'] == '2'){
            $upd['is_zong'] = 1;
        }else{
            $upd['is_zong'] = 0;
        }
        if (count($ids) > 1) {
            $res = Db::name('gongzhang')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('gongzhang')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 工长管理-工长删除
     */
    public function gongZhangDel()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'deleted'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('gongzhang')->where('id', 'in', $id)->update($upd);
            Db::name('member')->where('id', 'in', $id)->update(array('locked'=>0));
        } else {
            $res = Db::name('gongzhang')->where('id', $ids[0])->update($upd);
            Db::name('member')->where('id', $ids[0])->update(array('locked'=>0));
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 工长管理-搜索
     */
    public function gongZhangSearch(){
        $user = session('user');
        $region = _getRegion();
        $this->assign('regin', $region);
        $this->assign('user',$user);
        return $this->fetch('search');
    }
    /**
     * 工长管理-属性管理
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
     * 工长管理-属性添加
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
     * 工长管理-属性编辑
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
     * 工长管理-属性修改状态
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
     * 工长管理-属性删除
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