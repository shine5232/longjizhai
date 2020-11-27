<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class PointStore extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 积分商城-商品列表页面
     */
    public function goodsLis()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $brand_id = $this->request->param('brand_id', '');
            $name = $this->request->param('name', '');
            $page_start = ($page - 1) * $limit;
            $where['a.status'] = ['eq', 0];
            $where['a.cate'] = ['eq', 8];
            if ($brand_id) {
                $where['a.brand_id'] = ['eq', $brand_id];
            }
            if ($name) {
                $where['a.name'] = ['like', '%'.$name.'%'];
            }
            $data = Db::name('shop_goods')->alias('a')
                ->join('brands b', 'b.id = a.brand_id', 'LEFT')
                ->where($where)
                ->field('a.id,a.name,a.thumb,a.online,a.cate,a.title,a.points,b.name as brand_name')
                ->order('a.id DESC')
                ->limit($page_start, $limit)
                ->select();
            $count = Db::name('shop_goods')->alias('a')
                ->join('brands b', 'b.id = a.brand_id', 'LEFT')
                ->where($where)
                ->count();
            $datas = [];
            if ($data) {
                foreach ($data as $key => $vo) {
                    $datas[$key] = $vo;
                    $datas[$key]['cate_title'] = _getAllCateTitle($vo['cate']);//改
                }
                $this->ret['count'] = $count;
                $this->ret['data'] = $datas;
            }
            return json($this->ret);
        } else {
            $shop_id  = $this->request->get('id');
            $cate_id = Db::name('shop')->where('id', $shop_id)->value('shop_cate');
            $this->assign('shop_id', $shop_id);
            $this->assign('cate_id', $cate_id);
            return $this->fetch('goods_lis');
        }
    }
    /**
     * 积分商城-商品搜索页面
     */
    public function searchGoods()
    {
        return $this->fetch('search_goods');
    }
    /**
     * 积分商城-商品添加页面
     */
    public function goodsAdd()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            unset($post['imgFile']);
            $insert = $post;
            $insert['cate'] = $insert['cate_id'] = $post['cate'];
            $insert['county'] = 0;
            $insert['online'] = isset($post['online']) ? $post['online'] : 0;
            $insert['brand_id'] = $post['brand_id'] > 0 ? $post['brand_id'] : '';
            $insert['create_time'] = date('Y-m-d H:i:s');
            $db = Db::name('shop_goods')->insert($insert);
            if ($db) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        } else {
            //根据分类获取品牌数据
            $brands_id = Db::name('goods_cate')->where('status', 1)->where('id',8)->value('brands');
            $brands = [];
            if ($brands_id) {
                $where = [
                    'id' => ['in', $brands_id],
                    'status' => 0
                ];
                $brands = Db::name('brands')->where($where)->field('id,name')->select();
            }
            $this->assign('brands', $brands);
            return $this->fetch('goods_add');
        }
    }
    /**
     * 积分商城-商品编辑页面
     */
    public function goodsEdit()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
            unset($post['id'],$post['imgFile']);
            $update = $post;
            $insert['cate'] = $insert['cate_id'] = $post['cate'];
            $update['hot']=isset($post['hot'])?1:0;
            $update['online'] = isset($post['online']) ? $post['online'] : 0;
            $update['brand_id'] = $post['brand_id'] > 0 ? $post['brand_id'] : '';
            $update['update_time'] = date('Y-m-d H:i:s');
            Db::name('shop_goods')->where('id', $id)->update($update);
            $this->ret['code'] = 200;
            $this->ret['msg'] = '修改成功';
            return json($this->ret);
        } else {
            $shop_goods_id  = $this->request->get('id');
            $goods_info = Db::name('shop_goods')->where('id', $shop_goods_id)->find();
            //根据分类获取品牌数据
            $brands_id = Db::name('goods_cate')->where('status', 1)->where('id', $goods_info['cate'])->value('brands');
            $brands = [];
            if ($brands_id) {
                $where = [
                    'id' => ['in', $brands_id],
                    'status' => 0
                ];
                $brands = Db::name('brands')->where($where)->field('id,name')->select();
            }
            $this->assign('brands', $brands);
            $this->assign('goods_info', $goods_info);
            return $this->fetch('goods_edit');
        }
    }
    /**
     * 积分商城-商品删除
     */
    public function deleteGoods()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('shop_goods')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('shop_goods')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
