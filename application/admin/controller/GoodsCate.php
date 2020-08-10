<?php
namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class GoodsCate extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 商品分类管理-列表页面
     */
    public function index(){
        $cate = Db::name('goods_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $count = count($cate);
        return $this->fetch('cate_index',['cate'=>$cate,'count'=>$count]);
    }
    /**
     * 商品分类管理-添加页面
     */
    public function cateAdd(){
    	$auth = Db::name('goods_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $auth = array2Level($auth);
    	return  $this->fetch('cate_add',['auth'=>$auth]);
    }
    /**
     * 商品分类管理-添加处理
     */
    public function addCate(){
    	$post = $this->request->post();
    	$cate = Db::name('goods_cate')->where('id',$post['pid'])->field('level')->find();
        if($cate){
            $post['level'] = (int)$cate['level'] + 1;
        }
        Db::name('goods_cate')->insert($post);
        $this->success('添加成功');
    }
    /**
     * 商品分类管理-添加子分类
     */
    public function addChildCate(){
        $id  = $this->request->get('id');
        if($id!==0){
            $p_title = Db::name('goods_cate')->where('id',$id)->value('title');
        }else{
            $p_title = '顶级栏目';
        }
        $this->assign('p_title',$p_title);
        $this->assign('pid',$id);
        return  $this->fetch('cate_child');
    }
    /**
     * 商品分类管理-添加子分类数据处理
     */
    public function addCateChild(){
        $post =  $this->request->post();
        $cate = Db::name('goods_cate')->where('id',$post['pid'])->field('level')->find();
        if($cate){
            $post['level'] = (int)$cate['level'] + 1;
        }
        Db::name('goods_cate')->insert($post);
        $this->success('添加成功');
    }
    /**
     * 商品分类管理-编辑页面
     */
    public function cateEdit(){
        $id  = $this->request->get('id');
        $pid = Db::name('goods_cate')->where('id',$id)->value('pid');
        if($pid!==0){
            $p_title = Db::name('goods_cate')->where('id',$pid)->value('title');
        }else{
            $p_title = '顶级栏目';
        }
        $this->assign('p_title',$p_title);
        $data  =   Db::name('goods_cate')->where('id',$id)->find();
        return  $this->fetch('cate_edit',['data'=>$data]);
    }
    /**
     * 商品分类管理-编辑处理
     */
    public function editCate(){
        $post =  $this->request->post();
        $id = $post['id'];unset($post['id']);
        Db::name('goods_cate')->where('id',$id)->update($post);
        $this->success('修改成功');
    }
    /**
     * 商品分类管理-删除处理
     */
    public function deleteCate(){
        $id = $this->request->post('id');
        $juge = Db::name('goods_cate')->where('pid',$id)->find();
        if(!empty($juge)){
            $this->error('请先删除子栏目');
        }else{
            Db::name('goods_cate')->delete($id);
            $this->success('删除成功');
        }
    }
}
