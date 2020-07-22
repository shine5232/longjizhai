<?php
namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;
use org\net\Uploadsa;

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
    	$auth = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $auth = array2Level($auth);
    	return  $this->fetch('cate_add',['auth'=>$auth]);
    }
    /**
     * 文章管理-文章栏目添加处理
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
     * 文章管理-文章栏目编辑页面
     */
    public function cateEdit(){
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
    /**
     * 文章管理-文章栏目编辑处理
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
        return $this->fetch();
    }
    /**
     * 文章管理-文章列表数据
     */
    public function index_ajax(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $title = $this->request->param('title','');
        $status = $this->request->param('status',0);
        $page_start = ($page - 1) * $limit;
        $where = array('deleted'=>0);
        if($title){
            $where['title'] = ['like',"%$title%"];
        }
        if($status){
            $where['status'] = $status;
        }
        $data = Db::name('branch')->alias('a')
            ->join('region d','a.province = d.region_code','LEFT')
            ->join('region e','a.city = e.region_code','LEFT')
            ->join('region f','a.county = f.region_code','LEFT')
            ->where($where)
            ->field('a.*,d.region_name as province_name,e.region_name as city_name,f.region_name as county_name')
            ->order('a.id asc')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('branch')
            ->alias('a')
            ->join('region d','a.province = d.region_code','LEFT')
            ->join('region e','a.city = e.region_code','LEFT')
            ->join('region f','a.county = f.region_code','LEFT')
            ->where($where)
            ->count();
        if($data){
            $this->ret['count'] = $count;
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 文章管理-添加文章页面
     */
    public function articleAdd(){
        /* $s = new Uploadsa();
        var_dump($s->index());die; */
        $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $this->assign('cate',$cate);
        return $this->fetch('article_add');
    }
    /**
     * 文章管理-添加文章数据
     */
    public function addArticle(){
        $post     = $this->request->post();
        $post['create_time']   = date('Y-m-d h:i:s');
        $db = Db::name('article')->insert($post);
        if($db){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
