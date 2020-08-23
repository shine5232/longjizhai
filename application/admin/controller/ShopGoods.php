<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class ShopGoods extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 商家管理-商品列表页面
     */
    public function goodsLis()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $shop_id = $this->request->param('shop_id', '');
            $cate_id = $this->request->param('cate_id', '');
            $brand_id = $this->request->param('brand_id', '');
            $page_start = ($page - 1) * $limit;
            $where['a.shop_id'] = ['eq', $shop_id];
            $where['a.status'] = ['eq', 0];
            if ($cate_id) {
                $where['a.cate_id'] = ['eq', $cate_id];
            }
            if ($brand_id) {
                $where['a.brand_id'] = ['eq', $brand_id];
            }
            $data = Db::name('shop_goods')->alias('a')
                ->join('brands b', 'b.id = a.brand_id', 'LEFT')
                ->where($where)
                ->field('a.id,a.name,a.thumb,a.online,a.cate_id,a.title,b.name as brand_name')
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
                    $datas[$key]['cate_title'] = _getAllCateTitle($vo['cate_id']);
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
     * 商家管理-商品搜索页面
     */
    public function searchGoods()
    {
        $cate_id  = $this->request->get('cate_id');
        $cate = _getGoodsCate(); //获取顶级分类
        $this->assign('cate', $cate);
        $this->assign('cate_id', $cate_id);
        return $this->fetch('search_goods');
    }
    /**
     * 商家管理-商品添加页面
     */
    public function goodsAdd()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $county = Db::name('shop')->where('id', $post['shop_id'])->value('county');
            $insert = [
                'name' => $post['name'],
                'shop_id' => $post['shop_id'],
                'county' => $county,
                'keywords' => $post['keywords'],
                'thumb' => $post['thumb'],
                'title' => $post['title'],
                'unit' => $post['unit'],
                'brand_id' => $post['brand_id'] > 0 ? $post['brand_id'] : '',
                'cate_id' => $post['cate_id'],
                'content' => $post['content'],
                'create_time' => date('Y-m-d H:i:s')
            ];
            $db = Db::name('shop_goods')->insert($insert);
            if ($db) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        } else {
            $cate = _getGoodsCate(); //获取顶级分类
            $shop_id  = $this->request->get('shop_id');
            $cate_id  = $this->request->get('cate_id');
            $this->assign('cate', $cate);
            $this->assign('cate_id', $cate_id);
            $this->assign('shop_id', $shop_id);
            return $this->fetch('goods_add');
        }
    }
    /**
     * 商家管理-商品编辑页面
     */
    public function goodsEdit()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
            $update = [
                'name' => $post['name'],
                'keywords' => $post['keywords'],
                'thumb' => $post['thumb'],
                'title' => $post['title'],
                'unit' => $post['unit'],
                'brand_id' => $post['brand_id'] > 0 ? $post['brand_id'] : '',
                'cate_id' => $post['cate_id'],
                'content' => $post['content'],
                'update_time' => date('Y-m-d H:i:s')
            ];
            Db::name('shop_goods')->where('id', $id)->update($update);
            $this->ret['code'] = 200;
            $this->ret['msg'] = '修改成功';
            return json($this->ret);
        } else {
            $shop_goods_id  = $this->request->get('id');
            $goods_info = Db::name('shop_goods')->where('id', $shop_goods_id)->find();
            //根据当前分类获取上级所有分类
            $pid = $goods_info['cate_id'];
            $cate_id = [$goods_info['cate_id']];
            $cate = [];
            while ($pid != 0) {
                $pid = _getGoodsCate($pid, true);
                if ($pid != 0) {
                    $cate_id[] = $pid;
                }
                $cate[] = _getGoodsCate($pid);
            }
            $cate_id = array_reverse($cate_id);
            $cate = array_reverse($cate);
            //根据分类获取品牌数据
            $brands_id = Db::name('goods_cate')->where('status', 1)->where('id', $goods_info['cate_id'])->value('brands');
            $brands = [];
            if ($brands_id) {
                $where = [
                    'id' => ['in', $brands_id],
                    'status' => 0
                ];
                $brands = Db::name('brands')->where($where)->field('id,name')->select();
            }
            $this->assign('cate_id', $cate_id);
            $this->assign('cate', $cate);
            $this->assign('brands', $brands);
            $this->assign('goods_info', $goods_info);
            return $this->fetch('goods_edit');
        }
    }
    /**
     * 商家管理-商品删除
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