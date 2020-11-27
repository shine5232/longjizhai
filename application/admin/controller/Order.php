<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class Order extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 订单管理-订单列表
     */
    public function index($status){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $status = $this->request->param('status','');
            $order_sn = $this->request->param('order_sn','');
            $uname = $this->request->param('uname','');
            $page_start = ($page - 1) * $limit;
            $where['A.status'] = ['eq',$status];
            $and = ' AND 1';
            if($order_sn){
                $where['A.order_sn'] = ['eq',$order_sn];
            }
            if($uname){
                $and = " AND B.uname = '$uname'";
            }
            $data = Db::name('order')->alias('A')
                ->join('member B','B.id = A.uid '.$and,'INNER')
                ->join('shop C','C.id = A.shop_id','LEFT')
                ->where($where)
                ->field('A.*,B.uname,C.name AS shop_name')
                ->order('A.id DESC')->limit($page_start, $limit)->select();
            $count = Db::name('order')->alias('A')
                ->join('member B','B.uid = A.uid '.$and,'INNER')
                ->join('shop C','C.id = A.shop_id','LEFT')
                ->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            $this->assign('status',$status);
            return $this->fetch();
        }
    }
    /**
     * 订单管理-订单搜索
     */
    public function search(){
        $status = $this->request->get('status');
        $this->assign('status',$status);
        return $this->fetch('search');
    }
    /**
     * 订单管理-订单详情
     */
    public function detail(){
        $id = $this->request->get('id');
        $order = Db::name('order')->alias('A')
            ->join('member B','B.id = A.uid ','INNER')
            ->join('shop C','C.id = A.shop_id','LEFT')
            ->join('member_address D','D.id = A.address_id','INNER')
            ->field('A.*,B.uname,C.name AS shop_name,D.province,D.city,D.county,D.address,D.mobile,D.name AS get_name')
            ->where('A.id',$id)->find();
        if($order){
            $order['info'] = Db::name('order_info')->alias('A')
                ->join('shop_goods B','B.id = A.shop_goods_id','INNER')
                ->join('shop_goods_attr C','C.id = A.goods_attrs_id','INNER')
                ->join('shop_goods_attr D','D.id = C.pid','INNER')
                ->where('A.order_id',$id)
                ->field('A.goods_num,B.name,B.unit,C.name AS unint_name,C.price,C.shop_price,C.thumb,D.name AS attr_name')
                ->select();
        }
        $this->assign('order',$order);
        return $this->fetch('detail');
    }
}
