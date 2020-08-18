<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class ShopGoodsImg extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 商家管理-相册列表页面
     */
    public function goodsImg()
    {
        if (request()->isAjax()) {
            $shop_goods_id = $this->request->get('shop_goods_id');
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where['goods_id'] = $shop_goods_id;
            $data = Db::name('shop_goods_img')
                ->where($where)
                ->order('sort DESC,id DESC')
                ->limit($page_start, $limit)
                ->select();
            $count = Db::name('shop_goods_img')
                ->where($where)
                ->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $id  = $this->request->get('id');
            $count = Db::name('shop_goods_img')->where('goods_id', $id)->count();
            $this->assign('id', $id);
            $this->assign('count', $count);
            return $this->fetch('shop_goods_img');
        }
    }
    /**
     * 商家管理-相册添加页面
     */
    public function imgAdd()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            foreach ($post['img'] as $key => $v) {
                $insert[] = [
                    'goods_id' => $post['goods_id'],
                    'create_time' => date('Y-m-d H:i:s'),
                    'img' => $v
                ];
            }
            $db = Db::name('shop_goods_img')->insertAll($insert);
            if ($db) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        } else {
            $goods_id = $this->request->get('shop_goods_id');
            $count = Db::name('shop_goods_img')->where('goods_id', $goods_id)->count();
            $this->assign('count', $count);
            $this->assign('goods_id', $goods_id);
            return $this->fetch('img_add');
        }
    }
    /**
     * 商家管理-相册编辑页面
     */
    public function imgEdit()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
            $update = [
                'title' => $post['title'],
                'sort' => $post['sort'],
                'img' => $post['img'],
                'update_time' => date('Y-m-d H:i:s')
            ];
            Db::name('shop_goods_img')->where('id', $id)->update($update);
            $this->ret['code'] = 200;
            $this->ret['msg'] = '修改成功';
            return json($this->ret);
        }
    }
    /**
     * 商家管理-相册删除
     */
    public function deleteImg()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        if (count($ids) > 1) {
            $res = Db::name('shop_goods_img')->where('id', 'in', $id)->delete();
        } else {
            $res = Db::name('shop_goods_img')->where('id', $ids[0])->delete();
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
