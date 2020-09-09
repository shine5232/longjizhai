<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class Shop extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 商家管理-商家列表页
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $name = $this->request->param('name', '');
            $shop_cate = $this->request->param('shop_cate', '');
            $province = $this->request->param('province', '');
            $city = $this->request->param('city', '');
            $county = $this->request->param('county', '');
            $page_start = ($page - 1) * $limit;
            $where['a.status'] = ['eq', 0];
            $user = session('user');
            if ($name) {
                $where['a.name'] = ['like', "%$name%"];
            }
            if ($province) {
                $where['a.province'] = ['eq', $province];
            }
            if ($city) {
                $where['a.city'] = ['eq', $city];
            }
            if ($county) {
                $where['a.county'] = ['eq', $county];
            }
            if ($shop_cate) {
                $where['a.shop_cate'] = ['eq', $shop_cate];
            }
            if($user['county']){
                $where['a.county'] = ['eq', $user['county']];
            }
            $data = Db::name('shop')->alias('a')
                ->join('region b', 'b.region_code = a.province', 'LEFT')
                ->join('region c', 'c.region_code = a.city', 'LEFT')
                ->join('region d', 'd.region_code = a.county', 'LEFT')
                ->join('member e', 'e.id = a.uid', 'LEFT')
                ->join('goods_cate f', 'f.id = a.shop_cate', 'LEFT')
                ->where($where)
                ->field('a.*,b.region_name as province_name,c.region_name as city_name,d.region_name as county_name,e.realname,e.mobile,f.title as cate_title')
                ->order('a.id DESC')
                ->limit($page_start, $limit)
                ->select();
            $count = Db::name('shop')->alias('a')
                ->join('region b', 'b.region_code = a.province', 'LEFT')
                ->join('region c', 'c.region_code = a.city', 'LEFT')
                ->join('region d', 'd.region_code = a.county', 'LEFT')
                ->join('member e', 'e.id = a.uid', 'LEFT')
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
     * 商家管理-添加商家页面
     */
    public function shopAdd()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $insert = [
                'name' => $post['name'],
                'province' => $post['province'],
                'city' => $post['city'],
                'county' => $post['county'],
                'level' => $post['level'],
                'address' => $post['address'],
                'shop_cate' => $post['shop_cate'],
                'longitude' => $post['longitude'],
                'latitude' => $post['latitude'],
                'view_num' => $post['view_num'],
                'hot'=>$post['hot'],
                'collect_num' => $post['collect_num'],
                'sort' => $post['sort'],
                'square_logo' => $post['thumb1'],
                'rectangle_logo' => $post['thumb2'],
                'content' => $post['content'],
                'create_time' => date('Y-m-d H:i:s')
            ];
            $db = Db::name('shop')->insert($insert);
            if ($db) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        } else {
            $cate = _getGoodsCate(); //获取顶级分类
            $province = _getRegion(); //获取省份数据
            $level = _getLevel(17); //获取分组等级
            $this->assign('cate', $cate);
            $this->assign('province', $province);
            $this->assign('level', $level);
            return $this->fetch('shop_add');
        }
    }
    /**
     * 商家管理-编辑商家页面
     */
    public function shopEdit()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
            $update = [
                'name' => $post['name'],
                'province' => $post['province'],
                'city' => $post['city'],
                'county' => $post['county'],
                'level' => $post['level'],
                'hot'=>$post['hot'],
                'address' => $post['address'],
                'shop_cate' => $post['shop_cate'],
                'longitude' => $post['longitude'],
                'latitude' => $post['latitude'],
                'view_num' => $post['view_num'],
                'collect_num' => $post['collect_num'],
                'sort' => $post['sort'],
                'square_logo' => $post['thumb1'],
                'rectangle_logo' => $post['thumb2'],
                'content' => $post['content'],
                'update_time' => date('Y-m-d H:i:s')
            ];
            Db::name('shop')->where('id', $id)->update($update);
            $this->ret['code'] = 200;
            $this->ret['msg'] = '修改成功';
            return json($this->ret);
        } else {
            $id  = $this->request->get('id');
            $shop = Db::name('shop')->where('id', $id)->find();
            $cate = _getGoodsCate(); //获取顶级分类
            $province = _getRegion(); //获取省份数据
            $city = _getRegion($shop['province'], false, true); //获取城市数据
            $county = _getRegion($shop['city'], false, true); //获取区县数据
            $level = _getLevel(17); //获取分组等级
            $this->assign('cate', $cate);
            $this->assign('province', $province);
            $this->assign('city', $city);
            $this->assign('county', $county);
            $this->assign('level', $level);
            $this->assign('shop', $shop);
            return $this->fetch('shop_edit');
        }
    }
    /**
     * 商家管理-商家搜索页面
     */
    public function search()
    {
        $cate = _getGoodsCate(); //获取顶级分类
        $region = _getRegion(); //获取省份数据
        $this->assign('cate', $cate);
        $this->assign('regin', $region);
        return $this->fetch();
    }
    /**
     * 商家管理-商家删除
     */
    public function deleteShop()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('shop')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('shop')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 商家管理-会员绑定/解绑页面
     */
    public function userAdd()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
            $uid = $post['uid'];
            $update1 = [
                'uid' => $uid,
                'update_time' => date('Y-m-d H:i:s')
            ];
            $update2 = ['locked' => 1];
            if ($post['type'] == 2) {
                $update1['uid'] = '';
                $update2['locked'] = 0;
            }
            Db::name('shop')->where('id', $id)->update($update1);
            Db::name('member')->where('id', $uid)->update($update2);
            $this->ret['code'] = 200;
            $this->ret['msg'] = '操作成功';
            return json($this->ret);
        } else {
            if (request()->isAjax()) {
                $page = $this->request->param('page', 1, 'intval');
                $limit = $this->request->param('limit', 20, 'intval');
                $county = $this->request->param('county', '');
                $realname = $this->request->param('realname', '');
                $nickname = $this->request->param('nickname', '');
                $mobile = $this->request->param('mobile', '');
                $page_start = ($page - 1) * $limit;
                $where['a.subscribe'] = ['eq', 1];
                $where['a.type'] = ['eq', 5];
                $where['a.locked'] = ['eq', 0];
                if ($county) {
                    $where['a.county'] = ['eq', $county];
                }
                if ($realname) {
                    $where['a.realname'] = ['eq', $realname];
                }
                if ($mobile) {
                    $where['a.mobile'] = ['eq', $mobile];
                }
                /* if ($nickname) {
                    $where['b.nickname'] = ['like', "%$nickname%"];
                } */
                $data = Db::name('member')->alias('a')
                    //->join('member_weixin b', 'b.openid = a.openid', 'INNER')
                    ->where($where)
                    ->field('a.id,a.mobile,a.realname,a.area')
                    ->order('a.id DESC')
                    ->limit($page_start, $limit)
                    ->select();
                //echo Db::name('member')->getLastSql();die;
                $count = Db::name('member')->alias('a')
                    //->join('member_weixin b', 'b.openid = a.openid', 'INNER')
                    ->where($where)
                    ->count();
                if ($data) {
                    $this->ret['count'] = $count;
                    $this->ret['data'] = $data;
                }
                return json($this->ret);
            } else {
                $id  = $this->request->get('id');
                $shop = Db::name('shop')->alias('a')
                    ->join('member b', 'b.id = a.uid', 'LEFT')
                    //->join('member_weixin c', 'c.openid = b.openid', 'LEFT')
                    ->where('a.id', $id)
                    ->field('a.*,b.realname,b.mobile')
                    ->find();
                $this->assign('id', $id);
                if ($shop['uid']) {
                    $this->assign('shop', $shop);
                    return $this->fetch('user_del');
                } else {
                    $this->assign('county', $shop['county']);
                    return $this->fetch('user_add');
                }
            }
        }
    }
    /**
     * 商家管理-会员搜索页面
     */
    public function searchUser()
    {
        $id  = $this->request->get('id');
        $shop = Db::name('shop')->where('id', $id)->find();
        $this->assign('id', $id);
        $this->assign('county', $shop['county']);
        return $this->fetch('search_user');
    }
}
