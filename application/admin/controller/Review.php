<?php

namespace app\admin\controller;

use \think\Db;
use app\admin\model\ReviewUser as ReviewModel;

class Review extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 认证管理-列表页面
     */
    public function index($type, $is_reply)
    {
        if (request()->isAjax()) {
            $type  = $this->request->get('type');
            $is_reply  = $this->request->get('is_reply');
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where = '';
            if ($is_reply == 1) {
                $where = ' AND reply IS NOT NULL';
            } else {
                $where = ' AND reply IS NULL';
            }
            $sql = "SELECT A.content,A.create_time,B.uname,A.id
                FROM lg_review_user A 
                INNER JOIN lg_member B on A.uid = B.id
                WHERE A.type = " . $type . $where . "
                ORDER BY A.id DESC
                limit $page_start,$limit";
            // var_dump($sql);die;
            $data = Db::query($sql);
            $sql1 = "SELECT COUNT(1) AS count FROM lg_review_user A 
        WHERE A.type = " . $type . $where;
            $count = Db::query($sql1);
            // var_dump($count);die;
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $this->assign('type', $type);
            $this->assign('is_reply', $is_reply);
            return $this->fetch('index');
        }
    }

    public function reply()
    {
        if(request()->isPost()){
            $post =  $this->request->post();
            $id = $post['id'];
            $ReviewModel = new ReviewModel;
            // save方法第二个参数为更新条件
            $res = $ReviewModel->save($post, ['id', $id]);
            // var_dump(Db::name('review_user')->getLastSql());
            // die;
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        }else{
            $id  = $this->request->get('id');
            // var_dump($id);
            $data = Db::name('review_user')
                ->field('a.*,b.uname')
                ->alias('a')
                ->join('member b', 'a.uid = b.id')
                ->where('a.id', $id)
                ->find();
            // var_dump(db::name('review_user')->getLastSql());die;
            $img = explode(',', $data['review_img']);
            $this->assign('data', $data);
            $this->assign('img', $img);
            if (!$data['reply']) {
                return $this->fetch('reply');
            }
            return $this->fetch('detail');
        }
    }
}
