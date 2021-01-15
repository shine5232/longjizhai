<?php
namespace app\api\controller;

use think\Db;

class Goods extends Main
{
    /**
     * 根据条件获取商品分类
     */
    public function getGoodsLevel(){
        if(request()->isPost()){
            $post = $this->request->post();
            $parents = isset($post['parents'])?$post['parents']:0;
            $pid = isset($post['pid'])?$post['pid']:0;
            if($parents){//获取当前分类的父分类
                $where = ['id'=>$pid,'status'=>1];
                $data = Db::name('goods_cate')->where($where)->value('pid');
            }else{//获取当前分类的子分类
                if($pid){
                    $where = ['pid'=>$pid,'status'=>1];
                }else{
                    $where = ['level'=>1,'status'=>1,'id'=>['neq',8]];
                }
                $data = Db::name('goods_cate')->where($where)->field('id AS value,title AS text')->select();
            }
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
     * 根据商品类目查询绑定的品牌
     */
    public function getBrandByCate(){
        if(request()->isPost()){
            $post = $this->request->post();
            $cate_id = isset($post['cate_id'])?$post['cate_id']:0;
            $brands = Db::name('goods_cate')->where('id',$cate_id)->value('brands');
            $data = Db::name('brands')->where(['id'=>['in',$brands],'status'=>0])->field('id AS value,name AS text')->select();
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
     * 获取商品列表
     */
    public function getGoodsLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['county']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            if($post['county']>0){
                $where['A.county'] = $post['county'];
            }else{
                $where['A.is_zong'] = 1;
            }
            if(isset($post['keyword']) && $post['keyword'] != ''){//关键词
                $where['A.keywords'] = ['like','%'.$post['keyword'].'%'];
            }
            if(isset($post['brand']) && $post['brand']){//品牌
                $where['A.brand_id'] = $post['brand'];
            }
            if(isset($post['cate1']) && $post['cate1'] && !$post['cate2'] && !$post['cate3'] && !$post['cate4']){
                $cate = $post['cate1'];
                $where[] = ['exp','FIND_IN_SET('.$cate.',A.cate_id)'];
            }
            if(isset($post['cate2']) && $post['cate2'] && !$post['cate3'] && !$post['cate4']){
                $cate = $post['cate2'];
                $where[] = ['exp','FIND_IN_SET('.$cate.',A.cate_id)'];
            }
            if(isset($post['cate3']) && $post['cate3'] && !$post['cate4']){
                $cate = $post['cate3'];
                $where[] = ['exp','FIND_IN_SET('.$cate.',A.cate_id)'];
            }
            if(isset($post['cate4']) && $post['cate4']){
                $cate = $post['cate4'];
                $where[] = ['exp','FIND_IN_SET('.$cate.',A.cate_id)'];
            }
            $where['A.status']=['eq',0];
            $where['A.online']=['eq',1];
            $data = Db::name('shop_goods')->alias('A')
                ->join('brands B','B.id = A.brand_id','LEFT')
                ->join('goods_cate C','C.id = A.cate','LEFT')
                ->where($where)->field("A.id,A.name,A.title,A.thumb,B.name AS brand_name,C.title AS cate_name")->order('A.sort ASC')->limit($page_start, $limit)->select();
            if($data){
                foreach($data as $key=>$v){
                    $data[$key]['price'] = $data[$key]['shop_price'] = 0;
                    $where_attr = [
                        'goods_id'=>$v['id'],
                        'pid'=>0,
                        'status'=>0,
                    ];
                    $attr = Db::name('shop_goods_attr')->where($where_attr)->order('sort ASC')->field('price,shop_price,unit')->find();
                    if($attr){
                        $data[$key]['price'] = $attr['price'];
                        $data[$key]['shop_price'] = $attr['shop_price'];
                        $data[$key]['unit'] = $attr['unit'];
                    }
                    $data[$key]['thumb'] = _getServerName().'/public'.$v['thumb'];
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取商品详情
     */
    public function getGoodsInfoById(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where['A.id']=['eq',$post['id']];
            $where['A.status']=['eq',0];
            $where['A.online']=['eq',1];
            $data = Db::name('shop_goods')->alias('A')
                ->join('brands B','B.id = A.brand_id','LEFT')
                ->join('goods_cate C','C.id = A.cate','LEFT')
                ->where($where)->field("A.id,A.name,A.title,A.thumb,A.unit,A.video,A.content,A.shop_id,A.cate,A.points,B.name AS brand_name,C.title AS cate_name")
                ->find();
            if($data){
                if($data['video']){
                    $data['video'] = _getServerName().$data['video'];
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取商品轮播图
     */
    public function getGoodsBanner(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where['id']=['eq',$post['id']];
            $where['status']=['eq',0];
            $where['online']=['eq',1];
            $goods = Db::name('shop_goods')->where($where)->field("id,thumb")->find();
            if($goods){
                //轮播图
                $goods_img = Db::name('shop_goods_img')->where('goods_id',$goods['id'])->order('sort ASC')->field('img')->select();
                if($goods_img){
                    foreach($goods_img as $key=>$v){
                        $v['img'] = _getServerName().$v['img'];
                        $data[$key] = $v['img'];
                    }
                }else{
                    $data[0] = _getServerName().'/public'.$goods['thumb'];
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取商品评价列表
     */
    public function getGoodsReviewLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;

            $where['A.shop_goods_id']=['eq',$post['id']];
            $where['A.status']=['neq',2];
            $goods = Db::name('comment_order')->alias('A')
                ->join('member B','B.id = A.uid','INNER') 
                ->join('member_weixin C','C.id = B.uid','INNER')
                ->join('shop_goods_attr D','D.id = A.goods_attr_id','INNER') 
                ->join('shop_goods_attr E','E.id = D.pid','INNER')  
                ->where($where)->field("A.id,A.content,A.quality_point,A.transport_point,A.create_time,C.nickname,C.avatar,D.name,E.name AS cate_name")
                ->order('A.id DESC')->limit($page_start, $limit)->select();
            if($goods){
                foreach($goods as $key=>$v){
                    $img = Db::name('comment_order_img')->where('comment_order_id',$v['id'])->field('comment_img')->select();
                    if($img){
                        foreach($img as $k=>$i){
                            $goods[$key]['img'][$k] = _getServerName().$i['comment_img'];
                        }
                    }
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $goods;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取商品规格信息
     */
    public function getGoodsAttrLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where['A.goods_id']=['eq',$post['id']];
            $where['A.pid']=['eq',0];
            $where['A.status']=['eq',0];
            $where['A.online']=['eq',1];
            $data['attr'] = Db::name('shop_goods_attr')->alias('A')  
                ->where($where)->field("A.id,A.name,shop_price AS price,thumb AS img")
                ->order('A.sort ASC')->select();
            $data['lis'] = array();
            $data['ku'] = 0;
            if($data['attr']){
                foreach($data['attr'] as $key=>$v){
                    if($v['img']){
                        $data['attr'][$key]['img'] = _getServerName().'/public'.$v['img'];
                    }
                    $data['attr'][$key]['selected']=false;
                    $where2 = [
                        'pid'=>$v['id'],
                        'status' => 0,
                        'online' => 1,
                    ];
                    $ku = Db::name('shop_goods_attr')->where($where2)->sum('ku');
                    $data['ku'] += (int)$ku;
                }
                $where3 = [
                    'pid'=>$data['attr'][0]['id'],
                    'status' => 0,
                    'online' => 1,
                ];
                $attr = Db::name('shop_goods_attr')->where($where3)->field('id,name,shop_price AS price,thumb AS img,thumb AS previewImgUrl,ku,unit')->order('sort ASC')->select();
                if($attr){
                    foreach($attr as &$i){
                        if($i['img']){
                            $i['img'] = _getServerName().'/public'.$i['img'];
                        }
                        $data['lis'][] = [
                            'id'=>$i['id'],
                            'price'=>$i['price'],
                            'ku'=>$i['ku'],
                            'img'=>$i['img'],
                            'name'=>$i['name'],
                            'unit'=>$i['unit'],
                            'selected'=>false
                        ];
                    }
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 根据规格id获取属性信息
     */
    public function getSkuLisByPid(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where['goods_id']=['eq',$post['goods_id']];
            $where['pid']=['eq',$post['id']];
            $where['status']=['eq',0];
            $where['online']=['eq',1];
            $data = Db::name('shop_goods_attr')  
                ->where($where)
                ->field("id,name,shop_price AS price,thumb AS img,thumb AS previewImgUrl,ku,unit")
                ->order('sort ASC')->select();
            if($data){
                foreach($data as &$v){
                    if($v['img']){
                        $v['previewImgUrl'] = $v['img'] = _getServerName().'/public'.$v['img'];
                    }
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 手机端获取推荐商品数据
     */
    public function getRecommendGoodsLis(){
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
            $where['A.recommend_id'] = 1;
            $where['B.status'] = 0;
            $where['B.online'] = 1;
            $end_time = date('Y-m-d H:i:s');
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $data = Db::name('recommend_data')->alias('A')
                ->join('shop_goods B','B.id = A.object_id','INNER')
                ->join('brands C','C.id = B.brand_id','LEFT')
                ->join('goods_cate D','D.id = B.cate','LEFT')
                ->where($where)
                ->where("A.end_time >= '".$end_time."' OR A.end_time = ''")
                ->field('A.object_id AS id,A.img,B.name,B.title,C.name AS brand_name,D.title AS cate_name')
                ->order('A.sort ASC')
                ->limit($page_start, $limit)
                ->select();
            if($data){
                foreach($data as $key=>$v){
                    $data[$key]['price'] = $data[$key]['shop_price'] = 0;
                    $where_attr = [
                        'goods_id'=>$v['id'],
                        'pid'=>0,
                        'status'=>0,
                    ];
                    $attr = Db::name('shop_goods_attr')->where($where_attr)->order('sort ASC')->field('price,shop_price,unit')->find();
                    if($attr){
                        $data[$key]['price'] = $attr['price'];
                        $data[$key]['shop_price'] = $attr['shop_price'];
                        $data[$key]['unit'] = $attr['unit'];
                    }
                    $data[$key]['img'] = _getServerName().$v['img'];
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
     * 获取积分商品列表
     */
    public function getPointGoodsLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $where['A.status']=['eq',0];
            $where['A.online']=['eq',1];
            $where['A.cate']=['eq',8];
            $data = Db::name('shop_goods')->alias('A')
                ->join('brands B','B.id = A.brand_id','LEFT')
                ->join('goods_cate C','C.id = A.cate','LEFT')
                ->where($where)->field("A.id,A.name,A.title,A.thumb,A.points,B.name AS brand_name,C.title AS cate_name")->order('A.sort ASC')->limit($page_start, $limit)->select();
            if($data){
                foreach($data as $key=>$v){
                    $data[$key]['price'] = $data[$key]['shop_price'] = 0;
                    $attr = Db::name('shop_goods_attr')->where(['goods_id'=>$v['id'],'pid'=>0])->order('sort ASC')->field('price,shop_price,unit')->find();
                    if($attr){
                        $data[$key]['price'] = $attr['price'];
                        $data[$key]['shop_price'] = $attr['shop_price'];
                        $data[$key]['unit'] = $attr['unit'];
                    }
                    $data[$key]['thumb'] = _getServerName().'/public'.$v['thumb'];
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取智能预算商品列表
     */
    public function getTuiGoodsLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['cate_id']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            if(isset($post['brand']) && $post['brand']){//品牌
                $where['A.brand_id'] = $post['brand'];
            }
            if(isset($post['cate_id'])){
                $cate = $post['cate_id'];
                $where[] = ['exp','FIND_IN_SET('.$cate.',A.cate_id)'];
            }
            if(isset($post['style'])){
                $style = $post['style'];
                $where[] = ['exp','FIND_IN_SET('.$style.',A.style)'];
            }
            $order = 'D.sort DESC';
            if(isset($post['sort']) && $post['sort']){
                if($post['sort'] == '1'){//价格最低
                    $order = 'D.shop_price ASC';
                }elseif($post['sort'] == '2'){//价格最高
                    $order = 'D.shop_price DESC';
                }
            }
            if(isset($post['county']) && $post['county']){
                $where['A.county'] = $post['county'];
            }else{
                $where['A.zong'] = ['eq',1];
            }
            $where['A.status']=['eq',0];
            $where['A.online']=['eq',1];
            $where['D.pid']=['eq',0];
            $where['D.shop_price']=['>',0];
            $where['D.status']=['eq',0];
            $data = Db::name('shop_goods')->alias('A')
                ->join('brands B','B.id = A.brand_id','LEFT')
                ->join('goods_cate C','C.id = A.cate','LEFT')
                ->join('shop_goods_attr D','D.goods_id = A.id','LEFT')
                ->where($where)->field("A.id,A.name,A.title,A.thumb,B.name AS brand_name,C.title AS cate_name,D.price,D.shop_price,D.unit")->order($order)->limit($page_start, $limit)->select();
            if($data){
                foreach($data as $key=>$v){
                    $data[$key]['thumb'] = _getServerName().'/public'.$v['thumb'];
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
