<?php

namespace app\admin\controller;

use \think\Db;
use app\admin\model\Village as VillageModel;

class Community extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];

    /**
     * 小区管理-业主小区列表
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
            $sql = "SELECT A.id,A.village_addr,A.village_name,concat(B.region_name,'-',C.region_name,'-',D.region_name) AS city
                    FROM lg_village A 
                    INNER JOIN lg_region B ON A.province = B.region_code
                    INNER JOIN lg_region C ON A.city = C.region_code
                    INNER JOIN lg_region D ON A.county = D.region_code
                    WHERE A.village_type = 1 AND A.status = 0" . $where . "
                    ORDER BY id DESC
                    limit $page_start,$limit";
            // var_dump($sql);die;
            $data = Db::query($sql);
            $sql1 = "SELECT COUNT(1) AS count FROM lg_village A WHERE A.village_type = 1 AND A.status = 0" . $where;
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
     * 小区管理-添加小区页面
     */
    public function communityAdd()
    {
        $user = session('user');
        if (request()->isPost()) {
            $post     = $this->request->post();
            $post['create_time'] = date('Y-m-d H:i:S');
            $post['village_type'] = 1;
            $where = [];
            if($post['province']){
                $where['province'] = $post['province'];
            }
            if($post['city']){
                $where['city'] = $post['city'];
            }
            if($post['county']){
                $where['county'] = $post['county'];
            }
            $row = Db::name('village')
                ->where('village_name', $post['village_name'])
                ->where('village_addr', $post['village_addr'])
                ->where($where)
                ->find();
            // var_dump(Db::name('village')->getLastSql());die;
            // var_dump($row);die;
            if ($row) {
                $this->ret['code'] = -1;
                $this->ret['msg'] = '小区已存在';
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
            $region = _getRegion();
            $this->assign('regin', $region);
            $this->assign('user', $user);
            return $this->fetch('community_add');
        }
    }
    /**
     * 小区管理-编辑小区页面
     */
    public function communityEdit()
    {
        $user = session('user');
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
            $where = [];
            if($post['province']){
                $where['province'] = $post['province'];
            }
            if($post['city']){
                $where['city'] = $post['city'];
            }
            if($post['county']){
                $where['county'] = $post['county'];
            }
            $row = Db::name('village')
                ->where('village_name', $post['village_name'])
                ->where('village_addr', $post['village_addr'])
                ->where($where)
                ->where('id', '<>', $id)
                ->find();
            // var_dump(Db::name('village')->getLastSql());die;
            // var_dump($row);die;
            if ($row) {
                $this->ret['code'] = -1;
                $this->ret['msg'] = '小区已存在';
                return json($this->ret);
            }
            $row1 = Db::name('village')
                ->where('village_name', $post['village_name'])
                ->where('village_addr', $post['village_addr'])
                ->where($where)
                ->where('id', $id)
                ->find();
            // var_dump($row1);die;
            if ($row1) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
                return json($this->ret);
            }
            $db = Db::name('village')->where('id', $id)->update($post);
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
            $where['id'] = $id;
            $data = Db::name('village')->where($where)->find();
            $province = _getRegion();
            $city = _getRegion($data['province']);
            $county = _getRegion($data['city'], false, true);
            $this->assign('province', $province);
            $this->assign('city', $city);
            $this->assign('county', $county);
            $this->assign('data', $data);
            $this->assign('user', $user);
            return $this->fetch('community_edit');
        }
    }
    /**
     * 小区管理-小区搜索页面
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
     * 小区管理-小区删除
     */
    public function delCommunity()
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
     * 小区管理-小区批量删除
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
}
