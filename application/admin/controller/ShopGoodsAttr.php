<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class ShopGoodsAttr extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 商家管理-商品规格列表页面
     */
    public function goodsAttr()
    {
        if (request()->isGet()) {
            $shop_goods_id  = $this->request->get('id');
            $where['a.status'] = ['eq', 0];
            $where['a.pid'] = ['eq', 0];
            $where['a.goods_id'] = ['eq', $shop_goods_id];
            $data = Db::name('shop_goods_attr')->alias('a')
                ->where($where)
                ->field('a.*')
                ->order('a.sort ASC,a.id DESC')
                ->select();
            $this->assign('shop_goods_id', $shop_goods_id);
            $this->assign('data', $data);
            return $this->fetch('goods_attr');
        }
    }
    /**
     * 商家管理-规格添加页面
     */
    public function attrAdd()
    {
        if (request()->isPost()) {
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
     * 商家管理-规格编辑页面
     */
    public function attrEdit()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
            $update = [
                'name' => $post['name'],
                'price' => $post['price'],
                'shop_price' => $post['shop_price'],
                'thumb' => $post['thumb'],
                'sort' => $post['sort'],
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
            return $this->fetch('attr_edit');
        }
    }
    /**
     * 商家管理-规格删除
     */
    public function deleteAttr()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('shop_goods_attr')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('shop_goods_attr')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
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
            $is_bizhi = 0;
            $pid  = $this->request->get('pid');
            $where['a.status'] = ['eq', 0];
            $where['a.pid'] = ['eq', $pid];
            $data = Db::name('shop_goods_attr')->alias('a')
                ->where($where)
                ->field('a.*')
                ->order('a.sort ASC,a.id DESC')
                ->select();
            $cate_id = Db::name('shop_goods_attr')->alias('A')
                ->join('shop_goods B','B.id = A.goods_id','INNER')
                ->where('A.id',$pid)->value('B.cate_id');
            if($cate_id){
                $array = explode(',',$cate_id);
                if(in_array('515',$array)){
                    $is_bizhi = 1;
                }
            }
            $this->assign('data', $data);
            $this->assign('pid', $pid);
            $this->assign('is_bizhi', $is_bizhi);
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
                $tui_price = $vo['shop_price'];
                if($vo['unit'] == '片' || $vo['unit'] == '卷'){
                    $tui_price = $vo['shop_price'] / ($vo['wide'] *  $vo['long']);
                    round($tui_price,2);
                }
                if($vo['id']){//更新数据
                    $upd = [
                        'name' => $vo['name'],
                        'price' => $vo['price'],
                        'shop_price' => $vo['shop_price'],
                        'thumb' => $vo['thumb'],
                        'sort' => $vo['sort'],
                        'unit' => $vo['unit'],
                        'wide' => $vo['wide'],
                        'long' => $vo['long'],
                        'jimo_price' => isset($vo['jimo_price'])?$vo['jimo_price']:0,
                        'work_price' => isset($vo['work_price'])?$vo['work_price']:0,
                        'tui_price'=>$tui_price,
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
                        'wide' => $vo['wide'],
                        'long' => $vo['long'],
                        'jimo_price' => isset($vo['jimo_price'])?$vo['jimo_price']:0,
                        'work_price' => isset($vo['work_price'])?$vo['work_price']:0,
                        'tui_price'=>$tui_price,
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
            $tui_price = $post['shop_price'];
            if($post['unit'] == '片' || $post['unit'] == '卷'){
                $tui_price = $post['shop_price'] / ($post['wide'] *  $post['long']);
                round($tui_price,2);
            }
            $update = [
                'name' => $post['name'],
                'price' => $post['price'],
                'shop_price' => $post['shop_price'],
                'thumb' => $post['thumb'],
                'sort' => $post['sort'],
                'yun' => $post['yun'],
                'wide' => $post['wide'],
                'long' => $post['long'],
                'jimo_price' => isset($vo['jimo_price'])?$vo['jimo_price']:0,
                'work_price' => isset($vo['work_price'])?$vo['work_price']:0,
                'tui_price'=>$tui_price,
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
}
