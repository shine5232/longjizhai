<?php

namespace app\admin\controller;

use \think\Db;
use app\admin\model\Village as VillageModel;
use app\admin\model\Building_log as BuildLogModel;

class Building extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];

    /**
     * 在建工地管理-在建工地列表
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
                $where .= " AND A.village_name LIKE '%" . $keywords . "%'";
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
                $where .= ' AND A.county = '.$user['county'];
            }
            $page_start = ($page - 1) * $limit;
            $sql = "SELECT A.id,A.village_addr,A.village_name,concat(B.region_name,'-',C.region_name,'-',D.region_name) AS city,area,price,E.name AS speed_name
                FROM lg_village A 
                INNER JOIN lg_region B ON A.province = B.region_code
                INNER JOIN lg_region C ON A.city = C.region_code
                INNER JOIN lg_region D ON A.county = D.region_code
                LEFT JOIN lg_speed E ON A.speed_id = E.id
                WHERE A.village_type = 2 AND A.status = 0" . $where . "
                ORDER BY id DESC
                limit $page_start,$limit";
            // var_dump($sql);die;
            $data = Db::query($sql);
            $sql1 = "SELECT COUNT(1) AS count FROM lg_village A WHERE A.village_type = 2 AND A.status = 0" . $where;
            $count = Db::query($sql1);
            // var_dump($count);die;
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            return $this->fetch();
        }
    }
    /**
     * 在建工地管理-添加在建工地页面
     */
    public function buildingAdd()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $post['create_time'] = date('Y-m-d H:i:S');
            $post['village_type'] = 2;
            $row = Db::name('village')
                ->where('village_name', $post['village_name'])
                ->where('village_addr', $post['village_addr'])
                ->where('province', $post['province'])
                ->where('city', $post['city'])
                ->where('county', $post['county'])
                ->find();
            // var_dump(Db::name('village')->getLastSql());die;
            // var_dump($row);die;
            if ($row) {
                $this->ret['code'] = -1;
                $this->ret['msg'] = '在建工地已存在';
                return json($this->ret);
            }
            $db = Db::name('village')->insert($post);
            if ($db) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            } else {
                $this->ret['code'] = -1;
                $this->ret['msg'] = '添加失败';
            }
            return json($this->ret);
        } else {
            $company = Db::name('company')
                ->where('status = 0')
                ->select();
            $region = _getRegion();
            $this->assign('regin', $region);
            $this->assign('company', $company);
            return $this->fetch('add');
        }
    }
    /**
     * 在建工地管理-编辑在建工地页面
     */
    public function buildingEdit()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
            $row = Db::name('village')
                ->where('village_name', $post['village_name'])
                ->where('village_addr', $post['village_addr'])
                ->where('province', $post['province'])
                ->where('city', $post['city'])
                ->where('county', $post['county'])
                ->where('id', '<>', $id)
                ->find();
            // var_dump(Db::name('village')->getLastSql());die;
            // var_dump($row);die;
            if ($row) {
                $this->ret['code'] = -1;
                $this->ret['msg'] = '在建工地已存在';
                return json($this->ret);
            }
            $NoticeModel = new VillageModel;
            // save方法第二个参数为更新条件
            $res = $NoticeModel->save($post, ['id', $id]);
            // var_dump($db);die;
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            } else {
                $this->ret['code'] = -1;
                $this->ret['msg'] = '修改失败';
            }
            return json($this->ret);
        } else {
            $company = Db::name('company')
                ->where('status = 0')
                ->select();
            $id  = $this->request->get('id');
            $data = Db::name('village')->where('id', $id)->find();
            $province = _getRegion();
            $city = _getRegion($data['province']);
            $county = _getRegion($data['city'], false, true);
            $this->assign('province', $province);
            $this->assign('city', $city);
            $this->assign('county', $county);
            $this->assign('data', $data);
            $this->assign('company', $company);
            return $this->fetch('edit');
        }
    }
    /**
     * 在建工地管理-在建工地搜索页面
     */
    public function search()
    {
        $region = _getRegion();
        $this->assign('regin', $region);
        return $this->fetch();
    }
    /**
     * 在建工地管理-在建工地删除
     */
    public function delBuilding()
    {
        $id = $this->request->post('id');
        $upd = [
            'status'    =>  1,
            'del_time'   =>  date('Y-m-d H:i:s')
        ];
        $res = Db::name('village')->where('id', $id)->update($upd);
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }


    /**
     * 在建工地管理-在建工地批量删除
     */
    public function delAll()
    {
        $delList = $this->request->post('delList');
        $delList = json_decode($delList, true);
        $arr = [];
        foreach ($delList as $k => $v) {
            $data['id'] = $v;
            $data['status'] = 1;
            $arr[] = $data;
        }
        $user = new VillageModel;
        $res = $user->saveAll($arr);
        // var_dump(Db::name('mechanic')->getLastSql());die;
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 在建工地管理-工地日志
     */
    public function buildingLog()
    {
        if (request()->isAjax()) {
            $id = $this->request->post('id');
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $data = Db::name('building_log')
                ->field('a.*,b.name as speed_name')
                ->alias('a')
                ->join('speed b', 'a.speed_id = b.id', 'LEFT')
                ->where('building_id', $id)
                ->order('id DESC')
                ->limit($page_start, $limit)
                ->select();
            // var_dump(Db::name('building_log')->getLastSql());die;

            $count = Db::name('building_log')
                ->where('building_id', $id)
                ->count();
            // var_dump($count);die;
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $id = $this->request->get('id');
            $this->assign('id', $id);
            return $this->fetch('log');
        }
    }
    /**
     * 在建工地管理-添加在建工地
     */
    public function logAdd()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $post['create_time'] = date('Y-m-d H:i:S');
            $db = Db::name('building_log')->insert($post);
            Db::name('village')->where('id', $post['building_id'])->update(['speed_id' => $post['speed_id']]);
            if ($db) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            } else {
                $this->ret['code'] = -1;
                $this->ret['msg'] = '添加失败';
            }
            return json($this->ret);
        } else {
            $id = $this->request->get('id');
            $this->assign('id', $id);
            $speed = Db::name('speed')
                ->select();
            $this->assign('speed', $speed);
            return $this->fetch('log_add');
        }
    }
    /**
     * 在建工地管理-编辑在建工地
     */
    public function logEdit($id, $village_id)
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
            $village_id = $post['village_id'];
            $NoticeModel = new BuildLogModel;
            // save方法第二个参数为更新条件
            unset($post['village_id']);
            $res = $NoticeModel->save($post, ['id', $id]);
            Db::name('village')->where('id', $village_id)->update(['speed_id' => $post['speed_id']]);
            //    var_dump(Db::name('building_log')->getLastSql());die;
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            } else {
                $this->ret['code'] = -1;
                $this->ret['msg'] = '修改失败';
            }
            return json($this->ret);
        } else {
            $speed = Db::name('speed')
                ->select();
            $data = Db::name('building_log')->where('id', $id)->find();
            $this->assign('speed', $speed);
            $this->assign('data', $data);
            $this->assign('village_id', $village_id);
            return $this->fetch('log_edit');
        }
    }
    /**
     * 在建工地管理-删除日志
     */
    public function delLog()
    {
        $id = $this->request->post('id');
        $res = Db::name('building_log')->where('id', $id)->delete();
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 在建工地管理-批量删除日志
     */
    public function delLogAll()
    {
        $delList = $this->request->post('delList');
        $delList = json_decode($delList, true);
        foreach ($delList as $k => $v) {
            $res = Db::name('building_log')->delete($v);
        }
        // var_dump(Db::name('mechanic')->getLastSql());die;
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
