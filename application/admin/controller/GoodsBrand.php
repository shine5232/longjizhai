<?php
namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class GoodsBrand extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 品牌管理-品牌列表页
     */
    public function index(){
        return $this->fetch();
    }
    /**
     * 品牌管理-品牌列表数据
     */
    public function index_ajax(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $name = $this->request->param('name','');
        $goods_cate = $this->request->param('goods_cate','');
        $page_start = ($page - 1) * $limit;
        $where = ['a.status'=>0];
        if($name){
            $where['a.name'] = ['like',"%$name%"];
        }
        if($goods_cate){
            $where['a.goods_cate'] = $goods_cate;
        }
        $data = Db::name('brands')->alias('a')
            ->join('goods_cate g','a.goods_cate = g.id','INNER')
            ->where($where)
            ->field('a.*,g.title as cate_title')
            ->order('a.sort DESC,a.id DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('brands')
            ->alias('a')
            ->where($where)
            ->count();
        if($data){
            $this->ret['count'] = $count;
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 品牌管理-添加品牌页面
     */
    public function brandsAdd(){
        $cate = _getGoodsCate();
        $this->assign('cate',$cate);
        return $this->fetch('brands_add');
    }
    /**
     * 品牌管理-添加品牌数据
     */
    public function addBrands(){
        $post     = $this->request->post();
        echo '<pre>';var_dump($post);die;
        $post['create_time']   = date('Y-m-d H:i:s');
        $db = Db::name('brands')->insert($post);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 品牌管理-编辑品牌页面
     */
    public function brandsEdit(){
        $id  = $this->request->get('id');
        $brands = Db::name('brands')->where('id',$id)->find();
        $cate = Db::name('goods_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $this->assign('cate',$cate);
        $this->assign('brands',$brands);
        return $this->fetch('brands_edit');
    }
    /**
     * 品牌管理-编辑品牌数据
     */
    public function editBrands(){
        $post     = $this->request->post();
        $id = $post['id'];
        $post['update_time']  = date('Y-m-d H:i:s');
        unset($post['id']);
        $admin_user = session('user');
        if($admin_user['county'] != null){
            $post['county'] = $admin_user['county'];
        }
        $db = Db::name('brands')->where('id',$id)->update($post);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 品牌管理-品牌搜索页面
     */
    public function search(){
        $cate = Db::name('goods_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $this->assign('cate',$cate);
        return $this->fetch();
    }
    /**
     * 品牌管理-品牌删除
     */
    public function deleteBrands(){
        $id = $this->request->post('id');
        $ids = explode(',',$id);
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if(count($ids) > 1){
            $res = Db::name('brands')->where('id','in',$id)->update($upd);
        }else{
            $res = Db::name('brands')->where('id',$ids[0])->update($upd);
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
