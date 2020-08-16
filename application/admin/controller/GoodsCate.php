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
        $cates = [];
        if($cate){
            foreach($cate as $key=>$vo){
                $cates[$key] = $vo;
                $cates[$key]['brands'] = '';
                if($vo['brands']){
                    $brands = explode(',',$vo['brands']);
                    foreach($brands as $k=>$v){
                        $brands_name = Db::name('brands')->where('id',$v)->value('name');
                        $cates[$key]['brands'] .= '【'.$brands_name.'】';
                    }
                }
            }
        }
        $cates = array2Level($cates);
        $count = count($cates);
        return $this->fetch('cate_index',['cate'=>$cates,'count'=>$count]);
    }
    /**
     * 商品分类管理-添加页面
     */
    public function cateAdd(){
        if(request()->isPost()){
            $post = $this->request->post();
            $cate = Db::name('goods_cate')->where('id',$post['pid'])->field('level')->find();
            if($cate){
                $post['level'] = (int)$cate['level'] + 1;
            }
            $res = Db::name('goods_cate')->insert($post);
            if($res){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }else{
                $this->ret['msg'] = '添加失败';
            }
            return json($this->ret);
        }else{
            $auth = Db::name('goods_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
            $auth = array2Level($auth);
            return  $this->fetch('cate_add',['auth'=>$auth]);
        }
    }
    /**
     * 商品分类管理-添加子分类
     */
    public function addChildCate(){
        if(request()->isPost()){
            $post =  $this->request->post();
            $cate = Db::name('goods_cate')->where('id',$post['pid'])->field('level')->find();
            if($cate){
                $post['level'] = (int)$cate['level'] + 1;
            }
            $res = Db::name('goods_cate')->insert($post);
            if($res){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }else{
                $this->ret['msg'] = '添加失败';
            }
            return json($this->ret);
        }else{
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
    }
    /**
     * 商品分类管理-编辑页面
     */
    public function cateEdit(){
        if(request()->isPost()){
            $post =  $this->request->post();
            $id = $post['id'];unset($post['id']);
            Db::name('goods_cate')->where('id',$id)->update($post);
            $this->ret['code'] = 200;
            $this->ret['msg'] = '修改成功';
            return json($this->ret);
        }else{
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
    }
    /**
     * 商品分类管理-删除处理
     */
    public function deleteCate(){
        $id = $this->request->post('id');
        $juge = Db::name('goods_cate')->where('pid',$id)->find();
        if(!empty($juge)){
            $this->ret['msg'] = '请先删除子栏目';
        }else{
            Db::name('goods_cate')->delete($id);
            $this->ret['code'] = 200;
            $this->ret['msg'] = '删除成功';
        }
        return json($this->ret);
    }
    /**
     * 绑定品牌
     */
    public function addBrands(){
        if(request()->isPost()){
            $post = $this->request->post();
            $brands = implode(',',$post['brands']);
            $res = Db::name('goods_cate')->where('id',$post['cate_id'])->update(['brands'=>$brands]);
            if($res){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '绑定成功';
            }else{
                $this->ret['msg'] = '绑定失败';
            }
            return json($this->ret);
        }else{
            $id  = $this->request->get('id');
            $cate = Db::name('goods_cate')->where('id',$id)->where('status',1)->field('id,title,pid,brands')->find();
            $pid = $cate['pid'];
            $title = '【'.$cate['title'].'】';
            $title = _getAllCateTitle($pid,$title);
            $brands = Db::name('brands')->where('status',0)->field('id,name')->select();
            $this->assign('cate',$cate);
            $this->assign('title',$title);
            $this->assign('brands',$brands);
            $this->assign('brand',explode(',',$cate['brands']));
            return $this->fetch('add_brands');
        }
    }
}
