<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;
use think\Validate;
header('Access-Control-Allow-Origin: *');
class Branch extends Controller
{
    /**
     * 分站列表页面
     */
    public function index(){
        $this->assign('branch_name','');
        $this->assign('status','');
        return $this->fetch();
    }
    /**
     * 新增分站页面
     */
    public function branchAdd()
    {
        $region = _getRegion();
        $this->assign('regin',$region);
        return $this->fetch('add');
    }
    /**
     * 搜索分站页面
     */
    public function search(){
        $region = _getRegion();
        $this->assign('regin',$region);
        return $this->fetch();
    }
    //编辑页面
    public function branchEdit($id)
    {
        $data = Db::name('branch')
            ->alias('a')
            ->join('region d','a.province = d.region_code','LEFT')
            ->join('region e','a.city = e.region_code','LEFT')
            ->join('region f','a.city = f.region_code','LEFT')
            ->field('a.*,d.region_name as province_name,e.region_name as city_name,f.region_name as county_name')
            ->where('id', $id)
            ->find();
        $province = _getRegion();
        $city = _getRegion($data['province']);
        $county = _getRegion($data['city']);
        $this->assign('data', $data);
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('county', $county);
        return $this->fetch('edit');
    }
    public function region(){
        return $this->fetch('region_list');
    }
    public function city_list($region_id){
        $this->assign('region_id', $region_id);
        return $this->fetch();
    }
    public function county_list($region_id){
        $this->assign('region_id', $region_id);
        return $this->fetch();
    }
    
    public function countyAdd(){
        $region = _getRegion();
        $this->assign('regin',$region);
        return $this->fetch('county_add');
    }
}
