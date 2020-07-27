<?php
namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class Member extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 会员管理-会员列表页
     */
    public function index(){
        return $this->fetch();
    }
    /**
     * 会员管理-会员列表数据
     */
    public function index_ajax(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $title = $this->request->param('title','');
        $keywords = $this->request->param('keywords','');
        $cate_id = $this->request->param('cate_id','');
        $page_start = ($page - 1) * $limit;
        $where = array();
        if($title){
            $where['a.title'] = ['like',"%$title%"];
        }
        if($keywords){
            $where['a.keywords'] = ['like',"%$keywords%"];
        }
        if($cate_id){
            $where['a.cate_id'] = $cate_id;
        }
        $data = Db::name('member')->alias('a')
            ->join('region b','a.county = b.region_code','LEFT')
            ->join('region c','a.city = c.region_code','LEFT')
            ->join('region d','a.province = d.region_code','LEFT')
            ->where($where)
            ->field('a.*,b.region_name as county_name,c.region_name as city_name,d.region_name as province_name')
            ->order('a.id DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('member')
            ->alias('a')
            ->join('region b','a.county = b.region_code','LEFT')
            ->join('region c','a.city = c.region_code','LEFT')
            ->join('region d','a.province = d.region_code','LEFT')
            ->where($where)
            ->count();
        if($data){
            $this->ret['count'] = $count;
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 会员管理-添加会员页面
     */
    public function articleAdd(){
        $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $this->assign('cate',$cate);
        return $this->fetch('article_add');
    }
    /**
     * 会员管理-添加会员数据
     */
    public function addArticle(){
        $post     = $this->request->post();
        $admin_user = session('user');
        if($admin_user['county'] != null){
            $post['county'] = $admin_user['county'];
        }
        $post['create_time']   = date('Y-m-d H:i:s');
        $db = Db::name('article')->insert($post);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 会员管理-编辑会员页面
     */
    public function articleEdit(){
        $id  = $this->request->get('id');
        $article = Db::name('article')->where('id',$id)->find();
        $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $this->assign('cate',$cate);
        $this->assign('article',$article);
        return $this->fetch('article_edit');
    }
    /**
     * 会员管理-编辑会员数据
     */
    public function editArticle(){
        $post     = $this->request->post();
        $id = $post['id'];
        $post['update_time']  = date('Y-m-d H:i:s');
        unset($post['id']);
        $admin_user = session('user');
        if($admin_user['county'] != null){
            $post['county'] = $admin_user['county'];
        }
        $db = Db::name('article')->where('id',$id)->update($post);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 会员管理-会员搜索页面
     */
    public function search(){
        $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $this->assign('cate',$cate);
        return $this->fetch();
    }
    /**
     * 会员管理-会员删除
     */
    public function deleteArticle(){
        $id = $this->request->post('id');
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        $res = Db::name('article')->where('id',$id)->update($upd);
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
