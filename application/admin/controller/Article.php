<?php
namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class article extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 文章管理-文章栏目列表页面
     */
    public function cate(){
        $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $count = count($cate);
        return $this->fetch('cate_index',['cate'=>$cate,'count'=>$count]);
    }
    /**
     * 文章管理-文章栏目添加页面
     */
    public function cateAdd(){
        if(request()->isPost()){
            $post = $this->request->post();
            $validate = validate('cate');
            $res = $validate->check($post);
            if($res!==true){
                $this->error($validate->getError());
            }else{
                $cate = Db::name('article_cate')->where('id',$post['pid'])->field('level')->find();
                if($cate){
                    $post['level'] = (int)$cate['level'] + 1;
                }
                Db::name('article_cate')->insert($post);
                $this->success('添加成功');
            }
        }else{
            $auth = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
            $auth = array2Level($auth);
            return  $this->fetch('cate_add',['auth'=>$auth]);
        }
    }
    /**
     * 文章管理-文章栏目编辑页面
     */
    public function cateEdit(){
        if(request()->isPost()){
            $post =  $this->request->post();
            $id = $post['id'];
            $validate = validate('auth');
            $validate->scene('edit');
            $res = $validate->check($post);
            if($res!==true){
                $this->error($validate->getError());
            }else{
                unset($post['id']);
                Db::name('article_cate')->where('id',$id)->update($post);
                $this->success('修改成功');
            }
        }else{
            $id  = $this->request->get('id');
            $pid = Db::name('article_cate')->where('id',$id)->value('pid');
            if($pid!==0){
                $p_title = Db::name('article_cate')->where('id',$pid)->value('title');
            }else{
                $p_title = '顶级栏目';
            }
            $this->assign('p_title',$p_title);
            $data  =   Db::name('article_cate')->where('id',$id)->find();
            return  $this->fetch('cate_edit',['data'=>$data]);
        }
    }
    /**
     * 文章管理-文章栏目删除处理
     */
    public function deleteCate(){
        $id = $this->request->post('id');
        $juge = Db::name('article_cate')->where('pid',$id)->find();
        if(!empty($juge)){
            $this->error('请先删除子栏目');
        }else{
            Db::name('article_cate')->delete($id);
            $this->success('删除成功');
        }
    }
    /**
     * 文章管理-文章列表页
     */
    public function index(){
        if(request()->isAjax()){
            $page = $this->request->param('page',1,'intval');
            $limit = $this->request->param('limit',20,'intval');
            $title = $this->request->param('title','');
            $keywords = $this->request->param('keywords','');
            $cate_id = $this->request->param('cate_id','');
            $page_start = ($page - 1) * $limit;
            $where = ['a.status'=>0];
            if($title){
                $where['a.title'] = ['like',"%$title%"];
            }
            if($keywords){
                $where['a.keywords'] = ['like',"%$keywords%"];
            }
            if($cate_id){
                $where['a.cate_id'] = $cate_id;
            }
            $data = Db::name('article')->alias('a')
                ->join('region d','a.county = d.region_code','LEFT')
                ->join('article_cate g','a.cate_id = g.id','INNER')
                ->where($where)
                ->field('a.*,d.region_name as county_name,g.title as cate_title')
                ->order('a.sort DESC,a.id DESC')
                ->limit($page_start,$limit)
                ->select();
            $count = Db::name('article')
                ->alias('a')
                ->join('region d','a.county = d.region_code','LEFT')
                ->where($where)
                ->count();
            if($data){
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch();
        }
        
    }
    /**
     * 文章管理-添加文章页面
     */
    public function articleAdd(){
        if(request()->isPost()){
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
        }else{
            $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
            $cate = array2Level($cate);
            $this->assign('cate',$cate);
            return $this->fetch('article_add');
        }
    }
    /**
     * 文章管理-编辑文章页面
     */
    public function articleEdit(){
        if(request()->isPost()){
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
        }else{
            $id  = $this->request->get('id');
            $article = Db::name('article')->where('id',$id)->find();
            $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
            $cate = array2Level($cate);
            $this->assign('cate',$cate);
            $this->assign('article',$article);
            return $this->fetch('article_edit');
        }
    }
    /**
     * 文章管理-文章搜索页面
     */
    public function search(){
        $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $this->assign('cate',$cate);
        return $this->fetch();
    }
    /**
     * 文章管理-文章删除
     */
    public function deleteArticle(){
        $id = $this->request->post('id');
        $ids = explode(',',$id);
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if(count($ids) > 1){
            $res = Db::name('article')->where('id','in',$id)->update($upd);
        }else{
            $res = Db::name('article')->where('id',$ids[0])->update($upd);
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
