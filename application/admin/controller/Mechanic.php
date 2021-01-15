<?php

namespace app\admin\controller;

use \think\Db;
use app\admin\model\Mechanic as MechanicModel;
use \think\Reuquest;
use think\Session;

class Mechanic extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 技工管理-数据列表
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $realname = $this->request->param('realname', '');
            $mobile = $this->request->param('mobile', '');
            $uname = $this->request->param('uname', '');
            $uid = $this->request->param('uid', '');
            $subscribe = $this->request->param('subscribe', '');
            $status = $this->request->param('status', '');
            $checked = $this->request->param('checked', '');
            $city = $this->request->param('city', '');
            $province = $this->request->param('province', '');
            $county = $this->request->param('county', '');
            $where = '';
            $wheres = 'WHERE 1 = 1';
            if ($city) {
                $where .= " AND A.city =" . $city;
            }
            if ($province) {
                $where .= " AND  A.province = " . $province;
            }
            if ($county) {
                $where .= " AND A.county = " . $county;
            }
            if ($realname) {
                $where .= " AND A.name = '" . $realname . "'";
            }
            if($mobile){
                $where .= " AND A.mobile =" . $mobile;
            }
            if($uname){
                $wheres .= " AND E.uname = '" . $uname . "'";
            }
            if($uid){
                $wheres .= " AND E.id =" . $uid;
            }
            if($subscribe != ''){
                $wheres .= " AND E.subscribe =" . $subscribe;
            }
            if($status != ''){
                $wheres .= " AND E.status =" . $status;
            }
            if ($checked != '') {
                $where .= " AND A.checked = $checked";
            }
            $user = session('user');
            if($user['county']){
                $where .= " AND A.county =" . $user['county'];
            }
            $page_start = ($page - 1) * $limit;
            $sql = "SELECT A.*,E.uname,E.area,E.point,E.subor,E.subscribe,F.rank_name,G.uname AS topname
                FROM (
                    SELECT A.*,
                        CASE WHEN A.is_zong = 1 THEN '是' ELSE '否' END AS zong,B.name AS company_name,
                        CASE WHEN A.checked = 0 THEN '未审' WHEN A.checked = 1 THEN '通过' ELSE '未过' END AS checked_title
                        FROM lg_mechanic A LEFT JOIN lg_company B ON A.company_id = B.id
                    WHERE A.deleted = 0 " . $where . "
                    ORDER BY id DESC
                    limit $page_start,$limit
                ) A 
                INNER JOIN lg_member E ON A.uid = E.id
                INNER JOIN lg_member_rank F ON E.rank_id = F.id
                LEFT JOIN lg_member G ON E.superior_id = G.uid ".$wheres;
            $data = Db::query($sql);
            $sql1 = "SELECT COUNT(1) AS count FROM lg_mechanic A 
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
     * 技工管理-添加技工
     */
    public function mechanicAdd(){
        $user = session('user');
        if(request()->isPost()){
            $post = $this->request->post();
            $has = Db::name('mechanic')->where('uid',$post['uid'])->find();
            if($has){
                $this->ret['msg'] = '用户已关联';
                return json($this->ret);
            }
            $insert = $post;
            if(isset($post['uid']) && $post['uid']){
                $mechanic = Db::name('member')->where('id',$post['uid'])->field('province,city,county')->find();
                $insert['province'] = $mechanic['province'];
                $insert['city'] = $mechanic['city'];
                $insert['county'] = $mechanic['county'];
            }
            $insert['position'] = implode(',',$post['position']);
            $insert['create_time'] = date('Y-m-d H:i:s');
            $res = Db::name('mechanic')->insert($insert);
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
            $rank = Db::name('member_type')->where('pid',13)->field('id,type_title')->select();
            $ages_where = ['utype'=>1,'type'=>2,'status'=>0,'deleted'=>0];
            $position_where = ['utype'=>1,'type'=>1,'status'=>0,'deleted'=>0,'pid'=>0];
            $ages_attr = Db::name('member_attr')->where($ages_where)->field('id,title')->select();
            $position_attr = Db::name('member_attr')->where($position_where)->field('id,title')->select();

            $this->assign('regin', $region);
            $this->assign('member',$member);
            $this->assign('rank',$rank);
            $this->assign('ages_attr',$ages_attr);
            $this->assign('position_attr',$position_attr);
            $this->assign('user',$user);
            return $this->fetch('add');
        }
    }
    /**
     * 技工管理-编辑技工
     */
    public function mechanicEdit(){
        $user = session('user');
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $mechanic = Db::name('mechanic')->where('id',$id)->find();
            if($mechanic['uid'] != $post['uid']){
                $has = Db::name('mechanic')->where('uid',$post['uid'])->find();
                if($has){
                    $this->ret['msg'] = '用户已关联';
                    return json($this->ret);
                }
            }
            unset($post['imgFile']);
            unset($post['id']);
            $insert = $post;
            if(isset($post['uid']) && $post['uid']){
                $mechanics = Db::name('member')->where('id',$post['uid'])->field('province,city,county')->find();
                $insert['province'] = $mechanics['province'];
                $insert['city'] = $mechanics['city'];
                $insert['county'] = $mechanics['county'];
            }
            $insert['position'] = implode(',',$post['position']);
            $insert['update_time'] = date('Y-m-d H:i:s');
            $res = Db::name('mechanic')->where('id',$id)->update($insert);
            if($res){
                if($mechanic['uid'] != $post['uid']){
                    Db::name('member')->where('id',$mechanic['uid'])->update(array('locked'=>0));
                }
                Db::name('member')->where('id',$post['uid'])->update(array('locked'=>1));
                $this->ret['code'] = 200;
                $this->ret['msg'] = '修改成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $mechanic = Db::name('mechanic')->alias('A')->join('member B','B.id = A.uid','LEFT')->where('A.id',$id)->field('A.*,B.uname')->find();
            $region = _getRegion();
            $wheres['subscribe'] = 1;
            $wheres['locked'] = 0;
            if($user['county']){
                $wheres['county'] = $user['county'];
            }
            $member = Db::name('member')->where($wheres)->field('id,uid,uname,realname')->select();
            $rank = Db::name('member_type')->where('pid',13)->field('id,type_title')->select();
            $ages_where = ['utype'=>1,'type'=>2,'status'=>0,'deleted'=>0];
            $position_where = ['utype'=>1,'type'=>1,'status'=>0,'deleted'=>0,'pid'=>0];
            $ages_attr = Db::name('member_attr')->where($ages_where)->field('id,title')->select();
            $position_attr = Db::name('member_attr')->where($position_where)->field('id,title')->select();

            $this->assign('regin', $region);
            $this->assign('mechanic',$mechanic);
            $this->assign('member',$member);
            $this->assign('rank',$rank);
            $this->assign('ages_attr',$ages_attr);
            $this->assign('position_attr',$position_attr);
            $this->assign('user',$user);
            return $this->fetch('edit');
        }
    }
    /**
     * 技工管理-推荐/撤销 总站
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
            $res = Db::name('mechanic')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('mechanic')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 设计师管理-设计师删除
     */
    public function mechanicDel()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'deleted'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('mechanic')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('mechanic')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 技工管理-搜索
     */
    public function mechanicSearch(){
        $user = session('user');
        $region = _getRegion();
        $this->assign('regin', $region);
        $this->assign('user',$user);
        return $this->fetch('search');
    }
    /**
     * 技工管理-获取子工种
     */
    public function getChildren(){
        $post = $this->request->post();
        if(isset($post['id'])){
            $res = _getChildrenGong($post['id']);
            if ($res) {
                $this->ret['data'] = $res;
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
        }
        return json($this->ret); 
    }
    /**
     * 技工管理-属性管理
     */
    public function attr(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where = [
                'utype'=> 1,
                'deleted'=>0
            ];
            $data = Db::name('member_attr')->where($where)->order('type ASC,sort ASC,id DESC')->limit($page_start, $limit)->select();
            $count = Db::name('member_attr')->where($where)->count();
            if ($data) {
                foreach($data as $key=>$v){
                    $ptitle = Db::name('member_attr')->where('id',$v['pid'])->field('title')->find();
                    if($ptitle){
                        $data[$key]['ptitle'] = $ptitle['title'];
                    }else{
                        $data[$key]['ptitle'] = '顶级分类';
                    }
                }
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch('attr');
        }
    }
    /**
     * 技工管理-属性添加
     */
    public function attrAdd(){
        if(request()->isPost()){
            $post = $this->request->post();
            $status = isset($post['status'])?0:1;
            $pid = isset($post['pid'])?$post['pid']:0;
            $insert = [
                'title'=>$post['title'],
                'utype'=>1,
                'type'=>$post['type'],
                'pid'=>$pid,
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
            $where = [
                'utype'=>1,
                'type'=>1
            ];
            $attr = Db::name('member_attr')->where($where)->order(['type'=>'ASC','sort' => 'DESC', 'id' => 'ASC'])->select();
            $attr = array2Level($attr);
            $this->assign('attrs',$attr);
            return $this->fetch('attr_add');
        }
    }
    /**
     * 技工管理-属性编辑
     */
    public function attrEdit(){
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $status = isset($post['status'])?0:1;
            $pid = isset($post['pid'])?$post['pid']:0;
            $upd = [
                'title'=>$post['title'],
                'status'=>$status,
                'pid'=>$pid,
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
            $where = [
                'utype'=>1,
                'type'=>1
            ];
            $attr = Db::name('member_attr')->where($where)->order(['type'=>'ASC','sort' => 'DESC', 'id' => 'ASC'])->select();
            $attr = array2Level($attr);
            $this->assign('attrs',$attr);
            $this->assign('attr',$test);
            return $this->fetch('attr_edit');
        }
    }
    /**
     * 技工管理-属性修改状态
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
     * 技工管理-属性删除
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
