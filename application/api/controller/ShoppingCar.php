<?php
namespace app\api\controller;

use think\Db;

class ShoppingCar extends Main
{
    /**
     * 添加到购物车
     */
    public function addShoppingCar(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['goods_id']) || !isset($post['uid']) || !isset($post['num']) || !isset($post['goods_attr_id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $shop_id = Db::name('shop_goods')->where('id',$post['goods_id'])->value('shop_id');
            $insert1 = [
                'uid'=>$post['uid'],
                'shop_id'=>$shop_id,
            ];
            $has = Db::name('goods_car')->where($insert1)->value('id');
            if($has){
                $car_id = $has;
            }else{
                $insert1['create_time'] = date('Y-m-d H:i:s');
                $car_id = Db::name('goods_car')->insertGetId($insert1);
            }
            $insert2 = [
                'car_id' => $car_id,
                'shop_goods_id' => $post['goods_id'],
                'goods_attr_id' => $post['goods_attr_id'],
            ];
            $has2 = Db::name('goods_car_info')->where($insert2)->find();
            if($has2){
                $data = Db::name('goods_car_info')->where($insert2)->setInc('num',$post['num']);
            }else{
                $insert2['num'] = $post['num'];
                $insert2['create_time'] = date('Y-m-d H:i:s');
                $data = Db::name('goods_car_info')->insert($insert2);
            }
            if($data){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }else{
                $this->ret['msg'] = '添加失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取购物车数据列表
     */
    public function getShoppingCar(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $where = [
                'A.uid' => $post['uid'],
            ];
            $data = Db::name('goods_car')->alias('A')
                ->join('shop B','B.id = A.shop_id','LEFT')
                ->where($where)
                ->field('A.*,B.name AS shop_name')
                ->order('A.id DESC')
                ->limit($page_start, $limit)
                ->select();
            $car_num = 0;
            if($data){
                foreach($data as $key=>$v){
                    $info = Db::name('goods_car_info')->alias('A')
                        ->join('shop_goods B','B.id = A.shop_goods_id','LEFT')
                        ->join('shop_goods_attr C','C.id = A.goods_attr_id','LEFT')
                        ->join('brands D','D.id = B.brand_id','LEFT')
                        ->join('shop_goods_attr E','E.id = C.pid','LEFT')
                        ->field('A.id,A.num,A.car_id,A.goods_attr_id,B.id AS goods_id,B.name AS goods_name,B.unit,C.name AS attr_name,C.price,C.shop_price,C.thumb,C.pid AS pid_attr_id,D.name AS brand_name,E.name AS cate_name')
                        ->where('A.car_id',$v['id'])->select();
                    if($info){
                        foreach($info as $k=>$i){
                            $car_num += $i['num'];
                            $info[$k]['thumb'] =  _getServerName().'/public'.$i['thumb'];
                            $info[$k]['checked'] = false;
                        }
                    }
                    $data[$key]['lis'] = $info;
                    $data[$key]['checked'] = false;
                }
                $datas['data'] = $data;
                $this->ret['code'] = 200;
                $this->ret['data'] = $datas;
            }else{
                $this->ret['msg'] = '暂无数据';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取购物车商品总数
     */
    public function getShoppingCarNum(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where = [
                'A.uid' => $post['uid'],
            ];
            $data = Db::name('goods_car')->alias('A')
                ->join('goods_car_info B','B.car_id = A.id')
                ->where($where)
                ->sum('B.num');
            $car_num = 0;
            if($data){
                $car_num += $data;
                $this->ret['code'] = 200;
                $this->ret['data'] = $car_num;
            }else{
                $this->ret['msg'] = '暂无数据';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 更改购物车商品的数量
     */
    public function updateShoppingCarNum(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id']) || !isset($post['num'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $res = Db::name('goods_car_info')->where('id',$post['id'])->update(['num'=>$post['num']]);
            if($res){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '更新成功';
            }else{
                $this->ret['msg'] = '更新失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 删除购物车对应商品
     */
    public function deleteShoppingCar(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['id']) || !isset($post['car_id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $res = Db::name('goods_car_info')->where('id',$post['id'])->delete();
            if($res){
                $count = Db::name('goods_car_info')->where('car_id',$post['car_id'])->count();
                if(!$count){
                    $where = [
                        'uid' => $post['uid'],
                        'id' => $post['car_id'],
                    ];
                    Db::name('goods_car')->where($where)->delete();
                }
                $this->ret['code'] = 200;
                $this->ret['msg'] = '删除成功';
            }else{
                $this->ret['msg'] = '删除失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 清空购物车
     */
    public function emptyShoppingCar(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $res = Db::name('goods_car')
                ->where(['uid'=>$post['uid']])->field('id')->select();
            if($res){
                foreach($res as $key=>$v){
                    Db::name('goods_car')->where(['id'=>$v['id']])->delete();
                    Db::name('goods_car_info')->where(['car_id'=>$v['id']])->delete();
                }
            }
            if($res){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '删除成功';
            }else{
                $this->ret['msg'] = '删除失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
