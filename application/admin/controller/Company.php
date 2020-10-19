<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class Company extends Main
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
                $where .= " AND (A.name LIKE '%$keywords%' OR A.phone = '$keywords')";
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
                        E.uname,E.realname,E.subor,E.superior_id,E.rank_id,F.type_title
                FROM (
                    SELECT A.*,
                        CASE WHEN A.is_zong = 1 THEN '是' ELSE '否' END AS zong,
                        CASE WHEN A.checked = 0 THEN '未审' WHEN A.checked = 1 THEN '通过' ELSE '未过' END AS checked_title
                        FROM lg_company A
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
            $sql1 = "SELECT COUNT(1) AS count FROM lg_company A 
                    INNER JOIN lg_member B ON A.uid = B.uid
                    WHERE A.deleted = 0 " . $where;
            $count = Db::query($sql1);
            // var_dump($count);die;
            if ($data) {
                foreach($data as &$v){
                    $v['superior_id'] = Db::name('member')->where('uid',$v['superior_id'])->value('uname');
                }
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
    public function add(){
        $user = session('user');
        if(request()->isPost()){
            $post = $this->request->post();
            $has = Db::name('company')->where('uid',$post['uid'])->find();
            if($has){
                $this->ret['msg'] = '用户已关联';
                return json($this->ret);
            }
            $post['serve'] = implode(',',$post['serve']);
            unset($post['imgFile']);
            $insert = $post;
            if(isset($post['uid']) && $post['uid']){
                $companys = Db::name('member')->where('id',$post['uid'])->field('province,city,county')->find();
                $insert['province'] = $companys['province'];
                $insert['city'] = $companys['city'];
                $insert['county'] = $companys['county'];
            }
            $insert['create_time'] = date('Y-m-d H:i:s');
            $res = Db::name('company')->insert($insert);
            if($res){
                Db::name('member')->where('id',$post['uid'])->update(array('locked'=>1));
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            $region = _getRegion();
            $this->assign('regin', $region);
            $wheres['subscribe'] = 1;
            $wheres['locked'] = 0;
            if($user['county']){
                $wheres['county'] = $user['county'];
            }
            $member = Db::name('member')->where($wheres)->field('id,uid,uname,realname')->select();
            $rank = Db::name('member_type')->where('pid',16)->field('id,type_title')->select();
            $serve_where = ['utype'=>4,'type'=>4,'status'=>0,'deleted'=>0];
            $scale_where = ['utype'=>4,'type'=>5,'status'=>0,'deleted'=>0];
            $serve_attr = Db::name('member_attr')->where($serve_where)->field('id,title')->select();
            $scale_attr = Db::name('member_attr')->where($scale_where)->field('id,title')->select();
            $this->assign('member',$member);
            $this->assign('rank',$rank);
            $this->assign('serve_attr',$serve_attr);
            $this->assign('scale_attr',$scale_attr);
            $this->assign('user',$user);
            return $this->fetch('add');
        }
    }
    /**
     * 装饰公司管理-编辑公司
     */
    public function edit(){
        $user = session('user');
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $company = Db::name('company')->where('id',$id)->find();
            if($company['uid'] != $post['uid']){
                $has = Db::name('company')->where('uid',$post['uid'])->find();
                if($has){
                    $this->ret['msg'] = '用户已关联';
                    return json($this->ret);
                }
            }
            $post['serve'] = implode(',',$post['serve']);
            unset($post['imgFile']);
            unset($post['id']);
            $insert = $post;
            if(isset($post['uid']) && $post['uid']){
                $companys = Db::name('member')->where('id',$post['uid'])->field('province,city,county')->find();
                $insert['province'] = $companys['province'];
                $insert['city'] = $companys['city'];
                $insert['county'] = $companys['county'];
            }
            $insert['update_time'] = date('Y-m-d H:i:s');
            $res = Db::name('company')->where('id',$id)->update($insert);
            if($res){
                if($company['uid'] != $post['uid']){
                    Db::name('member')->where('id',$company['uid'])->update(array('locked'=>0));
                }
                Db::name('member')->where('id',$post['uid'])->update(array('locked'=>1));
                $this->ret['code'] = 200;
                $this->ret['msg'] = '修改成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $company = Db::name('company')->alias('A')->join('member B','B.id = A.uid','LEFT')->where('A.id',$id)->field('A.*,B.uname')->find();
            $region = _getRegion();
            $wheres['subscribe'] = 1;
            $wheres['locked'] = 0;
            if($user['county']){
                $wheres['county'] = $user['county'];
            }
            $member = Db::name('member')->where($wheres)->field('id,uid,uname,realname')->select();
            $rank = Db::name('member_type')->where('pid',16)->field('id,type_title')->select();
            $serve_where = ['utype'=>4,'type'=>4,'status'=>0,'deleted'=>0];
            $scale_where = ['utype'=>4,'type'=>5,'status'=>0,'deleted'=>0];
            $serve_attr = Db::name('member_attr')->where($serve_where)->field('id,title')->select();
            $scale_attr = Db::name('member_attr')->where($scale_where)->field('id,title')->select();
            
            $this->assign('regin', $region);
            $this->assign('company',$company);
            $this->assign('member',$member);
            $this->assign('rank',$rank);
            $this->assign('serve_attr',$serve_attr);
            $this->assign('scale_attr',$scale_attr);
            $this->assign('user',$user);
            return $this->fetch('edit');
        }
    }
    /**
     * 装饰公司管理-推荐/撤销 总站
     */
    public function updateAll(){
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
            $res = Db::name('company')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('company')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    
    /**
     * 装饰公司管理-装饰公司删除
     */
    public function companyDel()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'deleted'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('company')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('company')->where('id', $ids[0])->update($upd);
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
    public function search(){
        $user = session('user');
        $region = _getRegion();
        $this->assign('regin', $region);
        $this->assign('user',$user);
        return $this->fetch('search');
    }
    /**
     * 装饰公司管理-属性管理
     */
    public function attr(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where = [
                'utype'=> 4,
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
     * 装饰公司管理-属性添加
     */
    public function attrAdd(){
        if(request()->isPost()){
            $post = $this->request->post();
            $status = isset($post['status'])?0:1;
            $insert = [
                'title'=>$post['title'],
                'utype'=>4,
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
     * 装饰公司管理-属性编辑
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
     * 装饰公司管理-属性修改状态
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
     * 装饰公司管理-属性删除
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
     * 装饰公司管理-团队列表
     */
    public function team(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $type = $this->request->param('type','');
            $uname = $this->request->param('uname','');
            $company_id = $this->request->param('company_id','');
            $where['A.company_id'] = $company_id;
            if($type){
                $where['A.type'] = $type;
            }
            if($uname){
                $where['A.uname'] = ['like',"%$uname%"];
            }
            $data = Db::name('company_team')->alias('A')
                ->join('member_attr B','B.id = A.ages','LEFT')
                ->where($where)->order('A.type ASC,A.id DESC')
                ->field('A.*,B.title')
                ->limit($page_start, $limit)
                ->select();
            $count = Db::name('company_team')->alias('A')->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            $company_id = $this->request->get('company_id');
            $this->assign('company_id',$company_id);
            return $this->fetch('team');
        }
    }
    /**
     * 装饰公司管理-团队搜索
     */
    public function searchTeam(){
        $company_id = $this->request->get('company_id');
        $this->assign('company_id',$company_id);
        return $this->fetch('team_search');
    }
    /**
     * 装饰公司管理-人员添加
     */
    public function addTeam(){
        if(request()->isAjax()){
            $company_id = $this->request->param('company_id');
            $type = $this->request->param('type');
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where['A.checked'] = 1;
            $where['A.status'] = 0;
            $where['A.deleted'] = 0;
            $where['A.company_id'] = 0;
            if($type == '1'){
                $data = Db::name('mechanic')->alias('A')
                    ->join('company B','B.id = '.$company_id.' AND B.county = A.county','INNER')
                    ->join('member_type C','C.id = A.level','INNER')
                    ->join('member_attr D','D.id = A.ages','INNER')
                    ->where($where)
                    ->field('A.id,A.name,A.mobile,C.type_title,D.title')
                    ->limit($page_start, $limit)
                    ->select();
                $count = Db::name('mechanic')->alias('A')
                    ->join('company B','B.id = '.$company_id.' AND B.county = A.county','INNER')
                    ->where($where)
                    ->count();
                if ($data) {
                    $this->ret['count'] = $count;
                    $this->ret['data'] = $data;
                }
            }
            if($type == '2'){
                $data = Db::name('gongzhang')->alias('A')
                    ->join('company B','B.id = '.$company_id.' AND B.county = A.county','INNER')
                    ->join('member_type C','C.id = A.level','INNER')
                    ->join('member_attr D','D.id = A.ages','INNER')
                    ->where($where)
                    ->field('A.id,A.name,A.mobile,C.type_title,D.title')
                    ->limit($page_start, $limit)
                    ->select();
                $count = Db::name('gongzhang')->alias('A')
                    ->join('company B','B.id = '.$company_id.' AND B.county = A.county','INNER')
                    ->where($where)
                    ->count();
                if ($data) {
                    $this->ret['count'] = $count;
                    $this->ret['data'] = $data;
                }
            }
            if($type == '3'){
                $data = Db::name('designer')->alias('A')
                    ->join('company B','B.id = '.$company_id.' AND B.county = A.county','INNER')
                    ->join('member_type C','C.id = A.level','INNER')
                    ->join('member_attr D','D.id = A.ages','INNER')
                    ->where($where)
                    ->field('A.id,A.name,A.mobile,C.type_title,D.title')
                    ->limit($page_start, $limit)
                    ->select();
                $count = Db::name('designer')->alias('A')
                    ->join('company B','B.id = '.$company_id.' AND B.county = A.county','INNER')
                    ->where($where)
                    ->count();
                if ($data) {
                    $this->ret['count'] = $count;
                    $this->ret['data'] = $data;
                }
            }
            return json($this->ret);
        }else{
            $company_id = $this->request->get('company_id');
            $type = $this->request->get('type');
            $this->assign('company_id',$company_id);
            $this->assign('type',$type);
            return $this->fetch('team_add');
        }
    }
    /**
     * 装饰公司管理-人员绑定
     */
    public function teamBind(){
        $post = $this->request->post();
        $ids = explode(',', $post['id']);
        if (count($ids) > 1) {
            foreach($ids as $key=>$v){
                $insert[$key]['uid'] = $v;
                $insert[$key]['company_id'] = $post['company_id'];
                $insert[$key]['type'] = $post['type'];
                if($post['type'] == '1'){
                    $mechanic = Db::name('mechanic')->where('id',$v)->field('name,ages')->find();
                    $insert[$key]['uname'] = $mechanic['name'];
                    $insert[$key]['ages'] = $mechanic['ages'];
                }else if($post['type'] == '2'){
                    $gongzhang = Db::name('gongzhang')->where('id',$v)->field('name,ages')->find();
                    $insert[$key]['uname'] = $gongzhang['name'];
                    $insert[$key]['ages'] = $gongzhang['ages'];
                }else if($post['type'] == '3'){
                    $designer = Db::name('designer')->where('id',$v)->field('name,ages')->find();
                    $insert[$key]['uname'] = $designer['name'];
                    $insert[$key]['ages'] = $designer['ages'];
                }
                $insert[$key]['create_time'] = date('Y-m-d H:i:s');
            }
            $res = Db::name('company_team')->insertAll($insert);
        } else {
            $insert['uid'] = $ids[0];
            $insert['company_id'] = $post['company_id'];
            $insert['type'] = $post['type'];
            $insert['create_time'] = date('Y-m-d H:i:s');
            if($post['type'] == '1'){
                $mechanic = Db::name('mechanic')->where('id',$ids[0])->field('name,ages')->find();
                $insert['uname'] = $mechanic['name'];
                $insert['ages'] = $mechanic['ages'];
            }else if($post['type'] == '2'){
                $gongzhang = Db::name('gongzhang')->where('id',$ids[0])->field('name,ages')->find();
                $insert['uname'] = $gongzhang['name'];
                $insert['ages'] = $gongzhang['ages'];
            }else if($post['type'] == '3'){
                $designer = Db::name('designer')->where('id',$ids[0])->field('name,ages')->find();
                $insert['uname'] = $designer['name'];
                $insert['ages'] = $designer['ages'];
            }
            $res = Db::name('company_team')->insert($insert);
        }
        if ($res) {
            if($post['type'] == '1'){
                Db::name('mechanic')->where('id','in',$post['id'])->update(['company_id'=>$post['company_id']]);
            }else if($post['type'] == '2'){
                Db::name('gongzhang')->where('id','in',$post['id'])->update(['company_id'=>$post['company_id']]);
            }else if($post['type'] == '3'){
                Db::name('designer')->where('id','in',$post['id'])->update(['company_id'=>$post['company_id']]);
            }
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 装饰公司管理-人员删除
     */
    public function teamDel()
    {
        $id = $this->request->post('id');
        $post = $this->request->post();
        $ids = explode(',', $id);
        if (count($ids) > 1) {
            foreach($ids as $key=>$v){
                $team = Db::name('company_team')->where('id',$v)->find();
                if($team['type'] == '1'){
                    Db::name('mechanic')->where('id',$v)->update(['company_id'=>0]);
                }else if($team['type'] == '2'){
                    Db::name('gongzhang')->where('id',$v)->update(['company_id'=>0]);
                }else if($team['type'] == '3'){
                    Db::name('designer')->where('id',$v)->update(['company_id'=>0]);
                }
            }
            $res = Db::name('company_team')->where('id', 'in', $id)->delete();
        } else {
            $team = Db::name('company_team')->where('id',$ids[0])->find();
            if($team['type'] == '1'){
                Db::name('mechanic')->where('id',$ids[0])->update(['company_id'=>0]);
            }else if($team['type'] == '2'){
                Db::name('gongzhang')->where('id',$ids[0])->update(['company_id'=>0]);
            }else if($team['type'] == '3'){
                Db::name('designer')->where('id',$ids[0])->update(['company_id'=>0]);
            }
            $res = Db::name('company_team')->where('id', $ids[0])->delete();
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}