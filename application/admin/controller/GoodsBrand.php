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
        $page_start = ($page - 1) * $limit;
        $where = ['status'=>0];
        if($name){
            $where['name'] = ['like',"%$name%"];
        }
        $data = Db::name('brands')
            ->where($where)
            ->order('sort DESC,id DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('brands')
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
        return $this->fetch('brands_add');
    }
    /**
     * 品牌管理-添加品牌数据
     */
    public function addBrands(){
        $post     = $this->request->post();
        $insert = [
            'name'=>$post['name'],
            'sort'=>$post['sort'],
            'logo'=>$post['logo'],
            'content'=>$post['content'],
            'create_time'=>date('Y-m-d H:i:s')
        ];
        $db = Db::name('brands')->insert($insert);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = '添加成功';
        }
        return json($this->ret);
    }
    /**
     * 品牌管理-编辑品牌页面
     */
    public function brandsEdit(){
        $id  = $this->request->get('id');
        $brands = Db::name('brands')->where('id',$id)->find();
        $this->assign('brands',$brands);
        return $this->fetch('brands_edit');
    }
    /**
     * 品牌管理-编辑品牌数据
     */
    public function editBrands(){
        $post     = $this->request->post();
        $id = $post['id'];
        $update = [
            'name'=>$post['name'],
            'sort'=>$post['sort'],
            'logo'=>$post['logo'],
            'content'=>$post['content'],
            'update_time'=>date('Y-m-d H:i:s')
        ];
        $db = Db::name('brands')->where('id',$id)->update($update);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = '修改成功';
        }
        return json($this->ret);
    }
    /**
     * 品牌管理-品牌搜索页面
     */
    public function search(){
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