<?php
namespace app\api\controller;

use think\Controller;

class GoodsCate extends Controller
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 获取商品分类信息
     */
    public function index(){
        $post = $this->request->post();
        if($post['type'] == '1'){
            $data = _getGoodsCate();
        }else{
            $data = _getGoodsCate($post['pid']);
        }
        if($data){
            $this->ret['code'] = 200;
            $this->ret['data'] = $data;
        }else{
            $this->ret['msg'] = '暂无分类';
        }
        return json($this->ret);
    }
    /**
     * 根据商品分类获取品牌数据
     */
    public function getBrands(){
        $post = $this->request->post();
        $data = _getBrandsByCate($post['cate_id']);
        if($data){
            $this->ret['code'] = 200;
            $this->ret['data'] = $data;
        }else{
            $this->ret['msg'] = '暂无品牌';
        }
        return json($this->ret);
    }
}
