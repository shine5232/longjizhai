<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class ShopGoodsAttr extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 商家管理-商品属性列表页面
     */
    public function goodsAttr()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $shop_goods_id = $this->request->param('shop_goods_id', '');
            $page_start = ($page - 1) * $limit;
            $where['a.status'] = ['eq', 0];
            $where['a.pid'] = ['eq', 0];
            $where['a.goods_id'] = ['eq', $shop_goods_id];
            $data = Db::name('shop_goods_attr')->alias('a')
                ->join('shop_goods b', 'b.id = a.goods_id', 'INNER')
                ->where($where)
                ->field('a.*,b.unit')
                ->order('a.id DESC')
                ->limit($page_start, $limit)
                ->select();
            $count = Db::name('shop_goods_attr')->alias('a')
                ->join('shop_goods b', 'b.id = a.goods_id', 'INNER')
                ->where($where)
                ->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $shop_goods_id  = $this->request->get('id');
            $this->assign('shop_goods_id', $shop_goods_id);
            return $this->fetch('goods_attr');
        }
    }
    /**
     * 商家管理-属性添加页面
     */
    public function attrAdd()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $insert = [
                'name' => $post['name'],
                'price' => $post['price'],
                'shop_price' => $post['shop_price'],
                'thumb' => $post['thumb'],
                'paytype' => isset($post['paytype']) ? $post['paytype'] : 0,
                'pay_one' => $post['pay_one'],
                'pay_two' => $post['pay_two'],
                'pay_three' => $post['pay_three'],
                'online' => isset($post['online']) ? $post['online'] : 0,
                'goods_id' => $post['goods_id'],
                'create_time' => date('Y-m-d H:i:s')
            ];
            $db = Db::name('shop_goods_attr')->insert($insert);
            if ($db) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        } else {
            $goods_id = $this->request->get('shop_goods_id');
            $this->assign('goods_id', $goods_id);
            return $this->fetch('attr_add');
        }
    }
    /**
     * 商家管理-属性编辑页面
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
     * 商家管理-属性删除
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
     * 商家管理-商品规格列表页面
     */
    public function unintList()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $shop_goods_id = $this->request->param('shop_goods_id', '');
            $page_start = ($page - 1) * $limit;
            $where['a.status'] = ['eq', 0];
            $where['a.goods_id'] = ['eq', $shop_goods_id];
            $where['a.pid'] = ['neq', 0];
            $data = Db::name('shop_goods_attr')->alias('a')
                ->join('shop_goods b', 'b.id = a.goods_id', 'INNER')
                ->where($where)
                ->field('a.*,b.unit')
                ->order('a.id DESC')
                ->limit($page_start, $limit)
                ->select();
            $count = Db::name('shop_goods_attr')->alias('a')
                ->join('shop_goods b', 'b.id = a.goods_id', 'INNER')
                ->where($where)
                ->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $shop_goods_id  = $this->request->get('id');
            $this->assign('shop_goods_id', $shop_goods_id);
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
            $insert = [
                'name' => $post['name'],
                'price' => $post['price'],
                'shop_price' => $post['shop_price'],
                'pid'=>$post['pid'],
                'thumb' => $post['thumb'],
                'paytype' => isset($post['paytype']) ? $post['paytype'] : 0,
                'pay_one' => $post['pay_one'],
                'pay_two' => $post['pay_two'],
                'pay_three' => $post['pay_three'],
                'online' => isset($post['online']) ? $post['online'] : 0,
                'goods_id' => $post['goods_id'],
                'create_time' => date('Y-m-d H:i:s')
            ];
            $db = Db::name('shop_goods_attr')->insert($insert);
            if ($db) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        } else {
            $goods_id = $this->request->get('shop_goods_id');
            $pid = Db::name('shop_goods_attr')->where(['goods_id'=>$goods_id,'pid'=>0])->field('id,name')->select();
            $this->assign('goods_id', $goods_id);
            $this->assign('pid', $pid);
            return $this->fetch('unint_add');
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
                'pid'=>$post['pid'],
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
            $pid = Db::name('shop_goods_attr')->where(['goods_id'=>$goods_attr['goods_id'],'pid'=>0])->field('id,name')->select();
            $this->assign('goods_attr', $goods_attr);
            $this->assign('pid', $pid);
            $this->assign('goods_id', $goods_attr['goods_id']);
            return $this->fetch('unint_edit');
        }
    }
}
