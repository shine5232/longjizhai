<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;
use think\Validate;
header('Access-Control-Allow-Origin: *');
class Branch extends Controller
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 分站管理-分站列表页面
     */
    public function index(){
        return $this->fetch();
    }
    /**
     * 分站管理-新增分站页面
     */
    public function branchAdd()
    {
        $region = _getRegion();
        $this->assign('regin',$region);
        return $this->fetch('add');
    }
    /**
     * 分站管理-搜索分站页面
     */
    public function search(){
        $region = _getRegion();
        $this->assign('regin',$region);
        return $this->fetch();
    }
    /**
     * 分站管理-编辑分站页面
     */
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
        $county = _getRegion($data['city'],false,true);
        $this->assign('data', $data);
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('county', $county);
        return $this->fetch('edit');
    }
    /**
     * 城市管理-省份列表页
     */
    public function region(){
        return $this->fetch('region_list');
    }
    /**
     * 城市管理-城市列表页
     */
    public function city_list($region_id){
        $this->assign('region_id', $region_id);
        return $this->fetch();
    }
    /**
     * 城市管理-区县列表页
     */
    public function county_list($region_id){
        $this->assign('region_id', $region_id);
        return $this->fetch();
    }
    /**
     * 城市管理-添加区县页
     */
    public function countyAdd(){
        $region = _getRegion();
        $this->assign('regin',$region);
        return $this->fetch('county_add');
    }
    /**
     * 分站管理-分站列表数据
     */
    public function index_ajax(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $branch_name = $this->request->param('branch_name','');
        $province = $this->request->param('province','');
        $city = $this->request->param('city','');
        $county = $this->request->param('county','');
        $status = $this->request->param('status',0);
        $page_start = ($page - 1) * $limit;
        $where = array('deleted'=>0);
        if($branch_name){
            $where['branch_name'] = ['like',"%$branch_name%"];
        }
        if($province){
            $where['province'] = $province;
        }
        if($city){
            $where['city'] = $city;
        }
        if($county){
            $where['county'] = $county;
        }
        if($status){
            $where['status'] = $status;
        }
        $data = Db::name('branch')->alias('a')
            ->join('region d','a.province = d.region_code','LEFT')
            ->join('region e','a.city = e.region_code','LEFT')
            ->join('region f','a.county = f.region_code','LEFT')
            ->where($where)
            ->field('a.*,d.region_name as province_name,e.region_name as city_name,f.region_name as county_name')
            ->order('a.id asc')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('branch')
            ->alias('a')
            ->join('region d','a.province = d.region_code','LEFT')
            ->join('region e','a.city = e.region_code','LEFT')
            ->join('region f','a.county = f.region_code','LEFT')
            ->where($where)
            ->count();
        if($data){
            $this->ret['count'] = $count;
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 分站管理-增加分站数据
     */
    public function addBranch()
    {
        $post     = $this->request->post();
        $validate = validate('Branch');
        if (!$validate->check($post)) {
            $this->ret['msg'] = $validate->getError();
        } else {
            $post['status'] = isset($post['status'])?$post['status']:0;
            $post['create_time']   = date('Y-m-d h:i:s');
            $db = Db::name('branch')->insert($post);
            if($db){
                //锁定城市
                Db::name('region')->where('region_code',$post['county'])->update(array('is_open'=>1));
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
        }
        return json($this->ret);
    }
    /**
     * 分站管理-更新分站状态
     */
    public function updateStatus(){
        $branch_id = $this->request->param('branch_id');
        $branch = Db::name('branch')->where('id',$branch_id)->find();
        $data = [];
        if($branch){
            $data = [
                'status'        =>  $branch['status']==0?1:0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('branch')->where('id',$branch_id)->update($data);
        if($result){
            $this->ret['code'] = 200;
        }
        $this->ret['data'] = $data['status'];
        return json($this->ret);
    }
    /**
     * 分站管理-删除分站
     */
    public function deleteBranch(){
        $branch_id = $this->request->param('branch_id');
        if(is_array($branch_id)){

        }else{
            $upd = [
                'deleted' =>  1,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
            $branch = Db::name('branch')->where('id',$branch_id)->find();
            $result = Db::name('branch')->where('id',$branch_id)->update($upd);
            if($result){
                //释放城市
                Db::name('region')->where('region_code',$branch['county'])->update(array('is_open'=>0));
                $this->ret['code'] = 200;
            }
        }
        return json($this->ret);
    }
    /**
     * 城市管理-省份列表数据
     */
    public function region_list_ajax(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $page_start = ($page - 1) * $limit;
        $res = Db::name('region')
        ->field('region_id,region_name,region_short_name,region_code')
        ->where('region_level','1')
        ->limit($page_start,$limit)
        ->select(); 
        $count = Db::name('region')
        ->where('region_level','1')
        ->count(); 
        $this->ret['count'] = $count;
        $this->ret['data'] = $res;
        return json($this->ret);
    }
    /**
     * 城市管理-城市列表数据
     */
    public function city_list_ajax(){
        $region_id = $this->request->param('region_id');
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $page_start = ($page - 1) * $limit;
        $sql = "SELECT A.region_id,A.region_name,A.region_short_name,A.region_code FROM lg_region A INNER JOIN lg_region B ON A.region_superior_code = B.region_code AND B.region_id = ".$region_id." LIMIT ".$page_start.",".$limit;
        $sql2 = "SELECT count(1) FROM lg_region A INNER JOIN lg_region B ON A.region_superior_code = B.region_code AND B.region_id = ".$region_id;
        $data = Db::query($sql);
        $count = Db::query($sql2);
        $this->ret['count'] = $count[0]['count(1)'];
        $this->ret['data'] = $data;
        return json($this->ret);
    }
    /**
     * 城市管理-区县列表数据
     */
    public function country_list_ajax(){
        $region_id = $this->request->param('region_id');
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $page_start = ($page - 1) * $limit;
        $sql = "SELECT A.region_id,A.region_name,A.region_short_name,A.region_code FROM lg_region A INNER JOIN lg_region B ON A.region_superior_code = B.region_code AND B.region_id = ".$region_id." LIMIT ".$page_start.",".$limit;
        $sql2 = "SELECT count(1) FROM lg_region A INNER JOIN lg_region B ON A.region_superior_code = B.region_code AND B.region_id = ".$region_id;
        $data = Db::query($sql);
        $count = Db::query($sql2);
        $this->ret['count'] = $count[0]['count(1)'];
        $this->ret['data'] = $data;
        return json($this->ret);

    }
    /**
     * 城市管理-添加区县数据
     */
    public function addCounty(){
        $post     = $this->request->post();
        $validate = validate('Region');
        if (!$validate->check($post)) {
            $this->ret['msg'] = $validate->getError();
        } else {
            $sql ="SELECT MAX(region_id) FROM lg_region WHERE region_superior_code = ".$post['city']." AND region_name = '".$post['region_name']."'";
            $region_name = Db::query($sql);
            if($region_name[0]['MAX(region_id)']){
                $this->ret['code'] = -1;
                $this->ret['msg'] = '区县已存在';
                return json($this->ret);
            }
            $region_code = Db::name('region')->max('region_id');
            $res['region_name'] = $post['region_name'];
            $res['region_create_time'] = date('Y-m-d h:i:s');
            $res['region_code'] = $region_code+1;
            $res['region_short_name'] = $post['region_name'];
            $res['region_superior_code'] = $post['city'];
            $db = Db::name('region')->insert($res);
            if($db){
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
        }
        return json($this->ret);
    }
}
