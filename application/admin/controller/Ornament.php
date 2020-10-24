<?php

namespace app\admin\controller;

use \think\Db;
use think\Session;

class ornament extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 装修需求-需求统计
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $user = Session::get('user');
            $where['A.status'] = ['neq',2];
            if($user['county']){
                $where['A.province'] = $user['province'];
                $where['A.city'] = $user['city'];
                $where['A.county'] = $user['county'];
            }
            $data = Db::name('ornament')->alias('A')
                ->join('member B','A.uid = B.uid','INNER')
                ->join('region C', 'A.province = C.region_code', 'LEFT')
                ->join('region D', 'A.city = D.region_code', 'LEFT')
                ->join('region E', 'A.county = E.region_code', 'LEFT')
                ->where($where)->order('A.id DESC')->limit($page_start, $limit)
                ->field('A.*,B.uname,C.region_name as province_name,D.region_name as city_name,E.region_name as country_name')
                ->select();
            $count = Db::name('ornament')->alias('A')
                ->join('member B','A.uid = B.uid','INNER')
                ->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            return $this->fetch('');
        }
    }
    /**
     * 第三方机构-访问查看
     */
    public function look(){
        $id  = $this->request->get('id');
        $data = Db::name('ornament')->alias('A')
            ->join('member B','A.uid = B.uid','INNER')
            ->join('region C', 'A.province = C.region_code', 'LEFT')
            ->join('region D', 'A.city = D.region_code', 'LEFT')
            ->join('region E', 'A.county = E.region_code', 'LEFT')->where('A.id', $id)
            ->field('A.*,B.uname,C.region_name as province_name,D.region_name as city_name,E.region_name as country_name')
            ->find();
        
        return  $this->fetch('look', ['data' => $data]);
    }
    /**
     * 第三方机构-访问搜索
     */
    public function searchCount()
    {
        $where = ['status'=>['neq',2]];
        $party = Db::name('thirdparty')->where($where)->order('id DESC')->select();
        return $this->fetch('search_count',['party'=>$party]);
    }
}
