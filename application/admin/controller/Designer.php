<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class Designer extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 装饰公司管理-数据列表
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $keywords = $this->request->param('keywords', '');
            $city = $this->request->param('city', '');
            $province = $this->request->param('province', '');
            $county = $this->request->param('county', '');
            $where = '';
            if ($keywords) {
                $where .= " AND (A.name LIKE '%$keywords%' OR A.mobile = '$keywords')";
            }
            if ($city) {
                $where .= " AND A.city =" . $city;
            }
            if ($province) {
                $where .= " AND  A.province = " . $province;
            }
            if ($county) {
                $where .= " AND A.county =" . $county;
            }
            $user = session('user');
            if($user['county']){
                $where .= " AND A.county =" . $user['county'];
            }
            $page_start = ($page - 1) * $limit;
            $sql = "SELECT A.*,
                        concat(B.region_name,'-',C.region_name,'-',D.region_name) AS city,
                        E.uname,F.type_title
                FROM (
                    SELECT A.*,
                        CASE WHEN A.is_zong = 1 THEN '是' ELSE '否' END AS zong,B.name AS company_name,
                        CASE WHEN A.checked = 0 THEN '未审' WHEN A.checked = 1 THEN '通过' ELSE '未过' END AS checked_title
                        FROM lg_designer A LEFT JOIN lg_company B ON A.company_id = B.id
                    WHERE A.deleted = 0 " . $where . "
                    ORDER BY id DESC
                    limit $page_start,$limit
                ) A 
                INNER JOIN lg_region B ON A.province = B.region_code
                INNER JOIN lg_region C ON A.city = C.region_code
                INNER JOIN lg_region D ON A.county = D.region_code
                INNER JOIN lg_member E ON A.uid = E.id
                INNER JOIN lg_member_type F ON A.level = F.id";
            // var_dump($sql);die;
            $data = Db::query($sql);
            $sql1 = "SELECT COUNT(1) AS count FROM lg_designer A 
                    INNER JOIN lg_member B ON A.uid = B.uid
                    WHERE A.deleted = 0 " . $where;
            $count = Db::query($sql1);
            // var_dump($count);die;
            if ($data) {
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            return $this->fetch('index');
        }
    }
    /**
     * 装饰公司管理-添加公司
     */
    public function designerAdd(){
        $user = session('user');
        if(request()->isPost()){
            $post = $this->request->post();
            $has = Db::name('designer')->where('uid',$post['uid'])->find();
            if($has){
                $this->ret['msg'] = '用户已关联';
                return json($this->ret);
            }
            $insert = $post;
            if(isset($post['uid']) && $post['uid']){
                $designer = Db::name('member')->where('id',$post['uid'])->field('province,city,county')->find();
                $insert['province'] = $designer['province'];
                $insert['city'] = $designer['city'];
                $insert['county'] = $designer['county'];
            }
            $insert['create_time'] = date('Y-m-d H:i:s');
            $res = Db::name('designer')->insert($insert);
            if($res){
                Db::name('member')->where('id',$post['uid'])->update(array('locked'=>1));
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            $region = _getRegion();
            $wheres['subscribe'] = 1;
            $wheres['locked'] = 0;
            if($user['county']){
                $wheres['county'] = $user['county'];
            }
            $member = Db::name('member')->where($wheres)->field('id,uid,uname,realname')->select();
            $rank = Db::name('member_type')->where('pid',15)->field('id,type_title')->select();
            $ages_where = ['utype'=>3,'type'=>2,'status'=>0,'deleted'=>0];
            $position_where = ['utype'=>3,'type'=>3,'status'=>0,'deleted'=>0];
            $ages_attr = Db::name('member_attr')->where($ages_where)->field('id,title')->select();
            $position_attr = Db::name('member_attr')->where($position_where)->field('id,title')->select();

            $this->assign('regin', $region);
            $this->assign('member',$member);
            $this->assign('rank',$rank);
            $this->assign('ages_attr',$ages_attr);
            $this->assign('position_attr',$position_attr);
            $this->assign('user',$user);
            return $this->fetch('add');
        }
    }
    /**
     * 装饰公司管理-编辑公司
     */
    public function designerEdit(){
        $user = session('user');
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $designer = Db::name('designer')->where('id',$id)->find();
            if($designer['uid'] != $post['uid']){
                $has = Db::name('designer')->where('uid',$post['uid'])->find();
                if($has){
                    $this->ret['msg'] = '用户已关联';
                    return json($this->ret);
                }
            }
            unset($post['imgFile']);
            unset($post['id']);
            $insert = $post;
            if(isset($post['uid']) && $post['uid']){
                $designers = Db::name('member')->where('id',$post['uid'])->field('province,city,county')->find();
                $insert['province'] = $designers['province'];
                $insert['city'] = $designers['city'];
                $insert['county'] = $designers['county'];
            }
            $insert['update_time'] = date('Y-m-d H:i:s');
            $res = Db::name('designer')->where('id',$id)->update($insert);
            if($res){
                if($designer['uid'] != $post['uid']){
                    Db::name('member')->where('id',$designer['uid'])->update(array('locked'=>0));
                }
                Db::name('member')->where('id',$post['uid'])->update(array('locked'=>1));
                $this->ret['code'] = 200;
                $this->ret['msg'] = '修改成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $designer = Db::name('designer')->alias('A')->join('member B','B.id = A.uid','LEFT')->where('A.id',$id)->field('A.*,B.uname')->find();
            $region = _getRegion();
            $wheres['subscribe'] = 1;
            $wheres['locked'] = 0;
            if($user['county']){
                $wheres['county'] = $user['county'];
            }
            $member = Db::name('member')->where($wheres)->field('id,uid,uname,realname')->select();
            $rank = Db::name('member_type')->where('pid',15)->field('id,type_title')->select();
            $ages_where = ['utype'=>3,'type'=>2,'status'=>0,'deleted'=>0];
            $position_where = ['utype'=>3,'type'=>3,'status'=>0,'deleted'=>0];
            $ages_attr = Db::name('member_attr')->where($ages_where)->field('id,title')->select();
            $position_attr = Db::name('member_attr')->where($position_where)->field('id,title')->select();

            $this->assign('regin', $region);
            $this->assign('designer',$designer);
            $this->assign('member',$member);
            $this->assign('rank',$rank);
            $this->assign('ages_attr',$ages_attr);
            $this->assign('position_attr',$position_attr);
            $this->assign('user',$user);
            return $this->fetch('edit');
        }
    }
    /**
     * 装饰公司管理-推荐/撤销 总站
     */
    public function topOrDown(){
        $post = $this->request->post();
        $id = $post['id'];
        $ids = explode(',', $id);
        $upd['update_time'] = date('Y-m-d H:i:s');
        if($post['type'] == '2'){
            $upd['is_zong'] = 1;
        }else{
            $upd['is_zong'] = 0;
        }
        if (count($ids) > 1) {
            $res = Db::name('designer')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('designer')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 设计师管理-设计师删除
     */
    public function designerDel()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'deleted'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('designer')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('designer')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 装饰公司管理-搜索
     */
    public function designerSearch(){
        $user = session('user');
        $region = _getRegion();
        $this->assign('regin', $region);
        $this->assign('user',$user);
        return $this->fetch('search');
    }
    /**
     * 设计师管理-属性管理
     */
    public function attr(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where = [
                'utype'=> 3,
                'deleted'=>0
            ];
            $data = Db::name('member_attr')->where($where)->order('type ASC,sort ASC,id DESC')->limit($page_start, $limit)->select();
            $count = Db::name('member_attr')->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch('attr');
        }
    }
    /**
     * 设计师管理-属性添加
     */
    public function attrAdd(){
        if(request()->isPost()){
            $post = $this->request->post();
            $status = isset($post['status'])?0:1;
            $insert = [
                'title'=>$post['title'],
                'utype'=>3,
                'type'=>$post['type'],
                'sort'=>$post['sort'],
                'status'=>$status,
                'create_time'=>date('Y-m-d H:i:s')
            ];
            $res = Db::name('member_attr')->insert($insert);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            return $this->fetch('attr_add');
        }
    }
    /**
     * 设计师管理-属性编辑
     */
    public function attrEdit(){
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $status = isset($post['status'])?0:1;
            $upd = [
                'title'=>$post['title'],
                'status'=>$status,
                'sort'=>$post['sort'],
                'type'=>$post['type'],
                'update_time'=>date('Y-m-d H:i:s')
            ];
            $res = Db::name('member_attr')->where('id',$id)->update($upd);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '更新成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $test = Db::name('member_attr')->where('id',$id)->find();
            $this->assign('attr',$test);
            return $this->fetch('attr_edit');
        }
    }
    /**
     * 设计师管理-属性修改状态
     */
    public function updateStatus(){
        $id = $this->request->param('id');
        $test = Db::name('member_attr')->where('id', $id)->find();
        $data = [];
        if ($test) {
            $data = [
                'status'        =>  $test['status'] == 0 ? 1 : 0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('member_attr')->where('id', $id)->update($data);
        if ($result) {
            $this->ret['code'] = 200;
        }
        return json($this->ret);
    }
    /**
     * 设计师管理-属性删除
     */
    public function attrDel()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'deleted'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('member_attr')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('member_attr')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 设计师管理-设计师文章
     */
    public function article(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where['a.cate_id'] = 76;
            $where['a.status']=0;
            $user = session('user');
            if($user['county']){
                $where['a.county'] = $user['county'];
            }
            $data = Db::name('article')->alias('a')
                    ->join('article_cate g','a.cate_id = g.id','INNER')
                    ->join('region d','a.county = d.region_code','LEFT')
                    ->join('designer e','a.author = e.id','INNER')
                    ->where($where)
                    ->field('a.*,d.region_name as county_name,g.title as cate_title,e.name as author_name,CASE WHEN a.checked = 0 THEN "未审" WHEN a.checked = 1 THEN "通过" ELSE "未过" END AS checked_title')
                    ->order('a.author DESC,a.sort ASC,a.id DESC')
                    ->limit($page_start, $limit)
                    ->select();
            $count = Db::name('article')->alias('a')
                    ->join('article_cate g','a.cate_id = g.id','INNER')
                    ->join('designer e','a.author = e.id','INNER')
                    ->join('region d','a.county = d.region_code','LEFT')
                    ->where($where)
                    ->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch('article');
        }
    }
    /**
     * 设计师管理-添加文章页面
     */
    public function articleAdd(){
        if(request()->isPost()){
            $post     = $this->request->post();
            if(isset($post['author']) && $post['author']){
                $designer = Db::name('designer')->where('id',$post['author'])->field('province,city,county')->find();
                $post['province'] = $designer['province'];
                $post['city'] = $designer['city'];
                $post['county'] = $designer['county'];
            }
            $post['create_time']   = date('Y-m-d H:i:s');
            $db = Db::name('article')->insert($post);
            if($db){
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        }else{
            $admin_user = session('user');
            $where = ['checked'=>1,'status'=>0,'deleted'=>0];
            if($admin_user['county'] != null){
                $where['county'] = $admin_user['county'];
            }
            $designer = Db::name('designer')->where($where)->field('id,name')->select();
            $this->assign('designer',$designer);
            return $this->fetch('article_add');
        }
    }
    /**
     * 设计师管理-编辑文章页面
     */
    public function articleEdit(){
        if(request()->isPost()){
            $post     = $this->request->post();
            $id = $post['id'];
            $post['update_time']  = date('Y-m-d H:i:s');
            unset($post['id']);
            if(isset($post['author']) && $post['author']){
                $designer = Db::name('designer')->where('id',$post['author'])->field('province,city,county')->find();
                $post['province'] = $designer['province'];
                $post['city'] = $designer['city'];
                $post['county'] = $designer['county'];
            }
            $db = Db::name('article')->where('id',$id)->update($post);
            if($db){
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        }else{
            $id  = $this->request->get('id');
            $article = Db::name('article')->where('id',$id)->find();
            $admin_user = session('user');
            $where = ['checked'=>1,'status'=>0,'deleted'=>0];
            if($admin_user['county'] != null){
                $where['county'] = $admin_user['county'];
            }
            $designer = Db::name('designer')->where($where)->field('id,name')->select();
            $this->assign('designer',$designer);
            $this->assign('article',$article);
            return $this->fetch('article_edit');
        }
    }
    /**
     * 设计师管理-文章搜索页面
     */
    public function search(){
        $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $cate = array2Level($cate);
        $this->assign('cate',$cate);
        return $this->fetch();
    }
    /**
     * 设计师管理-文章删除
     */
    public function articleDel(){
        $id = $this->request->post('id');
        $ids = explode(',',$id);
        $upd = [
            'status'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if(count($ids) > 1){
            $res = Db::name('article')->where('id','in',$id)->update($upd);
        }else{
            $res = Db::name('article')->where('id',$ids[0])->update($upd);
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}