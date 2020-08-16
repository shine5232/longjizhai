<?php
namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class Shop extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 商家管理-商家列表页
     */
    public function index(){
        return $this->fetch();
    }
    /**
     * 商家管理-商家列表数据
     */
    public function index_ajax(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $name = $this->request->param('name','');
        $shop_cate = $this->request->param('shop_cate','');
        $province = $this->request->param('province','');
        $city = $this->request->param('city','');
        $county = $this->request->param('county','');
        $page_start = ($page - 1) * $limit;
        $where['a.status'] = ['eq',0];
        if($name){
            $where['a.name'] = ['like',"%$name%"];
        }
        if($province){
            $where['a.province'] = ['eq',$province];
        }
        if($city){
            $where['a.city'] = ['eq',$city];
        }
        if($county){
            $where['a.county'] = ['eq',$county];
        }
        if($shop_cate){
            $where['a.shop_cate'] = ['eq',$shop_cate];
        }
        $data = Db::name('shop')->alias('a')
            ->join('region b','b.region_code = a.province','LEFT')
            ->join('region c','c.region_code = a.city','LEFT')
            ->join('region d','d.region_code = a.county','LEFT')
            ->join('member e','e.id = a.uid','LEFT')
            ->join('goods_cate f','f.id = a.shop_cate','LEFT')
            ->where($where)
            ->field('a.*,b.region_name as province_name,c.region_name as city_name,d.region_name as county_name,e.realname,e.mobile,f.title as cate_title')
            ->order('a.id DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('shop')->alias('a')
            ->join('region b','b.region_code = a.province','LEFT')
            ->join('region c','c.region_code = a.city','LEFT')
            ->join('region d','d.region_code = a.county','LEFT')
            ->join('member e','e.id = a.uid','LEFT')
            ->where($where)
            ->count();
        if($data){
            $this->ret['count'] = $count;
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 商家管理-添加商家页面
     */
    public function shopAdd(){
        $cate = _getGoodsCate();//获取顶级分类
        $province = _getRegion();//获取省份数据
        $level = _getLevel(17);//获取分组等级
        $this->assign('cate',$cate);
        $this->assign('province',$province);
        $this->assign('level',$level);
        return $this->fetch('shop_add');
    }
    /**
     * 商家管理-添加商家数据
     */
    public function addShop(){
        $post     = $this->request->post();
        $insert = [
            'name'=>$post['name'],
            'province'=>$post['province'],
            'city'=>$post['city'],
            'county'=>$post['county'],
            'level'=>$post['level'],
            'address'=>$post['address'],
            'shop_cate'=>$post['shop_cate'],
            'longitude'=>$post['longitude'],
            'latitude'=>$post['latitude'],
            'view_num'=>$post['view_num'],
            'collect_num'=>$post['collect_num'],
            'sort'=>$post['sort'],
            'square_logo'=>$post['thumb1'],
            'rectangle_logo'=>$post['thumb2'],
            'content'=>$post['content'],
            'create_time'=>date('Y-m-d H:i:s')
        ];
        $db = Db::name('shop')->insert($insert);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = '添加成功';
        }
        return json($this->ret);
    }
    /**
     * 商家管理-编辑商家页面
     */
    public function shopEdit(){
        $id  = $this->request->get('id');
        $shop = Db::name('shop')->where('id',$id)->find();
        $cate = _getGoodsCate();//获取顶级分类
        $province = _getRegion();//获取省份数据
        $city = _getRegion($shop['province'],false,true);//获取城市数据
        $county = _getRegion($shop['city'],false,true);//获取区县数据
        $level = _getLevel(17);//获取分组等级
        $this->assign('cate',$cate);
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('county',$county);
        $this->assign('level',$level);
        $this->assign('shop',$shop);
        return $this->fetch('shop_edit');
    }
    /**
     * 商家管理-编辑商家数据
     */
    public function editShop(){
        $post     = $this->request->post();
        $id = $post['id'];
        $update = [
            'name'=>$post['name'],
            'province'=>$post['province'],
            'city'=>$post['city'],
            'county'=>$post['county'],
            'level'=>$post['level'],
            'address'=>$post['address'],
            'shop_cate'=>$post['shop_cate'],
            'longitude'=>$post['longitude'],
            'latitude'=>$post['latitude'],
            'view_num'=>$post['view_num'],
            'collect_num'=>$post['collect_num'],
            'sort'=>$post['sort'],
            'square_logo'=>$post['thumb1'],
            'rectangle_logo'=>$post['thumb2'],
            'content'=>$post['content'],
            'update_time'=>date('Y-m-d H:i:s')
        ];
        Db::name('shop')->where('id',$id)->update($update);
        $this->ret['code'] = 200;
        $this->ret['msg'] = '修改成功';
        return json($this->ret);
    }
    /**
     * 商家管理-商家搜索页面
     */
    public function search(){
        $cate = _getGoodsCate();//获取顶级分类
        $region = _getRegion();//获取省份数据
        $this->assign('cate',$cate);
        $this->assign('regin',$region);
        return $this->fetch();
    }
    /**
     * 商家管理-商家删除
     */
    public function deleteShop(){
        $id = $this->request->post('id');
        $ids = explode(',',$id);
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if(count($ids) > 1){
            $res = Db::name('shop')->where('id','in',$id)->update($upd);
        }else{
            $res = Db::name('shop')->where('id',$ids[0])->update($upd);
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 商家管理-会员绑定/解绑页面
     */
    public function userAdd(){
        $id  = $this->request->get('id');
        $shop = Db::name('shop')->alias('a')
                ->join('member b','b.id = a.uid','LEFT')
                ->join('member_weixin c','c.openid = b.openid','LEFT')
                ->where('a.id',$id)
                ->field('a.*,b.realname,b.mobile,c.nickname,c.avatar')
                ->find();
        $this->assign('id',$id);
        if($shop['uid']){
            $this->assign('shop',$shop);
            return $this->fetch('user_del');
        }else{
            $this->assign('county',$shop['county']);
            return $this->fetch('user_add');
        }
    }
    /**
     * 商家管理-会员搜索页面
     */
    public function searchUser(){
        $id  = $this->request->get('id');
        $shop = Db::name('shop')->where('id',$id)->find();
        $this->assign('id',$id);
        $this->assign('county',$shop['county']);
        return $this->fetch('search_user');
    }
    /**
     * 商家管理-会员绑定-列表数据请求
     */
    public function user_ajax(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $county = $this->request->param('county','');
        $realname = $this->request->param('realname','');
        $nickname = $this->request->param('nickname','');
        $mobile = $this->request->param('mobile','');
        $page_start = ($page - 1) * $limit;
        $where['a.subscribe'] = ['eq',1];
        $where['a.type'] = ['eq',5];
        $where['a.locked'] = ['eq',0];
        if($county){
            $where['a.county'] = ['eq',$county];
        }
        if($realname){
            $where['a.realname'] = ['eq',$realname];
        }
        if($mobile){
            $where['a.mobile'] = ['eq',$mobile];
        }
        if($nickname){
            $where['b.nickname'] = ['like',"%$nickname%"];
        }
        $data = Db::name('member')->alias('a')
            ->join('member_weixin b','b.openid = a.openid','INNER')
            ->join('region c','c.region_code = a.province','LEFT')
            ->join('region d','d.region_code = a.city','LEFT')
            ->join('region e','e.region_code = a.county','LEFT')
            ->where($where)
            ->field('a.id,a.mobile,a.realname,b.nickname,b.avatar,c.region_name as province_name,d.region_name as city_name,e.region_name as county_name')
            ->order('a.id DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('member')->alias('a')
            ->join('member_weixin b','b.openid = a.openid','INNER')
            ->where($where)
            ->count();
        if($data){
            $this->ret['count'] = $count;
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 商家管理-会员绑定/解绑数据处理
     */
    public function addUser(){
        $post     = $this->request->post();
        $id = $post['id'];
        $uid = $post['uid'];
        $update1 = [
            'uid'=>$uid,
            'update_time'=>date('Y-m-d H:i:s')
        ];
        $update2 = ['locked' => 1];
        if($post['type']==2){
            $update1['uid'] = '';
            $update2['locked'] = 0;
        }
        Db::name('shop')->where('id',$id)->update($update1);
        Db::name('member')->where('id',$uid)->update($update2);
        $this->ret['code'] = 200;
        $this->ret['msg'] = '关联成功';
        return json($this->ret);
    }
    /**
     * 商家管理-商品列表页面
     */
    public function goodsLis(){
        $shop_id  = $this->request->get('id');
        $cate_id = Db::name('shop')->where('id',$shop_id)->value('shop_cate');
        $this->assign('shop_id',$shop_id);
        $this->assign('cate_id',$cate_id);
        return $this->fetch('goods_lis');
    }
    /**
     * 商家管理-商品搜索页面
     */
    public function searchGoods(){
        $cate_id  = $this->request->get('cate_id');
        $cate = _getGoodsCate();//获取顶级分类
        $this->assign('cate',$cate);
        $this->assign('cate_id',$cate_id);
        return $this->fetch('search_goods');
    }
    /**
     * 商家管理-商品列表数据请求
     */
    public function goods_ajax(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $shop_id = $this->request->param('shop_id','');
        $cate_id = $this->request->param('cate_id','');
        $brand_id = $this->request->param('brand_id','');
        $page_start = ($page - 1) * $limit;
        $where['a.shop_id'] = ['eq',$shop_id];
        $where['a.status'] = ['eq',0];
        if($cate_id){
            $where['a.cate_id'] = ['eq',$cate_id];
        }
        if($brand_id){
            $where['a.brand_id'] = ['eq',$brand_id];
        }
        $data = Db::name('shop_goods')->alias('a')
            ->join('brands b','b.id = a.brand_id','LEFT')
            ->where($where)
            ->field('a.id,a.name,a.thumb,a.online,a.cate_id,a.title,b.name as brand_name')
            ->order('a.id DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('shop_goods')->alias('a')
            ->join('brands b','b.id = a.brand_id','LEFT')
            ->where($where)
            ->count();            
        $datas = [];
        if($data){
            foreach($data as $key=>$vo){
                $datas[$key] = $vo;
                $datas[$key]['cate_title'] = _getAllCateTitle($vo['cate_id']);
            }
            $this->ret['count'] = $count;
            $this->ret['data'] = $datas;
        }
        return json($this->ret);
    }
    /**
     * 商家管理-商品添加页面
     */
    public function goodsAdd(){
        $cate = _getGoodsCate();//获取顶级分类
        $shop_id  = $this->request->get('shop_id');
        $cate_id  = $this->request->get('cate_id');
        $this->assign('cate',$cate);
        $this->assign('cate_id',$cate_id);
        $this->assign('shop_id',$shop_id);
        return $this->fetch('goods_add');
    }
    /**
     * 商家管理-商品添加数据处理
     */
    public function addGoods(){
        $post     = $this->request->post();
        $county = Db::name('shop')->where('id',$post['shop_id'])->value('county');
        $insert = [
            'name'=>$post['name'],
            'shop_id'=>$post['shop_id'],
            'county'=>$county,
            'keywords'=>$post['keywords'],
            'thumb'=>$post['thumb'],
            'title'=>$post['title'],
            'unit'=>$post['unit'],
            'brand_id'=>$post['brand_id']>0?$post['brand_id']:'',
            'cate_id'=>$post['cate_id'],
            'content'=>$post['content'],
            'create_time'=>date('Y-m-d H:i:s')
        ];
        $db = Db::name('shop_goods')->insert($insert);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = '添加成功';
        }
        return json($this->ret);
    }
    /**
     * 商家管理-商品编辑页面
     */
    public function goodsEdit(){
        $shop_goods_id  = $this->request->get('id');
        $goods_info = Db::name('shop_goods')->where('id',$shop_goods_id)->find();
        //根据当前分类获取上级所有分类
        $pid = $goods_info['cate_id'];
        $cate_id = [$goods_info['cate_id']];
        $cate = [];
        while($pid != 0){
            $pid = _getGoodsCate($pid,true);
            if($pid != 0){
                $cate_id[] = $pid; 
            }
            $cate[] = _getGoodsCate($pid);
        }
        $cate_id = array_reverse($cate_id);
        $cate = array_reverse($cate);
        //根据分类获取品牌数据
        $brands_id = Db::name('goods_cate')->where('status',1)->where('id',$goods_info['cate_id'])->value('brands');
        $brands = [];
        if($brands_id){
            $where = [
                'id'=>['in',$brands_id],
                'status'=>0
            ];
            $brands = Db::name('brands')->where($where)->field('id,name')->select();
        }
        $this->assign('cate_id',$cate_id);
        $this->assign('cate',$cate);
        $this->assign('brands',$brands);
        $this->assign('goods_info',$goods_info);
        return $this->fetch('goods_edit');
    }
    /**
     * 商家管理-编辑商品数据
     */
    public function editGoods(){
        $post     = $this->request->post();
        $id = $post['id'];
        $update = [
            'name'=>$post['name'],
            'keywords'=>$post['keywords'],
            'thumb'=>$post['thumb'],
            'title'=>$post['title'],
            'unit'=>$post['unit'],
            'brand_id'=>$post['brand_id']>0?$post['brand_id']:'',
            'cate_id'=>$post['cate_id'],
            'content'=>$post['content'],
            'update_time'=>date('Y-m-d H:i:s')
        ];
        Db::name('shop_goods')->where('id',$id)->update($update);
        $this->ret['code'] = 200;
        $this->ret['msg'] = '修改成功';
        return json($this->ret);
    }
    /**
     * 商家管理-商品删除
     */
    public function deleteGoods(){
        $id = $this->request->post('id');
        $ids = explode(',',$id);
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if(count($ids) > 1){
            $res = Db::name('shop_goods')->where('id','in',$id)->update($upd);
        }else{
            $res = Db::name('shop_goods')->where('id',$ids[0])->update($upd);
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 商家管理-商品属性列表页面
     */
    public function goodsAttr(){
        $shop_goods_id  = $this->request->get('id');
        $this->assign('shop_goods_id',$shop_goods_id);
        return $this->fetch('goods_attr');
    }
    /**
     * 商家管理-商品属性列表数据请求
     */
    public function attr_ajax(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $shop_goods_id = $this->request->param('shop_goods_id','');
        $page_start = ($page - 1) * $limit;
        $where['a.status'] = ['eq',0];
        $where['a.goods_id'] = ['eq',$shop_goods_id];
        $data = Db::name('shop_goods_attr')->alias('a')
            ->join('shop_goods b','b.id = a.goods_id','INNER')
            ->where($where)
            ->field('a.*,b.unit')
            ->order('a.id DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('shop_goods_attr')->alias('a')
            ->join('shop_goods b','b.id = a.goods_id','INNER')
            ->where($where)
            ->count();   
        if($data){
            $this->ret['count'] = $count;
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 商家管理-属性添加页面
     */
    public function attrAdd(){
        $goods_id = $this->request->get('shop_goods_id');
        $this->assign('goods_id',$goods_id);
        return $this->fetch('attr_add');
    }
    /**
     * 商家管理-属性添加处理
     */
    public function addAttr(){
        $post     = $this->request->post();
        $insert = [
            'name'=>$post['name'],
            'price'=>$post['price'],
            'shop_price'=>$post['shop_price'],
            'specs'=>$post['specs'],
            'thumb'=>$post['thumb'],
            'paytype'=>isset($post['paytype'])?$post['paytype']:0,
            'pay_one'=>$post['pay_one'],
            'pay_two'=>$post['pay_two'],
            'pay_three'=>$post['pay_three'],
            'online'=>isset($post['online'])?$post['online']:0,
            'goods_id'=>$post['goods_id'],
            'create_time'=>date('Y-m-d H:i:s')
        ];
        $db = Db::name('shop_goods_attr')->insert($insert);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = '添加成功';
        }
        return json($this->ret);
    }
    /**
     * 商家管理-属性编辑页面
     */
    public function attrEdit(){
        $id  = $this->request->get('id');
        $goods_attr = Db::name('shop_goods_attr')->where('id',$id)->find();
        $this->assign('goods_attr',$goods_attr);
        return $this->fetch('attr_edit');
    }
    /**
     * 商家管理-编辑属性数据
     */
    public function editAttr(){
        $post     = $this->request->post();
        $id = $post['id'];
        $update = [
            'name'=>$post['name'],
            'price'=>$post['price'],
            'shop_price'=>$post['shop_price'],
            'specs'=>$post['specs'],
            'thumb'=>$post['thumb'],
            'paytype'=>isset($post['paytype'])?$post['paytype']:0,
            'pay_one'=>$post['pay_one'],
            'pay_two'=>$post['pay_two'],
            'pay_three'=>$post['pay_three'],
            'online'=>isset($post['online'])?$post['online']:0,
            'update_time'=>date('Y-m-d H:i:s')
        ];
        Db::name('shop_goods_attr')->where('id',$id)->update($update);
        $this->ret['code'] = 200;
        $this->ret['msg'] = '修改成功';
        return json($this->ret);
    }
    /**
     * 商家管理-属性删除
     */
    public function deleteAttr(){
        $id = $this->request->post('id');
        $ids = explode(',',$id);
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if(count($ids) > 1){
            $res = Db::name('shop_goods_attr')->where('id','in',$id)->update($upd);
        }else{
            $res = Db::name('shop_goods_attr')->where('id',$ids[0])->update($upd);
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 商家管理-相册列表页面
     */
    public function goodsImg(){
        $id  = $this->request->get('id');
        $count = Db::name('shop_goods_img')->where('goods_id',$id)->count();
        $this->assign('id',$id);
        $this->assign('count',$count);
        return $this->fetch('shop_goods_img');
    }
    /**
     * 商家管理-相册列表数据请求
     */
    public function img_ajax(){
        $shop_goods_id = $this->request->get('shop_goods_id');
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $page_start = ($page - 1) * $limit;
        $where['goods_id'] = $shop_goods_id;
        $data = Db::name('shop_goods_img')
            ->where($where)
            ->order('sort DESC,id DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('shop_goods_img')
            ->where($where)
            ->count();
        if($data){
            $this->ret['count'] = $count;
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 商家管理-相册添加页面
     */
    public function imgAdd(){
        $goods_id = $this->request->get('shop_goods_id');
        $count = Db::name('shop_goods_img')->where('goods_id',$goods_id)->count();
        $this->assign('count',$count);
        $this->assign('goods_id',$goods_id);
        return $this->fetch('img_add');
    }
    /**
     * 商家管理-相册添加处理
     */
    public function addImg(){
        $post     = $this->request->post();
        foreach($post['img'] as $key=>$v){
            $insert[] = [
                'goods_id' => $post['goods_id'],
                'create_time' => date('Y-m-d H:i:s'),
                'img' => $v
            ];
        }
        $db = Db::name('shop_goods_img')->insertAll($insert);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = '添加成功';
        }
        return json($this->ret);
    }
    /**
     * 商家管理-相册编辑页面
     */
    public function imgEdit(){
        $id  = $this->request->get('id');
        $goods_attr = Db::name('shop_goods_img')->where('id',$id)->find();
        $this->assign('goods_img',$goods_attr);
        return $this->fetch('img_edit');
    }
    /**
     * 商家管理-编辑相册数据
     */
    public function editImg(){
        $post     = $this->request->post();
        $id = $post['id'];
        $update = [
            'title'=>$post['title'],
            'sort'=>$post['sort'],
            'img'=>$post['img'],
            'update_time'=>date('Y-m-d H:i:s')
        ];
        Db::name('shop_goods_img')->where('id',$id)->update($update);
        $this->ret['code'] = 200;
        $this->ret['msg'] = '修改成功';
        return json($this->ret);
    }
    /**
     * 商家管理-相册删除
     */
    public function deleteImg(){
        $id = $this->request->post('id');
        $ids = explode(',',$id);
        if(count($ids) > 1){
            $res = Db::name('shop_goods_img')->where('id','in',$id)->delete();
        }else{
            $res = Db::name('shop_goods_img')->where('id',$ids[0])->delete();
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
