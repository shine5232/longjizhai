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
}
