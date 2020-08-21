<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class Member extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 会员管理-会员列表页
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where = array();
            // $data = Db::name('member')->alias('a')
            //     ->join('region b', 'a.county = b.region_code', 'INNER')
            //     ->join('region c', 'a.city = c.region_code', 'INNER')
            //     ->join('region d', 'a.province = d.region_code', 'INNER')
            //     ->where($where)
            //     ->field('a.*,b.region_name as county_name,c.region_name as city_name,d.region_name as province_name')
            //     ->order('a.id DESC')
            //     ->limit($page_start, $limit)
            //     ->select();
            $sql = "SELECT A.*,B.region_name as county_name,C.region_name as city_name,D.region_name as province_name FROM (
                SELECT id,uname,province,city,county,create_time,birthday,mobile,cancel_time,lastlogin,loginip,point,realname,subor,subscribe FROM lg_member
                ORDER BY id DESC
                LIMIT $page_start, $limit
            )A
            LEFT JOIN lg_region B ON A.province = B.region_code
            INNER JOIN lg_region C ON A.province = C.region_code
            INNER JOIN lg_region D ON A.province = D.region_code ";
            $data = Db::query($sql);
            $count = Db::name('member')
                ->alias('a')
                ->where($where)
                ->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            return $this->fetch();
        }
    }
    /**
     * 会员管理-添加会员页面
     */
    public function articleAdd()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $admin_user = session('user');
            if ($admin_user['county'] != null) {
                $post['county'] = $admin_user['county'];
            }
            $post['create_time']   = date('Y-m-d H:i:s');
            $db = Db::name('article')->insert($post);
            if ($db) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        } else {
            $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
            $cate = array2Level($cate);
            $this->assign('cate', $cate);
            return $this->fetch('article_add');
        }
    }
    /**
     * 会员管理-编辑会员页面
     */
    public function articleEdit()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
            $post['update_time']  = date('Y-m-d H:i:s');
            unset($post['id']);
            $admin_user = session('user');
            if ($admin_user['county'] != null) {
                $post['county'] = $admin_user['county'];
            }
            $db = Db::name('article')->where('id', $id)->update($post);
            if ($db) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        } else {
            $id  = $this->request->get('id');
            $article = Db::name('article')->where('id', $id)->find();
            $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
            $cate = array2Level($cate);
            $this->assign('cate', $cate);
            $this->assign('article', $article);
            return $this->fetch('article_edit');
        }
    }
    /**
     * 会员管理-会员搜索页面
     */
    public function search()
    {
        $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $this->assign('cate', $cate);
        return $this->fetch();
    }
    /**
     * 会员管理-会员删除
     */
    public function deleteArticle()
    {
        $id = $this->request->post('id');
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        $res = Db::name('article')->where('id', $id)->update($upd);
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
