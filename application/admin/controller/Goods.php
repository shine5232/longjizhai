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
        return $this->fetch();
    }
    /**
     * 商品管理-商品列表数据
     */
    public function index_ajax(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $name = $this->request->param('name','');
        $brand_id = $this->request->param('brand_id','');
        $cate_id = $this->request->param('cate_id','');
        $page_start = ($page - 1) * $limit;
        $where = [];
        if($name){
            $where['a.name'] = ['like',"%$name%"];
        }
        if($brand_id){
            $where['a.brand_id'] = ['eq',$brand_id];
        }
        if($cate_id){
            $where['a.cate_id'] = ['eq',$cate_id];
        }
        $data = Db::name('goods_info')->alias('a')
            ->join('goods_cate b','b.id = a.cate_id','LEFT')
            ->join('brands c','c.id = a.brand_id','LEFT')
            ->where($where)
            ->order('a.id DESC')
            ->limit($page_start,$limit)
            ->field('a.*,b.title as cate_title,c.name as brand_name')
            ->select();
        $count = Db::name('goods_info')->alias('a')
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
    }
    /**
     * 商品管理-添加商品页面
     */
    public function goodsAdd(){
        $cate = _getGoodsCate();//获取顶级分类
        $this->assign('cate',$cate);
        return $this->fetch('goods_add');
    }
    /**
     * 商品管理-添加商品数据
     */
    public function addGoods(){
        $post     = $this->request->post();
        $post['cate'] = array_filter($post['cate']);
        $cate_id =  end($post['cate']);
        $insert = [
            'name'=>$post['name'],
            'keywords'=>$post['keywords'],
            'thumb'=>$post['thumb'],
            'title'=>$post['title'],
            'unit'=>$post['unit'],
            'brand_id'=>$post['brand_id']>0?$post['brand_id']:'',
            'cate_id'=>$cate_id,
            'content'=>$post['content'],
            'create_time'=>date('Y-m-d H:i:s')
        ];
        $db = Db::name('goods_info')->insert($insert);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = '添加成功';
        }
        return json($this->ret);
    }
    /**
     * 商品管理-编辑商品页面
     */
    public function goodsEdit(){
        $id  = $this->request->get('id');
        $goods_info = Db::name('goods_info')->where('id',$id)->find();
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
     * 商品管理-编辑商品数据
     */
    public function editGoods(){
        $post     = $this->request->post();
        $id = $post['id'];
        $post['cate'] = array_filter($post['cate']);
        $cate_id = end($post['cate']);
        $update = [
            'name'=>$post['name'],
            'keywords'=>$post['keywords'],
            'thumb'=>$post['thumb'],
            'title'=>$post['title'],
            'unit'=>$post['unit'],
            'brand_id'=>$post['brand_id']>0?$post['brand_id']:'',
            'cate_id'=>$cate_id,
            'content'=>$post['content'],
            'update_time'=>date('Y-m-d H:i:s')
        ];
        Db::name('goods_info')->where('id',$id)->update($update);
        $this->ret['code'] = 200;
        $this->ret['msg'] = '修改成功';
        return json($this->ret);
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
            $res = Db::name('goods_info')->where('id','in',$id)->update($upd);
        }else{
            $res = Db::name('goods_info')->where('id',$ids[0])->update($upd);
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 商品管理-属性列表页面
     */
    public function goodsAttr(){
        $id  = $this->request->get('id');
        $this->assign('id',$id);
        return $this->fetch('goods_attr');
    }
    /**
     * 商品管理-属性列表数据请求
     */
    public function attr_ajax(){
        $goods_id = $this->request->get('goods_id');
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $page_start = ($page - 1) * $limit;
        $where['a.status'] = ['eq',0];
        $where['a.goods_id'] = ['eq',$goods_id];
        $data = Db::name('goods_attr')->alias('a')
            ->join('goods_info b','b.id = a.goods_id','INNER')
            ->where($where)
            ->field('a.*,b.unit')
            ->order('a.id DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('goods_attr')->alias('a')
            ->join('goods_info b','b.id = a.goods_id','INNER')
            ->where($where)
            ->count();
        if($data){
            $this->ret['count'] = $count;
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 商品管理-属性添加页面
     */
    public function attrAdd(){
        $goods_id = $this->request->get('goods_id');
        $this->assign('goods_id',$goods_id);
        return $this->fetch('attr_add');
    }
    /**
     * 商品管理-属性添加处理
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
        $db = Db::name('goods_attr')->insert($insert);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = '添加成功';
        }
        return json($this->ret);
    }
    /**
     * 商品管理-属性编辑页面
     */
    public function attrEdit(){
        $id  = $this->request->get('id');
        $goods_attr = Db::name('goods_attr')->where('id',$id)->find();
        $this->assign('goods_attr',$goods_attr);
        return $this->fetch('attr_edit');
    }
    /**
     * 商品管理-编辑属性数据
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
        Db::name('goods_attr')->where('id',$id)->update($update);
        $this->ret['code'] = 200;
        $this->ret['msg'] = '修改成功';
        return json($this->ret);
    }
    /**
     * 商品管理-属性删除
     */
    public function deleteAttr(){
        $id = $this->request->post('id');
        $ids = explode(',',$id);
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if(count($ids) > 1){
            $res = Db::name('goods_attr')->where('id','in',$id)->update($upd);
        }else{
            $res = Db::name('goods_attr')->where('id',$ids[0])->update($upd);
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 商品管理-相册列表页面
     */
    public function goodsImg(){
        $id  = $this->request->get('id');
        $count = Db::name('goods_img')->where('goods_id',$id)->count();
        $this->assign('id',$id);
        $this->assign('count',$count);
        return $this->fetch('goods_img');
    }
    /**
     * 商品管理-相册列表数据请求
     */
    public function img_ajax(){
        $goods_id = $this->request->get('goods_id');
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $page_start = ($page - 1) * $limit;
        $where['goods_id'] = $goods_id;
        $data = Db::name('goods_img')
            ->where($where)
            ->order('sort DESC,id DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('goods_img')
            ->where($where)
            ->count();
        if($data){
            $this->ret['count'] = $count;
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 商品管理-相册添加页面
     */
    public function imgAdd(){
        $goods_id = $this->request->get('goods_id');
        $count = Db::name('goods_img')->where('goods_id',$goods_id)->count();
        $this->assign('count',$count);
        $this->assign('goods_id',$goods_id);
        return $this->fetch('img_add');
    }
    /**
     * 商品管理-相册添加处理
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
        $db = Db::name('goods_img')->insertAll($insert);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = '添加成功';
        }
        return json($this->ret);
    }
    /**
     * 商品管理-相册编辑页面
     */
    public function imgEdit(){
        $id  = $this->request->get('id');
        $goods_attr = Db::name('goods_img')->where('id',$id)->find();
        $this->assign('goods_img',$goods_attr);
        return $this->fetch('img_edit');
    }
    /**
     * 商品管理-编辑相册数据
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
        Db::name('goods_img')->where('id',$id)->update($update);
        $this->ret['code'] = 200;
        $this->ret['msg'] = '修改成功';
        return json($this->ret);
    }
    /**
     * 商品管理-相册删除
     */
    public function deleteImg(){
        $id = $this->request->post('id');
        $ids = explode(',',$id);
        if(count($ids) > 1){
            $res = Db::name('goods_img')->where('id','in',$id)->delete();
        }else{
            $res = Db::name('goods_img')->where('id',$ids[0])->delete();
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
