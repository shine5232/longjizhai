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
            $sql = "SELECT A.type,B.uname,A.credentials_code,A.credentials_img1,A.credentials_img2,B.area,CASE WHEN A.type = 1 THEN '技工' WHEN A.type = 2 THEN '工长' WHEN A.type = 3 THEN '设计师'WHEN  A.type = 4 THEN '装饰公司' WHEN A.type = 5 THEN '商家' END AS type,A.create_time,A.id,B.county,A.uid
                        FROM lg_authenticate A
                        INNER JOIN lg_member B ON A.uid = B.id
                        WHERE  A.status = 0 $where
                        ORDER BY A.id DESC
                        LIMIT $page_start,$limit";
            //var_dump($sql);die;
            $data = Db::query($sql);
            $sql2 = "SELECT count(1) AS count
                        FROM lg_authenticate A  
                        INNER JOIN lg_member B ON A.uid = B.id
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
            $uid = '';
            if ($this->request->get('uid')) {
                $uid = $this->request->get('uid');
                // var_dump($post);die;

            }
            if ($type == 1) {
                $uid = $this->request->get('uid');
                $res = Db::name('authenticate')
                    ->where('id', $id)
                    ->update($post);
                if ($res) {
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                    if ($post['checked'] == 1) {
                        $set = Db::name('settings')->where('id = 2')->find();
                        $data =  unserialize($set['val']);
                        _updatePoint($uid,$data['card'],1);
                        $msg = '实名认证：奖励积分';
                        _saveUserPoint($uid,$data['card'],1,'4',$msg);
                        Db::name('member')->where('id',$uid)->update(['authed'=>1]);
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
                            _updateCasesNum('mechanic',$user['user_id'],1);
                        }elseif($user['type'] == '2'){//工长
                            _updateCasesNum('gongzhang',$user['user_id'],1);
                        }elseif($user['type'] == '3'){//设计师
                            _updateCasesNum('designer',$user['user_id'],1);
                        }elseif($user['type'] == '4'){//装饰公司
                            _updateCasesNum('company',$user['user_id'],1);
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
                    if($post['checked'] == 1){//审核通过
                        Db::name('member')->where('id',$uid)->update(['checked'=>1]);
                    }
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
                    if($post['checked'] == 1){//审核通过
                        Db::name('member')->where('id',$uid)->update(['checked'=>1]);
                    }
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
                    if($post['checked'] == 1){//审核通过
                        Db::name('member')->where('id',$uid)->update(['checked'=>1]);
                    }
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
                    if($post['checked'] == 1){//审核通过
                        Db::name('member')->where('id',$uid)->update(['checked'=>1]);
                    }
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                }
                return json($this->ret);
            }
            if ($type == 9) {
                $res = Db::name('shop')
                    ->where('id', $id)
                    ->update($post);
                if ($res) {
                    if($post['checked'] == 1){//审核通过
                        Db::name('member')->where('id',$uid)->update(['checked'=>1]);
                    }
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
        $where['A.id'] = $id;
        if($type == '6'){
            $data = Db::name('designer')->alias('A')
                ->join('member B','B.id = A.uid','LEFT')
                ->where($where)->field('B.id,B.thumb,B.type_lock,B.avatar_lock,B.city_lock,B.area,B.uname,A.name,A.mobile,A.create_time')
                ->find();
        }else if($type == '7'){
            $data = Db::name('gongzhang')->alias('A')
                ->join('member B','B.id = A.uid','LEFT')
                ->where($where)->field('B.id,B.thumb,B.type_lock,B.avatar_lock,B.city_lock,B.area,B.uname,A.name,A.mobile,A.create_time')
                ->find();
        }else if($type == '8'){
            $data = Db::name('mechanic')->alias('A')
                ->join('member B','B.id = A.uid','LEFT')
                ->where($where)->field('B.id,B.thumb,B.type_lock,B.avatar_lock,B.city_lock,B.area,B.uname,A.name,A.mobile,A.create_time')
                ->find();
        }else if($type == '9'){
            $data = Db::name('shop')->alias('A')
                ->join('member B','B.id = A.uid','LEFT')
                ->where($where)->field('B.id,B.thumb,B.type_lock,B.avatar_lock,B.city_lock,B.area,B.uname,A.user AS name,A.mobile,A.create_time')
                ->find();
        }
        $this->assign('type',$type);
        $this->assign('data', $data);
        return $this->fetch('look_user');
    }
    /**
     * 修改用户锁定状态
     */
    public function updateStatus(){
        $id = $this->request->param('id');
        $type = $this->request->param('type');
        $test = Db::name('member')->where('id', $id)->find();
        $data = [];
        if ($test) {
            $data = [
                $type        =>  $test[$type] == 0 ? 1 : 0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('member')->where('id', $id)->update($data);
        if ($result) {
            $this->ret['code'] = 200;
        }
        return json($this->ret);
    }
    /**
     * 查看案例
     */
    public function lookCases(){
        $id = $this->request->get('id');
        $cases = Db::name('cases')->alias('A')
            ->join('village B','B.id = A.area_id','INNER')
            ->join('member C','C.id = A.user_id','INNER')
            ->join('cases_attr D','D.id = A.home_id','LEFT')
            ->join('cases_attr F','F.id = A.price_id','LEFT')
            ->where(['A.id'=>$id])
            ->field('A.id,A.case_title,A.area,A.create_time,A.type,A.style,A.thumb,B.village_name,C.uname,C.realname,D.title AS home,F.title AS price')->find();
        if($cases['type'] == 3){
            $position = Db::name('case_img')->alias('A')
                ->join('cases_attr B','B.id = A.position_id','LEFT')->where(['case_id'=>$cases['id']])->field('A.position_id,B.title AS position')->group('position_id')->select();
            if($position){
                foreach($position as $key=>$v){
                    $position[$key]['img'] = Db::name('case_img')->where(['case_id'=>$cases['id'],'position_id'=>$v['position_id']])->field('img')->select();
                }
            }
            $cases_img = $position;
        }else{
            $cases_img = Db::name('case_img')->where(['case_id'=>$cases['id']])
                ->order(['sort' => 'DESC', 'id' => 'ASC'])->field('id,img,position_id')->select();
        }
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
                        WHERE  checked >= 0 $where
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
    /**
     * 商家审核
     */
    public function shop(){
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $keywords = $this->request->param('keywords', '');
            $where = '';
            if ($keywords) {
                $where .= " AND (A.name LIKE '%" . $keywords . "%' OR A.name LIKE '%" . $keywords . "%' OR A.mobile = '" . $keywords . "')";
            }
            $sql = "SELECT A.*,B.region_name FROM lg_shop A LEFT JOIN lg_region B ON B.region_code = A.county WHERE A.checked = 0 AND A.deleted = 0 $where ORDER BY id DESC LIMIT $page_start,$limit";
            $data = Db::query($sql);
            $sql2 = "SELECT count(1) AS count FROM lg_shop A WHERE  checked = 0 AND deleted = 0 $where";
            $count = Db::query($sql2);
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            $this->assign('type', 9);
            return $this->fetch('index');
        }
    }
}
