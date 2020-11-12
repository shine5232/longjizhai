<?php
namespace app\admin\controller;

use app\admin\model\Cases as CaseModel;
use \think\Db;
use \think\Reuquest;

class cases extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
   
    /**
     * 案例管理-案例表页
     */
    public function index($type){
        if(request()->isAjax()){
            $page = $this->request->param('page',1,'intval');
            $limit = $this->request->param('limit',20,'intval');
            $page_start = ($page - 1) * $limit;
            $user = session('user');
            $where = 'A.type = '.$type.' AND A.deleted = 0 ';
            if($user['county']){
                $where .= ' AND A.province = '.$user['province'] .' AND A.city = '.$user['city'].' AND A.county = '.$user['county'].' ';
            }
            $sql = "SELECT A.* 
                    FROM (
                        SELECT case_title,A.area,C.village_name,B.uname,view_num,collect_num,sort,CASE WHEN is_zong = 0 THEN '否' ELSE '是' END AS is_zong,A.create_time,A.id,A.county
                        FROM lg_cases A
                        INNER JOIN lg_member B ON A.user_id = B.id
                        INNER JOIN lg_village C ON A.area_id = C.id
                        WHERE ".$where." 
                            AND A.deleted = 0
                        ORDER BY sort DESC
                        LIMIT $page_start,$limit
                    ) A";
                // var_dump($sql);die;
                $data = Db::query($sql);
            $count = Db::name('cases')->alias('A')
                ->where($where)
                ->count();
            if($data){
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch('index',['type'=>$type]);
        }
        
    }
    
    /**
     * 案例管理-案例删除
     */
    public function delete(){
        $id = $this->request->post('id');
        $method = $this->request->post('method');
        if($method == 1){
            $upd = [
                'deleted'    =>  1,
                'delete_time'   =>  date('Y-m-d H:i:s')
            ];
            
            $res = Db::name('cases')->where('id',$id)->update($upd);
        }else{
            $res = Db::name('case_img')->where('id',$id)->delete();
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }

    /**
     * 案例管理-推荐/取消推荐案例
     */
    public function updCase(){
        $id = $this->request->post('id');
        $data = Db::name('cases')
            ->field('is_zong')
            ->where('id',$id)
            ->find();
        if($data['is_zong'] == 0){
            $upd = [
                'is_zong'    =>  1
            ];
        }else{
            $upd = [
                'is_zong'    =>  0
            ];
        }
        
        $res = Db::name('cases')->where('id',$id)->update($upd);
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }

    /**
     * 技工管理-批量管理
     */
    public function update($method){
        // var_dump($method);die;
        $delList = $this->request->post('delList');
        $delList = json_decode($delList,true);
        $arr = [];
        if($method == 1){
            foreach ($delList as $k => $v) {
                $data['id'] = $v;
                $data['deleted'] = 1;
                $arr[] = $data;
            }
        }elseif($method == 2){
            foreach ($delList as $k => $v) {
                $data['id'] = $v;
                $data['is_zong'] = 1;
                $arr[] = $data;
            }
        }elseif($method == 3){
            foreach ($delList as $k => $v) {
                $data['id'] = $v;
                $data['is_zong'] = 0;
                $arr[] = $data;
            } 
        }else{
            foreach ($delList as $k => $v) {
                $res = Db::name('case_img')->where('id',$v)->delete();
            }
            
            if($res){
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);   
        }
            $case = new CaseModel;
            $res = $case->saveAll($arr);
            if($res){
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
        
        return json($this->ret);
        
    }

    public function img(){
        $id = $this->request->get('id');
        if(request()->isAjax()){
            $page = $this->request->param('page',1,'intval');
            $limit = $this->request->param('limit',20,'intval');
            $page_start = ($page - 1) * $limit;
            $data = Db::name('case_img')
                    ->where('case_id',$id)
                    ->order('create_time DESC')
                    ->limit($page_start,$limit)
                    ->select();
            $count = Db::name('case_img')
                ->where('case_id',$id)
                ->count();
            if($data){
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            $this->assign('id', $id);
            return $this->fetch();
        }
    }
    /**
     * 案例属性管理-属性管理
     */
    public function attr(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where = ['deleted'=>0];
            $data = Db::name('cases_attr')->where($where)->order('type ASC,sort ASC,id DESC')->limit($page_start, $limit)->select();
            $count = Db::name('cases_attr')->where($where)->count();
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
     * 案例属性管理-属性添加
     */
    public function attrAdd(){
        if(request()->isPost()){
            $post = $this->request->post();
            $status = isset($post['status'])?0:1;
            $insert = [
                'title'=>$post['title'],
                'type'=>$post['type'],
                'sort'=>$post['sort'],
                'status'=>$status,
                'create_time'=>date('Y-m-d H:i:s')
            ];
            $res = Db::name('cases_attr')->insert($insert);
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
     * 案例属性管理-属性编辑
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
            $res = Db::name('cases_attr')->where('id',$id)->update($upd);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '更新成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $test = Db::name('cases_attr')->where('id',$id)->find();
            $this->assign('attr',$test);
            return $this->fetch('attr_edit');
        }
    }
    /**
     * 案例属性管理-属性修改状态
     */
    public function updateStatus(){
        $id = $this->request->param('id');
        $test = Db::name('cases_attr')->where('id', $id)->find();
        $data = [];
        if ($test) {
            $data = [
                'status'        =>  $test['status'] == 0 ? 1 : 0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('cases_attr')->where('id', $id)->update($data);
        if ($result) {
            $this->ret['code'] = 200;
        }
        return json($this->ret);
    }
    /**
     * 案例属性管理-属性删除
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
            $res = Db::name('cases_attr')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('cases_attr')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
}
