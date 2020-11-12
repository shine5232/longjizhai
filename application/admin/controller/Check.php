<?php

namespace app\admin\controller;

use app\admin\model\Cases as CaseModel;
use \think\Db;
use \think\Reuquest;

class check extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];

    /**
     * 案例管理-案例表页
     */
    public function authenticate()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $keywords = $this->request->param('keywords', '');
            $type = $this->request->param('type', '');
            $where = ' AND A.checked = 0';
            if ($keywords) {
                $where .= " AND B.uname LIKE '%" . $keywords . "%' ";
            }
            if ($type) {
                $where  .= " AND A.type = " . $type;
            }
            $user = session('user');
            if($user['county']){
                $where .= ' AND A.province = '.$user['province'].' AND A.city = '.$user['city'].' AND A.county = '.$user['county'];
            }
            // var_dump($where);die;
            $sql = "SELECT A.*,CASE WHEN B.region_name IS NULL THEN '总站' ELSE CONCAT(D.region_name,'-',C.region_name,'-',B.region_name) END AS region 
                    FROM (
                        SELECT B.uname,credentials_code,credentials_img,CASE WHEN A.type = 1 THEN '技工' WHEN A.type = 2 THEN '工长' WHEN A.type = 3 THEN '设计师'WHEN  A.type = 4 THEN '装饰公司' WHEN A.type = 5 THEN '商家' END AS type,A.create_time,A.id,B.county,A.uid
                        FROM lg_authenticate A
                        INNER JOIN lg_member B ON A.uid = B.uid
                        WHERE  A.status = 0 $where
                        ORDER BY A.id DESC
                        LIMIT $page_start,$limit
                    )A
                    LEFT JOIN lg_region B ON A.county = B.region_code
                    LEFT JOIN lg_region C ON B.region_superior_code = C.region_code
                    LEFT JOIN lg_region D ON C.region_superior_code = D.region_code

                    ";
            // var_dump($sql);die;
            $data = Db::query($sql);
            $sql2 = "SELECT count(1) AS count
                        FROM lg_authenticate A  
                        INNER JOIN lg_member B ON A.uid = B.uid
                        WHERE  A.status = 0 $where";
            $count = Db::query($sql2);
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $this->assign('type', 1);
            return $this->fetch('index');
        }
    }

    /**
     * 审核管理-审核认证页面
     */
    public function edit()
    {
        if (request()->isPost()) {
            $id = $this->request->get('id');
            $type = $this->request->get('type');
            $post =  $this->request->post();
            $post['checked_time'] = date('Y-m-d H:i:s');
            if ($this->request->get('uid')) {
                $uid = $this->request->get('uid');
                // var_dump($post);die;

            }
            if ($type == 1) {
                $res = Db::name('authenticate')
                    ->where('id', $id)
                    ->update($post);
                if ($res) {
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                    if ($post['checked'] == 1) {
                        $set = Db::name('settings')->where('id = 2')->find();
                        $data =  unserialize($set['val']);
                        Db::name('member')->where('uid', $uid)->setInc('point', $data['card']);
                    }
                }
                return json($this->ret);
            }
            if ($type == 2) {
                $res = Db::name('article')
                    ->where('id', $id)
                    ->update($post);
                if ($res) {
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                }
                return json($this->ret);
            }
            if ($type == 3) {
                $res = Db::name('cases')
                    ->where('id', $id)
                    ->update($post);
                if ($res) {
                    if($post['checked'] == '1'){//审核通过
                        $user = Db::name('cases')->where('id',$id)->field('user_id,type')->find();
                        if($user['type'] == '1'){//技工
                            Db::name('mechanic')->where('uid',$user['user_id'])->setInc('case_num',1);
                        }elseif($user['type'] == '2'){//工长
                            Db::name('gongzhang')->where('uid',$user['user_id'])->setInc('case_num',1);
                        }elseif($user['type'] == '3'){//设计师
                            Db::name('designer')->where('uid',$user['user_id'])->setInc('case_num',1);
                        }elseif($user['type'] == '4'){//装饰公司
                            Db::name('company')->where('uid',$user['user_id'])->setInc('case_num',1);
                        }
                    }
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                }
                return json($this->ret);
            }
            if ($type == 4) {
                $res = Db::name('joining')
                    ->where('id', $id)
                    ->update($post);
                if ($res) {
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                }
                return json($this->ret);
            }
            if ($type == 5) {
                $res = Db::name('company')
                    ->where('id', $id)
                    ->update($post);
                if ($res) {
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                }
                return json($this->ret);
            }
            if ($type == 6) {
                $res = Db::name('designer')
                    ->where('id', $id)
                    ->update($post);
                if ($res) {
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                }
                return json($this->ret);
            }
            if ($type == 7) {
                $res = Db::name('gongzhang')
                    ->where('id', $id)
                    ->update($post);
                if ($res) {
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                }
                return json($this->ret);
            }
            if ($type == 8) {
                $res = Db::name('mechanic')
                    ->where('id', $id)
                    ->update($post);
                if ($res) {
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                }
                return json($this->ret);
            }
        } else {
            $id = $this->request->get('id');
            $type = $this->request->get('type');
            $uid = '';
            if ($this->request->get('uid')) {
                $uid = $this->request->get('uid');
            }
            $this->assign('id', $id);
            $this->assign('type', $type);
            $this->assign('uid', $uid);
            return $this->fetch();
        }
    }
    public function search()
    {
        $type = $this->request->get('type');
        $this->assign('type', $type);
        return $this->fetch();
    }

    public function article()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $keywords = $this->request->param('keywords', '');
            $where = '';
            if ($keywords) {
                $where .= " AND A.title LIKE '%" . $keywords . "%' ";
            }
            $user = session('user');
            if($user['county']){
                $where .= ' AND A.province = '.$user['province'].' AND A.city = '.$user['city'].' AND A.county = '.$user['county'];
            }
            // var_dump($where);die;
            $sql = "SELECT A.*,B.title AS cate
                    FROM (
                        SELECT *
                        FROM lg_article A
                        WHERE  status = 0 AND checked = 0 $where
                        ORDER BY id DESC
                        LIMIT $page_start,$limit
                    )A
                    INNER JOIN lg_article_cate B ON A.cate_id = B.id AND B.status = 1

                    ";
            // var_dump($sql);die;
            $data = Db::query($sql);
            $sql2 = "SELECT count(1) AS count
                        FROM lg_article A  
                        INNER JOIN lg_article_cate B ON A.cate_id = B.id AND B.status = 1
                        WHERE  A.status = 0 AND checked = 0 $where";
            $count = Db::query($sql2);
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $this->assign('type', 2);
            return $this->fetch('index');
        }
    }
    /**
     * 查看文章详情
     */
    public function lookArticle(){
        $id = $this->request->get('id');
        $article = Db::name('article')->where('id',$id)->find();
        $cate = Db::name('article_cate')->where('status',1)->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $this->assign('cate',$cate);
        $this->assign('article', $article);
        return $this->fetch('look_article');
    }
    /**
     * 查看个人信息
     */
    public function lookUser(){
        $id = $this->request->get('id');
        $type = $this->request->get('type');
        die;
        $article = Db::name('article')->where('id',$id)->find();
        $cate = Db::name('article_cate')->where('status',1)->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $this->assign('cate',$cate);
        $this->assign('article', $article);
        return $this->fetch('look_user');
    }
    
    /**
     * 查看案例
     */
    public function lookCases(){
        $id = $this->request->get('id');
        $cases = Db::name('cases')->alias('A')
            ->join('village B','B.id = A.area_id','INNER')
            ->join('member C','C.id = A.user_id','INNER')
            ->join('cases_attr D','D.id = A.home_id','INNER')
            ->join('cases_attr E','E.id = A.position_id','INNER')
            ->join('cases_attr F','F.id = A.price_id','INNER')
            ->where(['A.id'=>$id])
            ->field('A.id,A.case_title,A.area,A.create_time,A.type,A.style,A.thumb,B.village_name,C.uname,C.realname,D.title AS home,E.title AS position,F.title AS price')->find();
        $cases_img = Db::name('case_img')->where(['case_id'=>$cases['id']])
            ->order(['sort' => 'DESC', 'id' => 'ASC'])->field('id,img')->select();
        $this->assign('cases_img',$cases_img);
        $this->assign('cases', $cases);
        return $this->fetch('look_cases');
    }
    /**
     * 案例审核
     */
    public function cases()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $keywords = $this->request->param('keywords', '');
            $where = '';
            if ($keywords) {
                $where .= " AND (A.title LIKE '%" . $keywords . "%' OR B.uname LIKE '%" . $keywords . "%')";
            }
            $user = session('user');
            if($user['county']){
                $where .= ' AND A.province = '.$user['province'].' AND A.city = '.$user['city'].' AND A.county = '.$user['county'];
            }
            // var_dump($where);die;
            $sql = "SELECT A.*,B.uname AS uname
                    FROM (
                        SELECT A.id,A.user_id,A.case_title,A.create_time
                        FROM lg_cases A
                        WHERE  A.deleted = 0 AND A.checked = 0 $where
                        ORDER BY id DESC
                        LIMIT $page_start,$limit
                    )A
                    INNER JOIN lg_member B ON A.user_id = B.id

                    ";
            // var_dump($sql);die;
            $data = Db::query($sql);
            $sql2 = "SELECT count(1) AS count
                        FROM lg_cases A  
                        INNER JOIN lg_member B ON A.user_id = B.id
                        WHERE  A.deleted = 0 AND A.checked = 0 $where";
            $count = Db::query($sql2);
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $this->assign('type', 3);
            return $this->fetch('index');
        }
    }
    public function join()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $keywords = $this->request->param('keywords', '');
            $where = '';
            if ($keywords) {
                $where .= " AND (A.company LIKE '%" . $keywords . "%' OR A.name LIKE '%" . $keywords . "%'   OR A.mobile LIKE '%" . $keywords . "%')";
            }
            // var_dump($where);die;
            $sql = "
                        SELECT *
                        FROM lg_joining A
                        WHERE  checked = 0 $where
                        ORDER BY id DESC
                        LIMIT $page_start,$limit
                   

                    ";
            // var_dump($sql);die;
            $data = Db::query($sql);
            $sql2 = "SELECT count(1) AS count
                        FROM lg_joining A  
                        WHERE  checked = 0 $where";
            $count = Db::query($sql2);
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $this->assign('type', 4);
            return $this->fetch('index');
        }
    }
    /**
     * 装饰公司审核
     */
    public function company(){
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $keywords = $this->request->param('keywords', '');
            $where = '';
            if ($keywords) {
                $where .= " AND (A.short_name LIKE '%" . $keywords . "%' OR A.name LIKE '%" . $keywords . "%' OR A.mobile = '" . $keywords . "')";
            }
            $sql = "SELECT A.*,B.region_name FROM lg_company A LEFT JOIN lg_region B ON B.region_code = A.county WHERE A.checked = 0 AND A.deleted = 0 $where ORDER BY id DESC LIMIT $page_start,$limit";
            $data = Db::query($sql);
            $sql2 = "SELECT count(1) AS count FROM lg_company A WHERE  checked = 0 AND deleted = 0 $where";
            $count = Db::query($sql2);
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $this->assign('type', 5);
            return $this->fetch('index');
        }
    }
    /**
     * 设计师审核
     */
    public function designer(){
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $keywords = $this->request->param('keywords', '');
            $where = '';
            if ($keywords) {
                $where .= " AND (A.name LIKE '%" . $keywords . "%' OR A.mobile = '" . $keywords . "')";
            }
            $sql = "SELECT A.*,B.region_name FROM lg_designer A LEFT JOIN lg_region B ON B.region_code = A.county WHERE A.checked = 0 AND A.deleted = 0 $where ORDER BY id DESC LIMIT $page_start,$limit";
            $data = Db::query($sql);
            $sql2 = "SELECT count(1) AS count FROM lg_designer A WHERE  checked = 0 AND deleted = 0 $where";
            $count = Db::query($sql2);
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $this->assign('type', 6);
            return $this->fetch('index');
        }
    }
    /**
     * 工长审核
     */
    public function gongzhang(){
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $keywords = $this->request->param('keywords', '');
            $where = '';
            if ($keywords) {
                $where .= " AND (A.name LIKE '%" . $keywords . "%' OR A.mobile = '" . $keywords . "')";
            }
            $sql = "SELECT A.*,B.region_name FROM lg_gongzhang A LEFT JOIN lg_region B ON B.region_code = A.county WHERE A.checked = 0 AND A.deleted = 0 $where ORDER BY id DESC LIMIT $page_start,$limit";
            $data = Db::query($sql);
            $sql2 = "SELECT count(1) AS count FROM lg_gongzhang A WHERE  checked = 0 AND deleted = 0 $where";
            $count = Db::query($sql2);
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $this->assign('type', 7);
            return $this->fetch('index');
        }
    }
    /**
     * 技工审核
     */
    public function mechanic(){
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $keywords = $this->request->param('keywords', '');
            $where = '';
            if ($keywords) {
                $where .= " AND (A.name LIKE '%" . $keywords . "%' OR A.mobile = '" . $keywords . "')";
            }
            $sql = "SELECT A.*,B.region_name FROM lg_mechanic A LEFT JOIN lg_region B ON B.region_code = A.county WHERE A.checked = 0 AND A.deleted = 0 $where ORDER BY id DESC LIMIT $page_start,$limit";
            $data = Db::query($sql);
            $sql2 = "SELECT count(1) AS count FROM lg_mechanic A WHERE  checked = 0 AND deleted = 0 $where";
            $count = Db::query($sql2);
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $this->assign('type', 8);
            return $this->fetch('index');
        }
    }
}
