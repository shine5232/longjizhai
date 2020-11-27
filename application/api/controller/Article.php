<?php
namespace app\api\controller;

use think\Db;

class Article extends Main
{
    /**
     * 用户收藏文章
     */
    public function collectArticle(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['article_id']) || !isset($post['uid'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $insert = [
                'uid' => $post['uid'],
                'collected_id' => $post['article_id'],
                'type'  =>  2,
            ];
            $collect = Db::name('collect')->where($insert)->find();
            if($collect){
                $upd['status'] = $collect['status']?0:1;
                $upd['update_time'] = date('Y-m-d H:i:s');
                $data = Db::name('collect')->where($insert)->update($upd);
                if($upd['status']){//减少1
                    Db::name('article')->where('id',$post['article_id'])->setDec('collect_num',1);
                }else{//增加1
                    Db::name('article')->where('id',$post['article_id'])->setInc('collect_num',1);
                }
            }else{
                $insert['create_time'] = date('Y-m-d H:i:s');
                $data = Db::name('collect')->insert($insert);
                Db::name('article')->where('id',$post['article_id'])->setInc('collect_num',1);
            }
            if($data){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '操作成功';
            }else{
                $this->ret['msg'] = '操作失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取装修课堂文章列表
     */
    public function getArticleLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['cate_id']) || !isset($post['cate_pid']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            if($post['cate_pid'] == 0){
                if($post['cate_id'] == 0){
                    $cate = 15;
                }elseif($post['cate_id'] == 1){
                    $cate = 16;
                }
            }
            if($post['cate_pid'] == 1){
                if($post['cate_id'] == 0){
                    $cate = 35;
                }elseif($post['cate_id'] == 1){
                    $cate = 34;
                }elseif($post['cate_id'] == 2){
                    $cate = 33;
                }
            }
            if($post['cate_pid'] == 2){
                if($post['cate_id'] == 0){
                    $cate = 77;
                }elseif($post['cate_id'] == 1){
                    $cate = 4;
                }elseif($post['cate_id'] == 2){
                    $cate = 5;
                }
            }
            if($post['cate_pid'] == 3){
                if($post['cate_id'] == 0){
                    $cate = 7;
                }elseif($post['cate_id'] == 1){
                    $cate = 8;
                }elseif($post['cate_id'] == 2){
                    $cate = 9;
                }
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $where = [
                'A.cate_id' => $cate,
                'A.status' => 0,
                'A.checked'  =>  1,
            ];
            $data = Db::name('article')->alias('A')
                ->where($where)->field('A.id,A.title,A.thumb,A.desc,A.create_time')
                ->order('A.sort DESC')->limit($page_start,$limit)->select();
            if($data){
                foreach($data as &$v){
                    $v['thumb'] = _getServerName().'/public'.$v['thumb'];
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '操作失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取文章详情
     */
    public function getArticleInfoById(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where = [
                'id' => $post['id'],
                'checked'  =>  1,
            ];
            $data = Db::name('article')->where($where)->field('id,cate_id,author,title,thumb,desc,content,create_time')->find();
            if($data){
                if($data['cate_id'] == 76){
                    $data['authors'] = Db::name('designer')->where('id',$data['author'])->value('name');
                }
                if($data['author'] == ''){
                    $data['authors'] = '龙吉宅';
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '暂无数据';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
