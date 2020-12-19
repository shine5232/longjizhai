<?php
namespace app\api\controller;

use think\Db;

class Shop extends Main
{
    /**
     * 根据城市获取推荐商家
     */
    public function getShopHot(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['county'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            if($post['county'] == '0'){
                $where['zong'] = 1;
            }else{
                $where['county'] = $post['county'];
            }
            $where['status'] = 0;
            $where['hot'] = 1;
            $data = Db::name('shop')->where($where)->field('id,rectangle_logo')->order('sort DESC,id DESC')->select();
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
    /**
     * 根据条件获取商家列表
     */
    public function getShopList(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['county']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            if($post['county'] == '0'){
                $where['A.zong'] = 1;
            }else{
                $where['A.county'] = $post['county'];
            }
            if(isset($post['cate']) && $post['cate']){
                $where['A.shop_cate'] = $post['cate'];
            }
            if(isset($post['rank']) && $post['rank']){
                $where['B.rank_id'] = $post['rank'];
            }
            if(isset($post['keyword']) && $post['keyword'] != ''){
                $where['A.name'] = ['like','%'.$post['keyword'].'%'];
            }
            $order = 'A.sort DESC';
            if(isset($post['order']) && $post['order']){
                if($post['order'] == '1'){//最新排序
                    $order = 'A.id DESC';
                }elseif($post['order'] == '2'){//热门排序
                    $order = 'A.hot DESC';
                }elseif($post['order'] == '3'){//口碑排序
                    $order = 'A.kou DESC';
                }
            }
            $where['A.status'] = 0;
            $end_time = date('Y-m-d H:i:s');
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $data = Db::name('shop')->alias('A')
                ->join('member B','B.id = A.uid','INNER')
                ->join('member_rank C','C.id = B.rank_id','INNER')
                ->where($where)->field('A.id,A.rectangle_logo AS img,A.name,B.rank_id,C.rank_name')
                ->where("A.endtime >= '".$end_time."' OR A.endtime IS NULL")
                ->order($order)
                ->limit($page_start, $limit)
                ->select();
            if($data){
                foreach($data as &$v){
                    $v['img'] = _getServerName().$v['img'];
                }
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
    /**
     * 根据商家id查询详情
     */
    public function getShopInfo(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $data['info'] = Db::name('shop')->alias('A')
                ->join('member B','B.id = A.uid','LEFT')
                ->join('member_rank C','C.id = B.rank_id','INNER')
                ->where('A.id',$post['id'])
                ->field('A.id,A.name,A.square_logo AS img,A.score,A.view_num AS view,A.address,A.content,B.rank_id AS rank,B.authed AS ren,B.mobile,B.realname AS user,C.rank_name')
                ->find();
            $data['cate'] = Db::name('shop_cate')
                ->where('shop_id',$post['id'])
                ->where('status',1)
                ->order('sort ASC')->field('id AS value,cate_name AS text')
                ->select();
            if($data['info']){
                $data['info']['img'] = _getServerName().$data['info']['img'];
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
    /**
     * 根据分类查询当前商铺的商品
     */
    public function getGoodsByShop(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id']) || !isset($post['cate']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            if($post['cate']){
                $where['A.self_cate_id'] = $post['cate'];
            }
            $where['A.shop_id'] = $post['id'];
            $where['A.online'] = 1;
            $where['A.status'] = 0;
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $data = Db::name('shop_goods')->alias('A')
                ->where($where)->field('A.id,A.name,A.thumb AS img,A.unit')
                ->order('A.id DESC')
                ->limit($page_start, $limit)
                ->select();
            if($data){
                foreach($data as $key=>$v){
                    $attr = Db::name('shop_goods_attr')->where(['goods_id'=>$v['id'],'pid'=>0])->order('sort ASC')->field('shop_price,price')->find();
                    $data[$key]['price'] = $attr['price'];
                    $data[$key]['shop_price'] = $attr['shop_price'];
                    $data[$key]['img'] = _getServerName()."/public".$v['img'];
                }
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
    /**
     * 手机端获取推荐商家数据
     */
    public function getRecommendShopLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['county']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            if($post['county']){
                $where['A.county'] = $post['county'];
            }else{
                $where['A.is_zong'] = 1;
            }
            $where['A.status'] = 1;
            $where['A.recommend_id'] = 2;
            $end_time = date('Y-m-d H:i:s');
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $data = Db::name('recommend_data')->alias('A')
                ->join('shop B','B.id = A.object_id','INNER')
                ->where($where)
                ->where("A.end_time >= '".$end_time."' OR A.end_time = '' OR B.endtime = ''")
                ->field('A.object_id AS id,A.img')
                ->order('A.sort DESC')
                ->limit($page_start, $limit)
                ->select();
            if($data){
                foreach($data as &$v){
                    $v['img'] = _getServerName().$v['img'];
                }
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
