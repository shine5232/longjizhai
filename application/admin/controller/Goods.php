<?php
namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class Goods extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 商品管理-商品列表页
     */
    public function index(){
        if(request()->isAjax()){
            $page = $this->request->param('page',1,'intval');
            $limit = $this->request->param('limit',20,'intval');
            $name = $this->request->param('name','');
            $brand_id = $this->request->param('brand_id','');
            $cate_id = $this->request->param('cate_id','');
            $checked = $this->request->param('checked', '-1');
            $user = session('user');
            $page_start = ($page - 1) * $limit;
            $where['a.status'] = ['eq',0];
            if($name){
                $where['a.name'] = ['like',"%$name%"];
            }
            if($brand_id){
                $where['a.brand_id'] = ['eq',$brand_id];
            }
            if ($checked > '-1') {
                $where['a.checked'] = ['eq', $checked];
            }
            if ($cate_id) {
                $where[] = ['exp','FIND_IN_SET('.$cate_id.',a.cate_id)'];
            }
            if($user['county']){
                $where['a.county'] = ['eq',$user['county']];
            }
            $data = Db::name('shop_goods')->alias('a')
                ->join('goods_cate b','b.id = a.cate_id','LEFT')
                ->join('brands c','c.id = a.brand_id','LEFT')
                ->where($where)
                ->order('a.sort ASC')
                ->limit($page_start,$limit)
                ->field('a.*,b.title as cate_title,c.name as brand_name')
                ->select();
            $count = Db::name('shop_goods')->alias('a')
                ->join('goods_cate b','b.id = a.cate_id','LEFT')
                ->where($where)
                ->count();
            if($data){
                foreach($data as &$vo){
                    $vo['cate_title'] = _getAllCateTitle($vo['cate_id']);
                }
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch();
        }
    } 
    /**
     * 商品管理-添加商品
     */
    public function goodsAdd(){
        if (request()->isPost()){
            $post     = $this->request->post();
            $insert = [
                'name'=>$post['name'],
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
        }else{
            $cate = _getGoodsCate();//获取顶级分类
            $this->assign('cate',$cate);
            return $this->fetch('goods_add');
        }
    }
    /**
     * 商品管理-编辑商品
     */
    public function goodsEdit(){
        if (request()->isPost()){
            $post     = $this->request->post();
            $id = $post['id'];
            $update = [
                'name'=>$post['name'],
                'keywords'=>$post['keywords'],
                'thumb'=>$post['thumb'],
                'title'=>$post['title'],
                'style' => $post['style'], 
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
        }else{
            $id  = $this->request->get('id');
            $goods_info = Db::name('shop_goods')->where('id',$id)->find();
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
            $goods_info['style'] = explode(',',$goods_info['style']);
            $this->assign('cate_id',$cate_id);
            $this->assign('cate',$cate);
            $this->assign('brands',$brands);
            $this->assign('goods_info',$goods_info);
            return $this->fetch('goods_edit');
        }
    }
    /**
     * 商品管理-商品搜索页面
     */
    public function search(){
        $cate = _getGoodsCate();//获取顶级分类
        $this->assign('cate',$cate);
        return $this->fetch();
    }
    /**
     * 商品管理-商品删除
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
     * 商品管理-规格列表页面
     */
    public function goodsAttr(){
        if (request()->isGet()) {
            $goods_id  = $this->request->get('goods_id');
            $where['a.status'] = ['eq',0];
            $where['a.pid'] = ['eq', 0];
            $where['a.goods_id'] = ['eq',$goods_id];
            $data = Db::name('shop_goods_attr')->alias('a')
                ->where($where)
                ->field('a.*')
                ->order('a.sort ASC')
                ->select();
            $this->assign('id',$goods_id);
            $this->assign('data', $data);
            return $this->fetch('goods_attr');
        }
    }
    /**
     * 商品管理-规格添加页面
     */
    public function attrAdd(){
        if (request()->isPost()){
            $post     = $this->request->post();
            $form = $post['form'];
            $goods_id = $post['goods_id'];
            foreach($form as $key=>$vo){
                if($vo['id']){//更新数据
                    $upd = [
                        'name' => $vo['name'],
                        'price' => $vo['price'],
                        'shop_price' => $vo['shop_price'],
                        'thumb' => $vo['thumb'],
                        'sort' => $vo['sort'],
                        'unit' => $vo['unit'],
                        'paytype' => $vo['pay_one']?1:0,
                        'pay_one' => $vo['pay_one'],
                        'pay_two' => $vo['pay_two'],
                        'pay_three' => $vo['pay_three'],
                        'online' => isset($vo['online']) ? $vo['online'] : 0,
                        'goods_id' => $goods_id,
                        'update_time' => date('Y-m-d H:i:s')
                    ];
                    $db = Db::name('shop_goods_attr')->where('id',$vo['id'])->update($upd);
                }else{//插入新数据
                    $insert = [
                        'name' => $vo['name'],
                        'price' => $vo['price'],
                        'shop_price' => $vo['shop_price'],
                        'thumb' => $vo['thumb'],
                        'sort' => $vo['sort'],
                        'unit' => $vo['unit'],
                        'paytype' => $vo['pay_one']?1:0,
                        'pay_one' => $vo['pay_one'],
                        'pay_two' => $vo['pay_two'],
                        'pay_three' => $vo['pay_three'],
                        'online' => isset($vo['online']) ? $vo['online'] : 0,
                        'goods_id' => $goods_id,
                        'create_time' => date('Y-m-d H:i:s')
                    ];
                    $db = Db::name('shop_goods_attr')->insert($insert);
                }
            }
            $this->ret['code'] = 200;
            $this->ret['msg'] = '添加成功';
            return json($this->ret);
        }
    }
    /**
     * 商品管理-规格编辑页面
     */
    public function attrEdit(){
        if (request()->isPost()){
            $post     = $this->request->post();
            $id = $post['id'];
            $update = [
                'name'=>$post['name'],
                'price'=>$post['price'],
                'shop_price'=>$post['shop_price'],
                'specs'=>$post['specs'],
                'thumb'=>$post['thumb'],
                'unit' => $post['unit'],
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
        }else{
            $id  = $this->request->get('id');
            $goods_attr = Db::name('shop_goods_attr')->where('id',$id)->find();
            $this->assign('goods_attr',$goods_attr);
            return $this->fetch('attr_edit');
        }
    }
    /**
     * 商品管理-规格删除
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
     * 商家管理-商品属性列表页面
     */
    public function unintList()
    {
        if (request()->isGet()) {
            
            $pid  = $this->request->get('pid');
            $where['a.status'] = ['eq', 0];
            $where['a.pid'] = ['eq', $pid];
            $data = Db::name('shop_goods_attr')->alias('a')
                ->where($where)
                ->field('a.*')
                ->order('a.sort ASC,a.id DESC')
                ->select();
            $this->assign('data', $data);
            $this->assign('pid', $pid);
            return $this->fetch('goods_unint');
        }
    }
    /**
     * 商家管理-属性添加页面
     */
    public function unintAdd()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $form = $post['form'];
            $pid = $post['pid'];
            $goods_id = Db::name('shop_goods_attr')->where('id',$pid)->value('goods_id');
            foreach($form as $key=>$vo){
                if($vo['id']){//更新数据
                    $upd = [
                        'name' => $vo['name'],
                        'price' => $vo['price'],
                        'shop_price' => $vo['shop_price'],
                        'thumb' => $vo['thumb'],
                        'sort' => $vo['sort'],
                        'unit' => $vo['unit'],
                        'ku' => $vo['ku'],
                        'yun' => $vo['yun'],
                        'pid'=>$pid,
                        'online' => isset($vo['online']) ? $vo['online'] : 0,
                        'goods_id' => $goods_id,
                        'update_time' => date('Y-m-d H:i:s')
                    ];
                    $db = Db::name('shop_goods_attr')->where('id',$vo['id'])->update($upd);
                }else{//插入新数据
                    $insert = [
                        'name' => $vo['name'],
                        'price' => $vo['price'],
                        'shop_price' => $vo['shop_price'],
                        'thumb' => $vo['thumb'],
                        'sort' => $vo['sort'],
                        'unit' => $vo['unit'],
                        'ku' => $vo['ku'],
                        'yun' => $vo['yun'],
                        'pid'=>$pid,
                        'online' => isset($vo['online']) ? $vo['online'] : 0,
                        'goods_id' => $goods_id,
                        'create_time' => date('Y-m-d H:i:s')
                    ];
                    $db = Db::name('shop_goods_attr')->insert($insert);
                }
            }
            $this->ret['code'] = 200;
            $this->ret['msg'] = '添加成功';
            return json($this->ret);
        }
    }
    /**
     * 商家管理-属性编辑页面
     */
    public function unintEdit()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
            $update = [
                'name' => $post['name'],
                'price' => $post['price'],
                'shop_price' => $post['shop_price'],
                'thumb' => $post['thumb'],
                'yun' => $post['yun'],
                'ku' => $post['ku'],
                'unit' => $post['unit'],
                'paytype' => isset($post['paytype']) ? $post['paytype'] : 0,
                'pay_one' => $post['pay_one'],
                'pay_two' => $post['pay_two'],
                'pay_three' => $post['pay_three'],
                'online' => isset($post['online']) ? $post['online'] : 0,
                'update_time' => date('Y-m-d H:i:s')
            ];
            Db::name('shop_goods_attr')->where('id', $id)->update($update);
            $this->ret['code'] = 200;
            $this->ret['msg'] = '修改成功';
            return json($this->ret);
        } else {
            $id  = $this->request->get('id');
            $goods_attr = Db::name('shop_goods_attr')->where('id', $id)->find();
            $this->assign('goods_attr', $goods_attr);
            return $this->fetch('unint_edit');
        }
    }
    /**
     * 商品管理-相册列表页面
     */
    public function goodsImg(){
        if(request()->isAjax()){
            $goods_id = $this->request->get('goods_id');
            $page = $this->request->param('page',1,'intval');
            $limit = $this->request->param('limit',20,'intval');
            $page_start = ($page - 1) * $limit;
            $where['goods_id'] = $goods_id;
            $data = Db::name('shop_goods_img')
                ->where($where)
                ->order('sort ASC,id DESC')
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
        }else{
            $id  = $this->request->get('id');
            $count = Db::name('shop_goods_img')->where('goods_id',$id)->count();
            $this->assign('id',$id);
            $this->assign('count',$count);
            return $this->fetch('goods_img');
        }
    }
    /**
     * 商品管理-相册添加页面
     */
    public function imgAdd(){
        if (request()->isPost()){
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
        }else{
            $goods_id = $this->request->get('goods_id');
            $count = Db::name('shop_goods_img')->where('goods_id',$goods_id)->count();
            $this->assign('count',$count);
            $this->assign('goods_id',$goods_id);
            return $this->fetch('img_add');
        }
    }
    /**
     * 商品管理-相册编辑页面
     */
    public function imgEdit(){
        if (request()->isPost()){
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
        }else{
            $id  = $this->request->get('id');
            $goods_attr = Db::name('shop_goods_img')->where('id',$id)->find();
            $this->assign('goods_img',$goods_attr);
            return $this->fetch('img_edit');
        }
    }
    /**
     * 商品管理-相册删除
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
