<?php
namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;
class article extends Main
{
    /**
     * 文章管理-文章栏目列表页面
     */
    function cate(){
        $cate = Db::name('article_cate')
	        ->order(['sort' => 'DESC', 'id' => 'ASC'])
	        ->select();
        $cate = array2Level($cate);
        $count = count($cate);
        return $this->fetch('cate',['cate'=>$cate,'count'=>$count]);
    }
    /**
     * 文章管理-文章栏目添加页面
     */
    function showAdd(){
    	$auth = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $auth = array2Level($auth);
    	return  $this->fetch('add',['auth'=>$auth]);
    }
    /**
     * 文章管理-文章栏目添加处理
     */
    function add(){
    	$post = $this->request->post();
    	$validate = validate('cate');
    	$res = $validate->check($post);

		if($res!==true){
			$this->error($validate->getError());
		}else{
			Db::name('article_cate')->insert($post);
			$this->success('添加成功');
		}
    }
    /**
     * 文章管理-文章栏目编辑页面
     */
    function showEdit(){
        $id  = $this->request->get('id');
        $pid = Db::name('article_cate')
            ->where('id',$id)
            ->value('pid');
        if($pid!==0){
            $p_title = Db::name('article_cate')
                ->where('id',$pid)
                ->value('title');
        }else{
            $p_title = '顶级菜单';
        }
        $this->assign('p_title',$p_title);
        $data  =   Db::name('article_cate')
            ->where('id',$id)
            ->find();
        return  $this->fetch('edit',['data'=>$data]);
    }
    /**
     * 文章管理-文章栏目编辑处理
     */
    function edit(){
        $post =  $this->request->post();
        $id = $post['id'];
        $validate = validate('auth');
        $validate->scene('edit');
        $res = $validate->check($post);
        if($res!==true){
            $this->error($validate->getError());
        }else{
            unset($post['id']);
            Db::name('article_cate')
            ->where('id',$id)
            ->update($post);
            $this->success('修改成功');
        }
    }
    /**
     * 文章管理-文章栏目删除处理
     */
    function delete(){
        $id = $this->request->post('id');
        $juge = Db::name('article_cate')->where('pid',$id)->find();
        if(!empty($juge)){
            $this->error('请先删除子栏目');
        }else{
            Db::name('article_cate')->delete($id);
            $this->success('删除成功');
        }
    }
}
