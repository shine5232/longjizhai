<?php

namespace app\admin\controller;

use \think\Db;
use app\admin\model\CommentUser as CommentUserModel;

class Review extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 评论管理-列表页面
     */
    public function index($type)
    {
        if (request()->isAjax()) {
            $type  = $this->request->get('type');
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $province = $this->request->param('province', '');
            $city = $this->request->param('city', '');
            $county = $this->request->param('county', '');
            $page_start = ($page - 1) * $limit;
            $user = session('user');
            if ($province) {
                $where['A.province'] = ['eq', $province];
            }
            if ($city) {
                $where['A.city'] = ['eq', $city];
            }
            if ($county) {
                $where['A.county'] = ['eq', $county];
            }
            if($user['county']){
                $where['A.county'] = ['eq', $user['county']];
            }
            $where['A.type'] = $type;
            if($type == 1){
                $model = 'mechanic';
            }else if($type == 2){
                $model = 'gongzhang';
            }else if($type == 3){
                $model = 'designer';
            }else if($type == 4){
                $model = 'company';
            }else if($type == 5){
                $model = 'shop';
            }
            $data = Db::name('comment_user')->alias('A')
                ->join("$model B", 'B.uid = A.uid', 'INNER')
                ->join('member C','C.id = A.comment_uid','INNER')
                ->where($where)
                ->field('A.*,B.name,C.realname')
                ->order('A.id DESC')
                ->limit($page_start, $limit)
                ->select();
            $count = Db::name('comment_user')->alias('A')
                ->join("$model B", 'B.uid = A.uid', 'INNER')
                ->where($where)
                ->count();
            if ($data) {
                foreach($data as $key=>$v){
                    $imgs = [];
                    $img = Db::name('comment_user_img')->where(['comment_user_id'=>$v['id']])->field('comment_img')->select();
                    if($img){
                        $imgs = array_column($img,'comment_img');
                    }
                    $data[$key]['img'] = $imgs;
                }
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $this->assign('type', $type);
            return $this->fetch('index');
        }
    }

    public function reply()
    {
        if(request()->isPost()){
            $post =  $this->request->post();
            $id = $post['id'];
            $post['replay_time'] = date('Y-m-d H:i:s');
            $CommentUserModel = new CommentUserModel;
            // save方法第二个参数为更新条件
            $res = $CommentUserModel->save($post, ['id', $id]);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        }else{
            $id  = $this->request->get('id');
            // var_dump($id);
            $data = Db::name('comment_user')
                ->field('a.*,b.uname')
                ->alias('a')
                ->join('member b', 'a.uid = b.id','INNER')
                ->where('a.id', $id)
                ->find();
            $img = Db::name('comment_user_img')->where('comment_user_id',$id)->field('comment_img')->select();
            $this->assign('data', $data);
            $this->assign('img', $img);
            if (!$data['replay']) {
                return $this->fetch('reply');
            }
            return $this->fetch('detail');
        }
    }
    /**
     * 评论管理-审核页面
     */
    public function checked(){
        if(request()->isPost()){
            $post =  $this->request->post();
            $id = $post['id'];
            $post['checked_time'] = date('Y-m-d H:i:s');
            unset($post['id']);
            $res = Db::name('comment_user')->where(['id'=>$id])->update($post);
            if ($res) {
                $user = Db::name('comment_user')->where(['id'=>$id])->find();
                if($post['checked'] == 1){//审核通过，计算口碑
                    if($user['type'] == 1){
                        $model = 'mechanic';
                    }else if($user['type'] == 2){
                        $model = 'gongzhang';
                    }else if($user['type'] == 3){
                        $model = 'designer';
                    }else if($user['type'] == 4){
                        $model = 'company';
                    }else if($user['type'] == 5){
                        $model = 'shop';
                    }
                    _computeUserScore($user['uid'],$user['design_point'],$user['service_point'],$user['care_point'],$model,true);
                }
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        }else{
            $id  = $this->request->get('id');
            $this->assign('id', $id);
            return $this->fetch('checked');
        }
    }
}
