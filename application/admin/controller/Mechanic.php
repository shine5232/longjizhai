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
                    $this->ret['msg'] = '设计师已存在';
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
            $type4 = Db::name('mechanic')->where('type = 4')->where('status =  0')->select();
            $where['subscribe'] = 1;
            if($user['county']){
                $where['county'] = $user['county'];
            }
            $member = Db::name('member')->where($where)->field('id,uid,uname,realname')->select();
            $region = _getRegion();
            $this->assign('regin', $region);
            $this->assign('type', $type);
            $this->assign('member', $member);
            $this->assign('type4', $type4);
            return $this->fetch('add');
        }
        // var_dump($type);die;

    }
    /**
     * 技工管理-编辑技工页面
     */
    public function edit()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
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
                ->where('id', '<>', $id)
                ->find();
            // var_dump(Db::name('village')->getLastSql());die;
            // var_dump($row);die;
            if ($row) {
                if ($post['type'] == 1) {
                    $this->ret['msg'] = '设计师已存在';
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
            $db = $MechanicModel->save($post, ['id', $id]);
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
            $data = Db::name('mechanic')->where('id', $id)->find();
            $type4 = Db::name('mechanic')->where('type = 4')->where('status =  0')->select();
            $member = Db::name('member')->where('type = 4')->select();
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
            return $this->fetch('edit');
        }
    }
    /**
     * 技工管理-技工搜索页面
     */
    public function search()
    {
        $region = _getRegion();
        $this->assign('regin', $region);
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
}
