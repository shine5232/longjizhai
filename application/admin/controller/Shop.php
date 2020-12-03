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
                ->join('member e', 'e.id = a.uid', 'LEFT')
                ->join('member_rank g','g.id = e.rank_id','LEFT')
                ->join('goods_cate f', 'f.id = a.shop_cate', 'LEFT')
                ->where($where)
                ->field('a.*,e.area,e.realname,e.mobile,e.uname,e.subor,e.superior_id,e.rank_id,f.title as cate_title,g.rank_name')
                ->order('a.id DESC')
                ->limit($page_start, $limit)
                ->select();
            $count = Db::name('shop')->alias('a')
                ->join('member e', 'e.id = a.uid', 'LEFT')
                ->where($where)
                ->count();
            if ($data) {
                foreach($data as &$v){
                    $v['superior_id'] = Db::name('member')->where('uid',$v['superior_id'])->value('uname');
                }
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
                'starttime' => $post['starttime'],
                'endtime' => $post['endtime'],
                'hot'=>$post['hot'],
                'kou'=>$post['kou'],
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
                'kou'=>$post['kou'],
                'starttime' => $post['starttime'],
                'endtime' => $post['endtime'],
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
    /**
     * 商家管理-商品分类列表页
     */
    public function cateLis()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $shop_id = $this->request->param('shop_id', '');
            $where['shop_id'] = ['eq', $shop_id];
            $where['status'] = ['neq', 2];
            $data = Db::name('shop_cate')->where($where)->order('sort DESC,id DESC')->limit($page_start, $limit)->select();
            $count = Db::name('shop_cate')->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $shop_id  = $this->request->get('id');
            $this->assign('shop_id', $shop_id);
            return $this->fetch('cate_lis');
        }
    }
    /**
     * 商家管理-添加商品分类
     */
    public function cateAdd()
    {
        if(request()->isPost()){
            $post = $this->request->post();
            $status = isset($post['status'])?$post['status']:0;
            $insert = $post;
            $insert['status']=$status;
            $insert['create_time']=date('Y-m-d H:i:s');
            $res = Db::name('shop_cate')->insert($insert);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            $shop_id  = $this->request->get('shop_id');
            $this->assign('shop_id', $shop_id);
            return $this->fetch('cate_add');
        }
    }
    /**
     * 商家管理-编辑商品分类
     */
    public function cateEdit(){
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $status = isset($post['status'])?$post['status']:0;
            $upd = $post;
            $upd['status']=$status;
            $upd['update_time']=date('Y-m-d H:i:s');
            unset($upd['id']);
            $res = Db::name('shop_cate')->where('id',$id)->update($upd);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '更新成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $cate = Db::name('shop_cate')->where('id',$id)->find();
            $this->assign('cate',$cate);
            return $this->fetch('cate_edit');
        }
    }
    /**
     * 商家管理-商品分类删除
     */
    public function cateDel()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'status'    =>  2,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('shop_cate')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('shop_cate')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 商家管理-商铺推送至推荐位
     */
    public function recommend(){
        if(request()->isPost()){
            $res = true;
            $post = $this->request->post();
            $status = isset($post['status'])?$post['status']:0;
            $is_zong = isset($post['is_zong'])?$post['is_zong']:0;
            $insert = $post;
            $insert['status']=$status;
            $insert['is_zong']=$is_zong;
            $insert['create_time']=date('Y-m-d H:i:s');
            $where = [
                'object_id' => $post['object_id'],
                'recommend_id' => $post['recommend_id'],
                'type' => $post['type']
            ];
            $has = Db::name('recommend_data')->where($where)->find();
            if(!$has){
                if($post['type'] == 1){
                    $data = Db::name('shop_goods')->where('id',$post['object_id'])->find();
                    $insert['title'] = $data['name'];
                    $insert['img'] = '/public'.$data['thumb'];
                }else if($post['type'] == 2){
                    $data = Db::name('shop')->where('id',$post['object_id'])->find();
                    $insert['title'] = $data['name'];
                    $insert['img'] = $data['rectangle_logo'];
                }else if($post['type'] == 3){
                    $data = Db::name('cases')->where('id',$post['object_id'])->find();
                    $insert['title'] = $data['case_title'];
                    $insert['img'] = $data['thumb'];
                }else if($post['type'] == 4){
                    $data = Db::name('article')->where('id',$post['object_id'])->find();
                    $insert['title'] = $data['title'];
                    $insert['img'] = '/public'.$data['thumb'];
                }
                $insert['county'] = $data['county'];
                $res = Db::name('recommend_data')->insert($insert);
            }
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            $shop_id  = $this->request->get('id');
            $type  = $this->request->get('type');
            $recommend = Db::name('recommend')->where(['status'=>1,'type'=>$type])->select();
            $this->assign('shop_id', $shop_id);
            $this->assign('recommend', $recommend);
            $this->assign('type', $type);
            return $this->fetch('recommend');
        }
    }
}
