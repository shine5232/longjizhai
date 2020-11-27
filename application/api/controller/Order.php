<?php
namespace app\api\controller;

use think\Db;

class Order extends Main
{
    /**
     * 创建临时订单数据
     */
    public function creatOrderPro(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['data'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $data = $post['data'];
            $ok = false;
            if($data){
                $num = 0;
                $pay_all = 0;
                foreach($data as $key=>$v){
                    $pay = 0;
                    $yuns = 0;
                    foreach($v['lis'] as $k=>$i){
                        $yun = 0;
                        $info = Db::name('shop_goods')->alias('A')
                            ->join('shop B','B.id = A.shop_id','LEFT')
                            ->join('shop_goods_attr C','C.goods_id = A.id','LEFT')
                            ->join('shop_goods_attr D','D.id = C.pid','LEFT')
                            ->where('A.id',$i['goods_id'])
                            ->where('C.id',$i['goods_attr_id'])
                            ->where('D.id',$i['pid_attr_id'])
                            ->field('A.name AS goods_name,A.cate,A.points,B.name AS shop_name,C.name AS attr_name,C.shop_price,C.thumb,D.name AS cate_name')
                            ->find();
                        $data[$key]['shop_name'] = $info['shop_name'];
                        $data[$key]['lis'][$k]['goods_name'] = $info['goods_name'];
                        $data[$key]['lis'][$k]['attr_name'] = $info['attr_name'].';'.$info['cate_name'];
                        $data[$key]['lis'][$k]['cate_name'] = $info['cate_name'];
                        $data[$key]['lis'][$k]['shop_price'] = $info['shop_price'];
                        $data[$key]['lis'][$k]['cate_name'] = $info['cate_name'];
                        $data[$key]['lis'][$k]['points'] = $info['points'];
                        $data[$key]['lis'][$k]['cate'] = $info['cate'];
                        $data[$key]['lis'][$k]['yun'] = $yun;
                        if($info['cate'] == 8){
                            $pay_one = $info['points'] * $i['num'];
                        }else{
                            $pay_one = $info['shop_price'] * $i['num'] + $yun;
                        }
                        $data[$key]['lis'][$k]['pay'] = $pay_one;
                        $data[$key]['lis'][$k]['thumb'] = _getServerName().'/public'.$info['thumb'];
                        $num += (int)$i['num'];
                        $pay_all += $data[$key]['lis'][$k]['pay'];
                        $pay += $data[$key]['lis'][$k]['pay'];
                        $yuns += $data[$key]['lis'][$k]['yun'];
                    }
                    $data[$key]['pay'] = $pay;
                    $data[$key]['yuns'] = $yuns;
                }
                $datas['num'] = $num;
                $datas['cate'] = $info['cate'];
                $datas['pay_all'] = $pay_all;
                $datas['data'] = $data;
                $ok = cache('order_pro'.$post['uid'],$datas);
            }
            if($ok){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '创建成功';
            }else{
                $this->ret['msg'] = '创建失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取临时订单数据
     */
    public function getOrderPro(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $data = cache('order_pro'.$post['uid']);
            if($data){
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '创建失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 生成订单
     */
    public function createOrder(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['data']) || !isset($post['address_id']) || !isset($post['froms'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $data = $post['data'];
            $order_id = 0;
            foreach($data as $key=>$v){
                if($v['shop_id']){
                    $shop_id = $v['shop_id'];
                }else{
                    $shop_id = 0;
                }
                $order_sn = 'LJZ'.date('YmdHis').$post['uid'].$shop_id.mt_rand(1000,9999);
                $insert = [
                    'uid' => $post['uid'],
                    'order_sn'  =>  $order_sn,
                    'money' =>  $v['pay'],
                    'pay_money' => $v['pay'],
                    'cate' => $v['lis'][0]['cate'],
                    'shop_id' => $shop_id,
                    'freight_fee' => $v['yuns'],
                    'address_id' => $post['address_id'],
                    'create_time' => date('Y-m-d H:i:s'),
                ];
                if($v['lis'][0]['cate'] == 8){
                    $insert['status'] = 1;
                    $insert['pay_time'] = date('Y-m-d H:i:s');
                }
                $order_id = Db::name('order')->insertGetId($insert);
                if($order_id){
                    foreach($v['lis'] as $k=>$s){
                        $insert2 = [
                            'order_id' => $order_id,
                            'shop_goods_id' => $s['goods_id'],
                            'goods_num' => $s['num'],
                            'goods_attrs_id' => $s['goods_attr_id'],
                            'create_time' => date('Y-m-d H:i:s'),
                        ];
                        Db::name('order_info')->insert($insert2);
                        if($post['froms'] == 'car'){
                            //删除购物车操作
                            $res = Db::name('goods_car_info')->where('id',$s['id'])->delete();
                            if($res){
                                $count = Db::name('goods_car_info')->where('car_id',$s['car_id'])->count();
                                if(!$count){
                                    $where = [
                                        'uid' => $post['uid'],
                                        'id' => $s['car_id'],
                                    ];
                                    Db::name('goods_car')->where($where)->delete();
                                }
                            }
                        }
                    }
                }
            }
            cache('order_pro'.$post['uid'],null);
            if($order_id){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '创建成功';
            }else{
                $this->ret['msg'] = '创建失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取我的订单列表
     */
    public function getOrderLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['page']) || !isset($post['size']) || !isset($post['type'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $where = [
                'A.status' => $post['type'],
                'A.uid' => $post['uid'],
            ];
            if(isset($post['cate']) && $post['cate'] == 8){
                $where['cate'] = 8;
            }
            $data = Db::name('order')->alias('A')
                ->join('shop B','B.id = A.shop_id','LEFT')
                ->where($where)
                ->field('A.*,B.name AS shop_name')
                ->order('A.id DESC')
                ->limit($page_start, $limit)
                ->select();
            if($data){
                foreach($data as $key=>$v){
                    $info = Db::name('order_info')->alias('A')
                        ->join('shop_goods B','B.id = A.shop_goods_id','LEFT')
                        ->join('shop_goods_attr C','C.id = A.goods_attrs_id','LEFT')
                        ->join('shop_goods_attr D','D.id = C.pid','LEFT')
                        ->where('A.order_id',$v['id'])
                        ->field('A.goods_num,B.name AS goods_name,B.unit,C.name AS attr_name,C.price,C.shop_price,C.thumb,D.name AS cate_name,(C.shop_price * A.goods_num) AS pay')
                        ->select();
                    $data[$key]['goods'] = $info;
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '创建失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取订单详情
     */
    public function getOrderInfo(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where = [
                'A.status' => $post['type'],
                'A.uid' => $post['uid'],
            ];
            if(isset($post['cate']) && $post['cate'] == 8){
                $where['cate'] = 8;
            }
            $data = Db::name('order')->alias('A')
                ->join('shop B','B.id = A.shop_id','LEFT')
                ->where($where)
                ->field('A.*,B.name AS shop_name')
                ->find();
            if($data){
                $info = Db::name('order_info')->alias('A')
                        ->join('shop_goods B','B.id = A.shop_goods_id','LEFT')
                        ->join('shop_goods_attr C','C.id = A.goods_attrs_id','LEFT')
                        ->join('shop_goods_attr D','D.id = C.pid','LEFT')
                        ->where('A.order_id',$data['id'])
                        ->field('A.goods_num,B.name AS goods_name,B.unit,C.name AS attr_name,C.price,C.shop_price,C.thumb,D.name AS cate_name,(C.shop_price * A.goods_num) AS pay')
                        ->select();
                $data['goods'] = $info;
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '创建失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
