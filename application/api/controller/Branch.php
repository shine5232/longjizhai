<?php
namespace app\api\controller;

use think\Db;
use think\Session;
use think\Validate;

class Branch extends Main
{
    /**
     * 分站列表数据
     */
    public function index(){
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
     * 增加分站数据
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
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
        }
        return json($this->ret);
    }
    /**
     * 更新分站状态
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
     * 删除分站
     */
    public function deleteBranch(){
        $branch_id = $this->request->param('branch_id');
        if(is_array($branch_id)){

        }else{
            $upd = [
                'deleted' =>  1,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
            $result = Db::name('branch')->where('id',$branch_id)->update($upd);
            if($result){
                $this->ret['code'] = 200;
            }
        }
        return json($this->ret);
    }
    public function region(){
        $res = Db::name('region')
        ->field('region_id,region_name,region_short_name,region_code')
        ->where('region_level','1')
        ->select(); 
        $count = Db::name('region')
        ->where('region_level','1')
        ->count(); 
        $this->ret['count'] = $count;
        $this->ret['data'] = $res;
        return json($this->ret);
    }
    public function city_list(){
        $region_id = $this->request->param('region_id');
        $sql = "SELECT A.region_id,A.region_name,A.region_short_name,A.region_code FROM lg_region A INNER JOIN lg_region B ON A.region_superior_code = B.region_code AND B.region_id = ".$region_id;
        $sql2 = "SELECT count(1) FROM lg_region A INNER JOIN lg_region B ON A.region_superior_code = B.region_code AND B.region_id = ".$region_id;
         $data = Db::query($sql);
        $count = Db::query($sql2);
        $this->ret['count'] = $count;
        $this->ret['data'] = $data;
        return json($this->ret);

    }
    public function country_list(){
        $region_id = $this->request->param('region_id');
        $sql = "SELECT A.region_id,A.region_name,A.region_short_name,A.region_code FROM lg_region A INNER JOIN lg_region B ON A.region_superior_code = B.region_code AND B.region_id = ".$region_id;
        $sql2 = "SELECT count(1) FROM lg_region A INNER JOIN lg_region B ON A.region_superior_code = B.region_code AND B.region_id = ".$region_id;
         $data = Db::query($sql);
        $count = Db::query($sql2);
        $this->ret['count'] = $count;
        $this->ret['data'] = $data;
        return json($this->ret);

    }
    public function addCounty(){
        $post     = $this->request->post();
        $validate = validate('Region');
        if (!$validate->check($post)) {
            $this->ret['msg'] = $validate->getError();
        } else {
            $sql ="SELECT MAX(region_id) FROM lg_region WHERE region_superior_code = ".$post['city']." AND region_name = '".$post['region_name']."'";
            // var_dump($sql);die;
            $region_name = Db::query($sql);
            if($region_name){
                $this->ret['code'] = -1;
                $this->ret['msg'] = '区县已存在';
                return json($this->ret);
            }
            $region_code = Db::name('region')
            ->where('region_superior_code',$post['city'])
            ->max('region_id');
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
    /**
     * 根据城市code获取分站名称
     */
    public function getBranch(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['county'])){
                $this->ret['msg'] = '缺少参数county';
                return json($this->ret);
            }
            if($post['county'] == '0'){
                $this->ret['code'] = 200;
                $this->ret['data'] = '龙吉宅-总站';
                return json($this->ret);
            }else{
                $where['county'] = $post['county'];
            }
            $data = Db::name('branch')->where($where)->value('branch_name');
            if($data){
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '暂无数据';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
