<?php

namespace app\admin\controller;

use \think\Db;

class Appointment extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 预约管理-列表页面
     */
    public function index($type)
    {
        if (request()->isAjax()) {
            $type  = $this->request->get('type');
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $keywords = $this->request->param('keywords', '');
            $page_start = ($page - 1) * $limit;
            $where = '';
            if ($keywords) {
                $where = " AND (A.name = '" . $keywords . "' OR B.uname = '" . $keywords . "')";
            }
            $user = session('user');
            if($user['county']){
                $where .= ' AND A.province = '.$user['province'].' AND A.city = '.$user['city'].' AND A.county = '.$user['county'];
            }
            $sql = "SELECT CASE WHEN A.status = 0 THEN '未处理' WHEN A.status = 1 THEN '预约成功' ELSE '预约失败' END AS status_name,A.status ,A.note,A.name,A.id,B.uname AS appointmented_name,A.mobile,A.appoint_time,A.content
                FROM lg_appointment A 
                INNER JOIN lg_member B ON A.appointmented_uid = B.id 
                WHERE B.type = " . $type . $where . "
                ORDER BY A.id DESC
                limit $page_start,$limit";
            //var_dump($sql);die;
            $data = Db::query($sql);
            // var_dump($data);die;
            $sql1 = "SELECT COUNT(1) AS count FROM lg_appointment A 
                    INNER JOIN lg_member B ON A.appointmented_uid = B.id 
                    WHERE B.type = " . $type . $where;
            $count = Db::query($sql1);
            // var_dump($count);die;
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $this->assign('type', $type);
            return $this->fetch('index');
        }
    }
    /**
     * 预约管理-预约详情页面
     */
    public function detail()
    {
        $id  = $this->request->get('id');
        $this->assign('id', $id);
        return $this->fetch('reply');
    }
    /**
     * 预约管理-回复处理
     */
    public function replyDetail()
    {
        $post =  $this->request->post();
        $id = $post['id'];
        // if($post['status'] == 2 && $post['note'] == null){
        //     $this->ret['code'] = 0;
        //     $this->ret['msg'] = '拒绝时备注必填';
        // }
        $res = Db::name('appointment')
            ->where('id', $id)
            ->update(['note' => $post['note'], 'status' => $post['status']]);
        // var_dump(Db::name('review_user')->getLastSql());
        // die;
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 预约管理-搜索页面
     */
    public function search()
    {
        return $this->fetch('');
    }
}
