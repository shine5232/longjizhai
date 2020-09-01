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
     * 技工管理-业主小区列表
     */
    public function index($type)
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
                $where .= " AND (E.uname LIKE '%" . $keywords . "%' OR A.phone LIKE '%" . $keywords . "%') ";
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
            $user = Session::get('user');
            if($user['county']){
                $where .= " AND A.county =" . $user['county'];
            }
            $page_start = ($page - 1) * $limit;
            $sql = "SELECT A.*,
                        concat(B.region_name,'-',C.region_name,'-',D.region_name) AS city,
                        E.uname
                FROM (
                    SELECT A.id,
                        A.uid,
                        A.nickName,
                        A.subordinate,
                        A.phone,
                        A.city,
                        A.company,
                        A.university,
                        A.case,
                        A.create_time,
                        A.site,
                        A.province,
                        A.county,
                        CASE WHEN A.is_zong = 1 THEN '是' ELSE '否' END AS is_zong
                        FROM lg_mechanic A
                    WHERE type = " . $type . " AND status = 0" . $where . "
                    ORDER BY id DESC
                    limit $page_start,$limit
                ) A 
                INNER JOIN lg_region B ON A.province = B.region_code
                INNER JOIN lg_region C ON A.city = C.region_code
                INNER JOIN lg_region D ON A.county = D.region_code
                INNER JOIN lg_member E ON A.uid = E.uid";
            // var_dump($sql);die;
            $data = Db::query($sql);
            $sql1 = "SELECT COUNT(1) AS count FROM lg_mechanic A 
        INNER JOIN lg_member B ON A.uid = B.uid
        WHERE A.type = " . $type . " AND A.status = 0" . $where;
            $count = Db::query($sql1);
            // var_dump($count);die;
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $this->assign('type', $type);
            return $this->fetch('index');
        }
    }
    /**
     * 技工管理-添加技工页面
     */
    public function add($type)
    {
        $user = Session::get('user');
        if (request()->isPost()) {
            $post     = $this->request->post();
            $post['create_time'] = date('Y-m-d H:i:s');
            // var_dump($post['create_time']);die;
            if($user['county']){
                $post['province'] = $user['province'];
                $post['city'] = $user['city'];
                $post['county'] = $user['county'];
            }
            $row = Db::name('mechanic')
                ->where('name', $post['name'])
                ->where('nickName', $post['nickName'])
                ->where('phone', $post['phone'])
                ->where('company', $post['company'])
                ->where('type', $post['type'])
                ->where('university', $post['university'])
                ->where('province', $post['province'])
                ->where('city', $post['city'])
                ->where('county', $post['county'])
                ->find();
            // var_dump(Db::name('village')->getLastSql());die;
            // var_dump($row);die;
            if ($row) {
                $this->ret['code'] = -1;
                if ($post['type'] == 1) {
                    $this->ret['msg'] = '技工已存在';
                } elseif ($post['type'] == 2) {
                    $this->ret['msg'] = '技工已存在';
                } elseif ($post['type'] == 3) {
                    $this->ret['msg'] = '工长已存在';
                } elseif ($post['type'] == 4) {
                    $this->ret['msg'] = '装饰公司已存在';
                }
                return json($this->ret);
            }
            // var_dump($post);die;
            $db = Db::name('mechanic')->insert($post);
            // var_dump(Db::name('mechanic')->getLastSql());die;
            if ($db) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            } else {
                $this->ret['code'] = -1;
                $this->ret['msg'] = '添加失败';
            }
            return json($this->ret);
        } else {
            $where['type'] = 4;
            $where['status'] = 0;
            $wheres['subscribe'] = 1;
            if($user['county']){
                $wheres['county'] = $user['county'];
                $where['county'] = $user['county'];
            }
            $type4 = Db::name('mechanic')->where($where)->select();
            $member = Db::name('member')->where($wheres)->field('id,uid,uname,realname')->select();
            $region = _getRegion();
            $this->assign('regin', $region);
            $this->assign('type', $type);
            $this->assign('member', $member);
            $this->assign('type4', $type4);
            $this->assign('user', $user);
            if($type == '1'){
                return $this->fetch('deaigner_add');
            }elseif($type == '2'){
                return $this->fetch('mechanic_add');
            }elseif($type == '3'){
                return $this->fetch('gz_add');
            }else{
                return $this->fetch('company_add');
            }
        }
        // var_dump($type);die;

    }
    /**
     * 技工管理-编辑技工页面
     */
    public function edit()
    {
        $user = session('user');
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
            if($user['county']){
                $post['province'] = $user['province'];
                $post['city'] = $user['city'];
                $post['county'] = $user['county'];
            }
            $row = Db::name('mechanic')
                ->where('uid', $post['uid'])
                ->where('nickName', $post['nickName'])
                ->where('phone', $post['phone'])
                ->where('company', $post['company'])
                ->where('type', $post['type'])
                ->where('university', $post['university'])
                ->where('province', $post['province'])
                ->where('city', $post['city'])
                ->where('county', $post['county'])
                ->where('id', '<>', $id)
                ->find();
            // var_dump(Db::name('village')->getLastSql());die;
            // var_dump($row);die;
            if ($row) {
                if ($post['type'] == 1) {
                    $this->ret['msg'] = '技工已存在';
                } elseif ($post['type'] == 2) {
                    $this->ret['msg'] = '技工已存在';
                } elseif ($post['type'] == 3) {
                    $this->ret['msg'] = '工长已存在';
                } elseif ($post['type'] == 4) {
                    $this->ret['msg'] = '装饰公司已存在';
                }
                $this->ret['code'] = -1;
                return json($this->ret);
            }
            $MechanicModel = new MechanicModel;
            // save方法第二个参数为更新条件
            unset($post['id']);
            $db = $MechanicModel->save($post, ['id'=>$id]);
            // var_dump($db);die;
            if ($db) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            } else {
                $this->ret['code'] = -1;
                $this->ret['msg'] = '修改失败';
            }
            return json($this->ret);
        } else {
            $id  = $this->request->get('id');
            $type  = $this->request->get('type');
            $where['type'] = 4;
            $where['status'] = 0;
            $wheres['subscribe'] = 1;
            if($user['county']){
                $wheres['county'] = $user['county'];
                $where['county'] = $user['county'];
            }
            $data = Db::name('mechanic')->where('id', $id)->find();
            $type4 = Db::name('mechanic')->where($where)->select();
            $member = Db::name('member')->where($wheres)->select();
            $province = _getRegion();
            $city = _getRegion($data['province']);
            $county = _getRegion($data['city'], false, true);
            $this->assign('province', $province);
            $this->assign('city', $city);
            $this->assign('county', $county);
            $this->assign('data', $data);
            $this->assign('type', $type);
            $this->assign('member', $member);
            $this->assign('type4', $type4);
            $this->assign('user', $user);
            return $this->fetch('edit');
        }
    }
    /**
     * 技工管理-技工搜索页面
     */
    public function search()
    {
        $user = session('user');
        $region = _getRegion();
        $this->assign('regin', $region);
        $this->assign('user', $user);
        return $this->fetch();
    }
    /**
     * 技工管理-技工删除
     */
    public function delMechanic()
    {
        $id = $this->request->post('id');
        $upd = [
            'status'    =>  1,
            'del_time'   =>  date('Y-m-d H:i:s')
        ];
        $res = Db::name('mechanic')->where('id', $id)->update($upd);
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }

    /**
     * 技工管理-批量管理
     */
    public function update($method)
    {
        // var_dump($method);die;
        $delList = $this->request->post('delList');
        $delList = json_decode($delList, true);
        $arr = [];
        if ($method == 1) {
            foreach ($delList as $k => $v) {
                $data['id'] = $v;
                $data['status'] = 1;
                $arr[] = $data;
            }
        } elseif ($method == 2) {
            foreach ($delList as $k => $v) {
                $data['id'] = $v;
                $data['is_zong'] = 1;
                $arr[] = $data;
            }
        } else {
            foreach ($delList as $k => $v) {
                $data['id'] = $v;
                $data['is_zong'] = 0;
                $arr[] = $data;
            }
        }
        $user = new MechanicModel;
        $res = $user->saveAll($arr);
        // var_dump(Db::name('mechanic')->getLastSql());die;
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
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
