<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;
use think\Validate;

header('Access-Control-Allow-Origin: *');
class Branch extends Controller
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 分站管理-分站列表页面
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $branch_name = $this->request->param('branch_name', '');
            $province = $this->request->param('province', '');
            $city = $this->request->param('city', '');
            $county = $this->request->param('county', '');
            $status = $this->request->param('status', 0);
            $page_start = ($page - 1) * $limit;
            $where = array('deleted' => 0);
            if ($branch_name) {
                $where['branch_name'] = ['like', "%$branch_name%"];
            }
            if ($province) {
                $where['province'] = $province;
            }
            if ($city) {
                $where['city'] = $city;
            }
            if ($county) {
                $where['county'] = $county;
            }
            if ($status) {
                $where['status'] = $status;
            }
            $data = Db::name('branch')->alias('a')
                ->join('region d', 'a.province = d.region_code', 'LEFT')
                ->join('region e', 'a.city = e.region_code', 'LEFT')
                ->join('region f', 'a.county = f.region_code', 'LEFT')
                ->where($where)
                ->field('a.*,d.region_name as province_name,e.region_name as city_name,f.region_name as county_name')
                ->order('a.id asc')
                ->limit($page_start, $limit)
                ->select();
            $count = Db::name('branch')
                ->alias('a')
                ->join('region d', 'a.province = d.region_code', 'LEFT')
                ->join('region e', 'a.city = e.region_code', 'LEFT')
                ->join('region f', 'a.county = f.region_code', 'LEFT')
                ->where($where)
                ->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            return $this->fetch();
        }
    }
    /**
     * 分站管理-新增分站页面
     */
    public function branchAdd()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $validate = validate('Branch');
            if (!$validate->check($post)) {
                $this->ret['msg'] = $validate->getError();
            } else {
                $post['status'] = isset($post['status']) ? $post['status'] : 0;
                $post['create_time']   = date('Y-m-d h:i:s');
                $db = Db::name('branch')->insert($post);
                if ($db) {
                    //锁定城市
                    Db::name('region')->where('region_code', $post['county'])->update(array('is_open' => 1));
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                }
            }
            return json($this->ret);
        } else {
            $region = _getRegion();
            $this->assign('regin', $region);
            return $this->fetch('add');
        }
    }
    /**
     * 分站管理-搜索分站页面
     */
    public function search()
    {
        $region = _getRegion();
        $this->assign('regin', $region);
        return $this->fetch();
    }
    /**
     * 分站管理-编辑分站页面
     */
    public function branchEdit($id)
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $post['status'] = isset($post['status']) ? $post['status'] : 0;
            $post['update_time']   = date('Y-m-d h:i:s');
            $id = $post['id'];
            $county = Db::name('branch')->where('id', $id)->value('county');
            unset($post['id']);
            $db = Db::name('branch')->where('id', $id)->update($post);
            if ($db) {
                //锁定城市
                if ($county != $post['county']) {
                    Db::name('region')->where('region_code', $county)->update(array('is_open' => 0));
                    Db::name('region')->where('region_code', $post['county'])->update(array('is_open' => 1));
                }
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        } else {
            $data = Db::name('branch')
                ->alias('a')
                ->join('region d', 'a.province = d.region_code', 'LEFT')
                ->join('region e', 'a.city = e.region_code', 'LEFT')
                ->join('region f', 'a.city = f.region_code', 'LEFT')
                ->field('a.*,d.region_name as province_name,e.region_name as city_name,f.region_name as county_name')
                ->where('id', $id)
                ->find();
            $province = _getRegion();
            $city = _getRegion($data['province']);
            $county = _getRegion($data['city'], false, true);
            $this->assign('data', $data);
            $this->assign('province', $province);
            $this->assign('city', $city);
            $this->assign('county', $county);
            return $this->fetch('edit');
        }
    }

    /**
     * 分站管理-更新分站状态
     */
    public function updateStatus()
    {
        $branch_id = $this->request->param('branch_id');
        $branch = Db::name('branch')->where('id', $branch_id)->find();
        $data = [];
        if ($branch) {
            $data = [
                'status'        =>  $branch['status'] == 0 ? 1 : 0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('branch')->where('id', $branch_id)->update($data);
        if ($result) {
            $this->ret['code'] = 200;
        }
        $this->ret['data'] = $data['status'];
        return json($this->ret);
    }
    /**
     * 分站管理-删除分站
     */
    public function deleteBranch()
    {
        $branch_id = $this->request->param('branch_id');
        if (is_array($branch_id)) {
        } else {
            $upd = [
                'deleted' =>  1,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
            $branch = Db::name('branch')->where('id', $branch_id)->find();
            $result = Db::name('branch')->where('id', $branch_id)->update($upd);
            if ($result) {
                //释放城市
                Db::name('region')->where('region_code', $branch['county'])->update(array('is_open' => 0));
                $this->ret['code'] = 200;
            }
        }
        return json($this->ret);
    }
    /**
     * 城市管理-省份列表页
     */
    public function region()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $res = Db::name('region')
                ->field('region_id,region_name,region_short_name,region_code')
                ->where('region_level', '1')
                ->limit($page_start, $limit)
                ->select();
            $count = Db::name('region')
                ->where('region_level', '1')
                ->count();
            $this->ret['count'] = $count;
            $this->ret['data'] = $res;
            return json($this->ret);
        } else {
            return $this->fetch('region_list');
        }
    }
    /**
     * 城市管理-城市列表页
     */
    public function city_list($region_id)
    {
        if (request()->isAjax()) {
            $region_id = $this->request->param('region_id');
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $sql = "SELECT A.region_id,A.region_name,A.region_short_name,A.region_code FROM lg_region A INNER JOIN lg_region B ON A.region_superior_code = B.region_code AND B.region_id = " . $region_id . " LIMIT " . $page_start . "," . $limit;
            $sql2 = "SELECT count(1) FROM lg_region A INNER JOIN lg_region B ON A.region_superior_code = B.region_code AND B.region_id = " . $region_id;
            $data = Db::query($sql);
            $count = Db::query($sql2);
            $this->ret['count'] = $count[0]['count(1)'];
            $this->ret['data'] = $data;
            return json($this->ret);
        } else {
            $this->assign('region_id', $region_id);
            return $this->fetch();
        }
    }
    /**
     * 城市管理-区县列表页
     */
    public function county_list($region_id)
    {
        if (request()->isAjax()) {
            $region_id = $this->request->param('region_id');
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $sql = "SELECT A.region_id,A.region_name,A.region_short_name,A.region_code FROM lg_region A INNER JOIN lg_region B ON A.region_superior_code = B.region_code AND B.region_id = " . $region_id . " ORDER BY A.region_sort ASC LIMIT " . $page_start . "," . $limit;
            $sql2 = "SELECT count(1) FROM lg_region A INNER JOIN lg_region B ON A.region_superior_code = B.region_code AND B.region_id = " . $region_id;
            $data = Db::query($sql);
            $count = Db::query($sql2);
            $this->ret['count'] = $count[0]['count(1)'];
            $this->ret['data'] = $data;
            return json($this->ret);
        } else {
            $this->assign('region_id', $region_id);
            return $this->fetch();
        }
    }
    /**
     * 城市管理-添加区县页
     */
    public function countyAdd()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $validate = validate('Region');
            if (!$validate->check($post)) {
                $this->ret['msg'] = $validate->getError();
            } else {
                $sql = "SELECT MAX(region_id) FROM lg_region WHERE region_superior_code = " . $post['city'] . " AND region_name = '" . $post['region_name'] . "'";
                $region_name = Db::query($sql);
                if ($region_name[0]['MAX(region_id)']) {
                    $this->ret['code'] = -1;
                    $this->ret['msg'] = '区县已存在';
                    return json($this->ret);
                }
                $region_code = Db::name('region')->max('region_id');
                $res['region_name'] = $post['region_name'];
                $res['region_create_time'] = date('Y-m-d h:i:s');
                $res['region_code'] = $region_code + 1;
                $res['region_short_name'] = $post['region_name'];
                $res['region_superior_code'] = $post['city'];
                $res['region_sort'] = $post['region_sort'];
                $res['self_defined'] = 1;
                $res['region_level'] = 3;
                $db = Db::name('region')->insert($res);
                if ($db) {
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                }
            }
            return json($this->ret);
        } else {
            $region = _getRegion();
            $this->assign('regin', $region);
            return $this->fetch('county_add');
        }
    }
    /**
     * 分站管理-数据复制
     */
    public function copy(){
        if(request()->isPost()){
            ini_set('max_execution_time', '0');
            $post = $this->request->post();
            $county_name_from = _getRegionNameByCode($post['county_from']);
            $county_name_to = _getRegionNameByCode($post['county_to']);
            //筛选商家数据
            $shop_where = array('county'=>$post['county_from'],'status'=>0);
            $shop = Db::name('shop')->where($shop_where)->field('id,name,title,shop_cate')->select();
            if($post['county_from'] == $post['county_to']){
                $this->ret['msg'] = '复制失败，数据源与目标分站相同';
                return json($this->ret);
            }
            $shop_to = Db::name('shop')->where(array('county'=>$post['county_to'],'status'=>0))->count();
            if($shop_to){
                $this->ret['msg'] = '复制失败，已有分站数据';
                return json($this->ret);
            }
            if(count($shop)){
                foreach($shop as $shop_key=>$shop_v){
                    //复制商家数据
                    $insert_shop = [
                        'name'=>str_replace($county_name_from['region_short_name'],$county_name_to['region_short_name'],$shop_v['name']),
                        'title'=>$shop_v['title'],
                        'shop_cate'=>$shop_v['shop_cate'],
                        'province'=>$post['province_to'],
                        'city'=>$post['city_to'],
                        'county'=>$post['county_to'],
                        'create_time'=>date('Y-m-d H:i:s')
                    ];
                    //$shop_id = 1;
                    $shop_id = Db::name('shop')->insertGetId($insert_shop);
                    //复制商家商品数据
                    $shop_goods_where = array('shop_id'=>$shop_v['id'],'status'=>0);
                    $shop_goods = Db::name('shop_goods')->where($shop_goods_where)->field('id,name,cate_id,brand_id,keywords,title,content,thumb,unit')->select();
                    $count_goods = count($shop_goods);
                    if($count_goods){
                        foreach($shop_goods as $key_goods=>$goods_v){
                            $insert_goods = [
                                'name'=>$goods_v['name'],
                                'cate_id'=>$goods_v['cate_id'],
                                'brand_id'=>$goods_v['brand_id'],
                                'keywords'=>$goods_v['keywords'],
                                'title'=>$goods_v['title'],
                                'content'=>$goods_v['content'],
                                'thumb'=>$goods_v['thumb'],
                                'unit'=>$goods_v['unit'],
                                'county'=>$post['county_to'],
                                'create_time'=>date('Y-m-d H:i:s'),
                                'shop_id'=>$shop_id
                            ];
                            //var_dump($insert_goods);
                            //$goods_id = 2;
                            $goods_id = Db::name('shop_goods')->insertGetId($insert_goods);
                            //复制商品属性数据
                            $goods_attr_where = array('goods_id'=>$goods_v['id'],'status'=>0);
                            $goods_attr = Db::name('shop_goods_attr')->where($goods_attr_where)->field('id,name,price,shop_price,goods_id,thumb,paytype,pay_one,pay_two,pay_three,specs')->select();
                            $count_attr = count($goods_attr);
                            if($count_attr){
                                foreach($goods_attr as $key_attr=>$attr_v){
                                    $insert_attr = [
                                        'name'=>$attr_v['name'],
                                        'price'=>$attr_v['price'],
                                        'shop_price'=>$attr_v['shop_price'],
                                        'paytype'=>$attr_v['paytype'],
                                        'pay_one'=>$attr_v['pay_one'],
                                        'pay_two'=>$attr_v['pay_two'],
                                        'thumb'=>$attr_v['thumb'],
                                        'pay_three'=>$attr_v['pay_three'],
                                        'specs'=>$attr_v['specs'],
                                        'goods_id'=>$goods_id,
                                        'create_time'=>date('Y-m-d H:i:s')
                                    ];
                                    //var_dump($insert_attr);
                                    Db::name('shop_goods_attr')->insert($insert_attr);
                                    usleep(5000);
                                }
                            }
                            //复制商品图片数据
                            $goods_img_where = array('goods_id'=>$goods_v['id']);
                            $goods_img = Db::name('shop_goods_img')->where($goods_img_where)->field('img,title,sort')->select();
                            $count_img = count($goods_img);
                            if($count_img){
                                foreach($goods_img as $key_img=>$img_v){
                                    $insert_img = [
                                        'img'=>$img_v['img'],
                                        'title'=>$img_v['title'],
                                        'goods_id'=>$goods_id,
                                        'sort'=>$img_v['sort'],
                                        'create_time'=>date('Y-m-d H:i:s')
                                    ];
                                    //var_dump($insert_img);
                                    Db::name('shop_goods_img')->insert($insert_img);
                                    usleep(5000);
                                }
                            }
                            usleep(($count_attr * 5000));
                        }
                    }
                    usleep(($count_goods * 5000));
                }
                $this->ret['code'] = 200;
                $this->ret['msg'] = '复制成功';
            }else{
                $this->ret['msg'] = '复制失败，没有找到数据源';
            }
            return json($this->ret);
        }else{
            $province = _getRegion();
            $this->assign('province',$province);
            return $this->fetch('copy');
        }
    }
}
