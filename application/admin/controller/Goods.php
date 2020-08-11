<?php
namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class Goods extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 商品管理-商品列表页
     */
    public function index(){
        return $this->fetch();
    }
    /**
     * 商品管理-商品列表数据
     */
    public function index_ajax(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $name = $this->request->param('name','');
        $goods_cate = $this->request->param('goods_cate','');
        $page_start = ($page - 1) * $limit;
        $where = ['status'=>0];
        if($name){
            $where['name'] = ['like',"%$name%"];
        }
        if($goods_cate){
            $where['goods_cate'] = ['in',$goods_cate];
        }
        $data = Db::name('goods_info')
            ->where($where)
            ->order('sort DESC,id DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('goods_info')
            ->where($where)
            ->count();
        if($data){
            $datas = array();
            foreach($data as $key=>$v){
                $cate = Db::name('goods_cate')->where('id','in',$v['goods_cate'])->field('title')->select();
                $goods_cate = '';
                if($cate){
                    foreach($cate as $k=>$va){
                        $goods_cate .= '【'.$va['title'].'】';
                    }
                }
                $v['goods_cate'] = $goods_cate;
                $datas[] = $v;
            }
            $this->ret['count'] = $count;
            $this->ret['data'] = $datas;
        }
        return json($this->ret);
    }
    /**
     * 商品管理-添加商品页面
     */
    public function goods_infoAdd(){
        return $this->fetch('goods_info_add');
    }
    /**
     * 商品管理-添加商品数据
     */
    public function addgoods_info(){
        $post     = $this->request->post();
        $insert = [
            'name'=>$post['name'],
            'sort'=>$post['sort'],
            'logo'=>$post['logo'],
            'goods_cate'=>implode(',',$post['cate']),
            'content'=>$post['content'],
            'create_time'=>date('Y-m-d H:i:s')
        ];
        $db = Db::name('goods_info')->insert($insert);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = '添加成功';
        }
        return json($this->ret);
    }
    /**
     * 商品管理-编辑商品页面
     */
    public function goods_infoEdit(){
        $id  = $this->request->get('id');
        $goods_info = Db::name('goods_info')->where('id',$id)->find();
        $goods_cate = explode(',',$goods_info['goods_cate']);
        $cate = [];
        //获取当前分类的父分类
        foreach($goods_cate as $key=>$v){
            $cate2 = _getGoodsCate($v,true);
            $cate1 = _getGoodsCate($cate2,true);
            $cate[$key]['level_3'] = $v;//当前分类id
            $cate[$key]['level_2'] = $cate2;//二级分类id
            $cate[$key]['level_1'] = $cate1;//顶级分类id
            $cate[$key]['select_1'] = _getGoodsCate();//顶级分类
            $cate[$key]['select_2'] = _getGoodsCate($cate1);//二级分类
            $cate[$key]['select_3'] = _getGoodsCate($cate2);//三级分类
        }
        $goods_info['goods_cate'] = $cate;
        $this->assign('goods_info',$goods_info);
        return $this->fetch('goods_info_edit');
    }
    /**
     * 商品管理-编辑商品数据
     */
    public function editgoods_info(){
        $post     = $this->request->post();
        $id = $post['id'];
        $update = [
            'name'=>$post['name'],
            'sort'=>$post['sort'],
            'logo'=>$post['logo'],
            'goods_cate'=>implode(',',$post['cate']),
            'content'=>$post['content'],
            'update_time'=>date('Y-m-d H:i:s')
        ];
        $db = Db::name('goods_info')->where('id',$id)->update($update);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = '修改成功';
        }
        return json($this->ret);
    }
    /**
     * 商品管理-商品搜索页面
     */
    public function search(){
        return $this->fetch();
    }
    /**
     * 商品管理-商品删除
     */
    public function deletegoods_info(){
        $id = $this->request->post('id');
        $ids = explode(',',$id);
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if(count($ids) > 1){
            $res = Db::name('goods_info')->where('id','in',$id)->update($upd);
        }else{
            $res = Db::name('goods_info')->where('id',$ids[0])->update($upd);
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
