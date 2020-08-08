<?php
namespace app\admin\controller;

use \think\Db;
use app\admin\model\Village as VillageModel;

class Building extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
   
    /**
     * 在建工地管理-在建工地列表
     */
    public function index(){
        return $this->fetch();
    }
    /**
     * 在建工地管理-业主在建工地列表数据
     */
    public function index_list(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $keywords = $this->request->param('keywords','');
        $city = $this->request->param('city','');
        $province = $this->request->param('province','');
        $country = $this->request->param('country','');
        $where = '';
        if($keywords){
            $where = " AND A.village_name LIKE '%".$keywords."%'";
        }
        $where1 = '';
        if($city){
            $where1 = " AND A.city =".$city;
        }
        $where2 = '';
        if($province){
            $where2 = " AND  A.province = ".$province;
        }
        $where3 = '';
        if($country){
            $where3 = " AND (A.country =".$country;
        }
        $page_start = ($page - 1) * $limit;
        $sql = "SELECT A.id,A.village_addr,A.village_name,concat(B.region_name,'-',C.region_name,'-',D.region_name) AS city,area,price,E.name AS speed_name
                FROM lg_village A 
                INNER JOIN lg_region B ON A.province = B.region_code
                INNER JOIN lg_region C ON A.city = C.region_code
                INNER JOIN lg_region D ON A.county = D.region_code
                INNER JOIN lg_speed E ON A.speed_id = E.id
                WHERE A.village_type = 2 AND A.status = 0".$where.$where1.$where2.$where3."
                ORDER BY id DESC
                limit $page_start,$limit";
        // var_dump($sql);die;
        $data = Db::query($sql);
        $sql1 = "SELECT COUNT(1) AS count FROM lg_village A WHERE A.village_type = 2 AND A.status = 0".$where.$where1.$where2.$where3;
        $count = Db::query($sql1);
        // var_dump($count);die;
        if($data){
            $this->ret['count'] = $count[0]['count'];
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 在建工地管理-添加在建工地页面
     */
    // addBuilding
    public function buildingAdd(){
        $company = Db::name('company')
                ->where('status = 0')
                ->select();
        $region = _getRegion();
        $this->assign('regin',$region);
        $this->assign('company',$company);
        return $this->fetch('add');
    }
    /**
     * 业主在建工地管理-添加在建工地数据
     */
    public function addBuilding(){
        $post     = $this->request->post();
        $post['create_time'] = date('Y-m-d H:i:S');
        $post['village_type'] = 2;
        $row = Db::name('village')
        ->where('village_name',$post['village_name'])
        ->where('village_addr',$post['village_addr'])
        ->where('province',$post['province'])
        ->where('city',$post['city'])
        ->where('county',$post['county'])
        ->find();
        // var_dump(Db::name('village')->getLastSql());die;
        // var_dump($row);die;
        if($row){
            $this->ret['code'] = -1;
            $this->ret['msg'] = '在建工地已存在';
            return json($this->ret);
        }
        $db = Db::name('village')->insert($post);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }else{
            $this->ret['code'] = -1;
            $this->ret['msg'] = '添加失败';
        }
        return json($this->ret);
    }
    /**
     * 在建工地管理-编辑在建工地页面
     */
    public function buildingEdit(){
        $company = Db::name('company')
                ->where('status = 0')
                ->select();
        $id  = $this->request->get('id');
        $data = Db::name('village')->where('id',$id)->find();
        $province = _getRegion();
        $city = _getRegion($data['province']);
        $county = _getRegion($data['city'],false,true);
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('county', $county);
        $this->assign('data',$data);
        $this->assign('company', $company);
        return $this->fetch('edit');
    }
    /**
     * 在建工地管理-编辑在建工地数据
     */
    public function editBuilding(){
        $post     = $this->request->post();
        $id = $post['id'];
        $row = Db::name('village')
        ->where('village_name',$post['village_name'])
        ->where('village_addr',$post['village_addr'])
        ->where('province',$post['province'])
        ->where('city',$post['city'])
        ->where('county',$post['county'])
        ->where('id','<>',$id)
        ->find();
        // var_dump(Db::name('village')->getLastSql());die;
        // var_dump($row);die;
        if($row){
            $this->ret['code'] = -1;
            $this->ret['msg'] = '在建工地已存在';
            return json($this->ret);
        }
        $NoticeModel = new VillageModel;
        // save方法第二个参数为更新条件
        $res = $NoticeModel->save($post,['id',$id]);
        // var_dump($db);die;
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }else{
            $this->ret['code'] = -1;
            $this->ret['msg'] = '修改失败';
        }
        return json($this->ret);
    }
    /**
     * 在建工地管理-在建工地搜索页面
     */
    public function search(){
        $region = _getRegion();
        $this->assign('regin',$region);
        return $this->fetch();
    }
    /**
     * 在建工地管理-在建工地删除
     */
    public function delBuilding(){
        $id = $this->request->post('id');
        $upd = [
            'status'    =>  1,
            'del_time'   =>  date('Y-m-d H:i:s')
        ];
        $res = Db::name('village')->where('id',$id)->update($upd);
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}