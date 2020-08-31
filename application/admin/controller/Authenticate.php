<?php

namespace app\admin\controller;

use \think\Db;

class Authenticate extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 认证管理-列表页面
     */
    public function index($type)
    {
        if (request()->isPost()) {
            $type  = $this->request->get('type');
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $keywords = $this->request->param('keywords', '');
            $city = $this->request->param('city', '');
            $province = $this->request->param('province', '');
            $country = $this->request->param('country', '');
            $where = '';
            if ($keywords) {
                $where = " AND (B.uname LIKE '%" . $keywords . "%' OR B.mobile LIKE '%" . $keywords . "%') ";
            }
            $where1 = '';
            if ($city) {
                $where1 = " AND B.city =" . $city;
            }
            $where2 = '';
            if ($province) {
                $where2 = " AND  B.province = " . $province;
            }
            $where3 = '';
            if ($country) {
                $where3 = " AND (B.country =" . $country;
            }
            $page_start = ($page - 1) * $limit;
            $sql = "SELECT A.*,B.uname,B.mobile,
                        CASE WHEN A.status = 1 THEN '是' ELSE '否' END AS is_pass,
                        concat(C.region_name,'-',D.region_name,'-',E.region_name) AS city
                FROM lg_authenticate A 
                INNER JOIN lg_member B on A.uid = B.id
                INNER JOIN lg_region C ON B.province = C.region_code
                INNER JOIN lg_region D ON B.city = D.region_code
                INNER JOIN lg_region E ON B.county = E.region_code
                WHERE A.type = " . $type . " AND A.status = 0" . $where . $where1 . $where2 . $where3 . "
                ORDER BY id DESC
                limit $page_start,$limit";
            // var_dump($sql);die;
            $data = Db::query($sql);
            $sql1 = "SELECT COUNT(1) AS count FROM lg_authenticate A 
        INNER JOIN lg_member B on A.uid = B.id
        INNER JOIN lg_region C ON B.province = C.region_code
        INNER JOIN lg_region D ON B.city = D.region_code
        INNER JOIN lg_region E ON B.county = E.region_code
        WHERE A.type = " . $type . " AND A.status = 0" . $where . $where1 . $where2 . $where3;
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
     * 认证管理-列表页面接口数据
     */
    public function indexList()
    {
    }

    /**
     * 认证管理-列表搜索页面
     */
    public function search()
    {
        $region = _getRegion();
        $type  = $this->request->get('type');
        $this->assign('type', $type);
        $this->assign('regin', $region);
        return $this->fetch('search');
    }
}
