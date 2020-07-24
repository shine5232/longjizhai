<?php
namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class Member extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 会员管理-会员分类列表页面
     */
    public function type(){
        return $this->fetch();
    }
    /**
     * 会员管理-会员分类列表数据
     */
    public function type_ajax(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $page_start = ($page - 1) * $limit;
        $where = array('status'=>0);
        $data = Db::name('member_type')
            ->where($where)
            ->order('id DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('member_type')
            ->where($where)
            ->count();
        if($data){
            $this->ret['count'] = $count;
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 会员管理-会员分类添加页面
     */
    public function typeAdd(){
        return $this->fetch('type_add');
    }
    /**
     * 会员管理-会员分类添加数据
     */
    public function addType(){
        $post = $this->request->post();
    	$validate = validate('MemberType');
    	$res = $validate->check($post);
		if($res!==true){
			$this->ret['msg'] = $validate->getError();
		}else{
            $post['create_time'] = date('Y-m-d H:i:s');
			$db = Db::name('member_type')->insert($post);
			if($db){
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
        }
        return json($this->ret);
    }
    /**
     * 会员管理-会员分类删除数据
     */
    public function deleteType(){
        $id = $this->request->post('id');
        $data = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        $res = Db::name('member_type')->where('id',$id)->update($data);
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 会员管理-会员等级列表页面
     */
    public function rank(){
        return $this->fetch();
    }
    /**
     * 会员管理-会员等级列表数据
     */
    public function rank_ajax(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $page_start = ($page - 1) * $limit;
        $where = array();
        $data = Db::name('member_rank')
            ->where($where)
            ->order('id DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('member_rank')
            ->where($where)
            ->count();
        if($data){
            $this->ret['count'] = $count;
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 会员管理-会员等级添加页面
     */
    public function rankAdd(){
        $type = Db::name('member_type')->where('status',0)->select();
        $this->assign('type',$type);
    	return $this->fetch('rank_add');
    }
    /**
     * 会员管理-会员等级添加处理
     */
    public function addCate(){
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
    }
    /**
     * 会员管理-会员等级编辑页面
     */
    public function cateEdit(){
        $id  = $this->request->get('id');
        $pid = Db::name('article_cate')->where('id',$id)->value('pid');
        if($pid!==0){
            $p_title = Db::name('article_cate')->where('id',$pid)->value('title');
        }else{
            $p_title = '顶级等级';
        }
        $this->assign('p_title',$p_title);
        $data  =   Db::name('article_cate')->where('id',$id)->find();
        return  $this->fetch('cate_edit',['data'=>$data]);
    }
    /**
     * 会员管理-会员等级编辑处理
     */
    public function editCate(){
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
    }
    /**
     * 会员管理-会员等级删除处理
     */
    public function deleteCate(){
        $id = $this->request->post('id');
        $juge = Db::name('article_cate')->where('pid',$id)->find();
        if(!empty($juge)){
            $this->error('请先删除子等级');
        }else{
            Db::name('article_cate')->delete($id);
            $this->success('删除成功');
        }
    }
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
        $data = Db::name('article')->alias('a')
            ->join('region d','a.county = d.region_code','LEFT')
            ->join('article_cate g','a.cate_id = g.id','INNER')
            ->where($where)
            ->field('a.*,d.region_name as county_name,g.title as cate_title')
            ->order('a.id DESC')
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
