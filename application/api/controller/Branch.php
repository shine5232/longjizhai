<?php
namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Session;
use think\Validate;

class Branch extends Controller
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
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
}
