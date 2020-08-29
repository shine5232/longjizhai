<?php
namespace app\admin\controller;

use app\admin\model\Cases as CaseModel;
use \think\Db;
use \think\Reuquest;

class check extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
   
    /**
     * 案例管理-案例表页
     */
    public function authenticate(){
        if(request()->isAjax()){
            $page = $this->request->param('page',1,'intval');
            $limit = $this->request->param('limit',20,'intval');
            $page_start = ($page - 1) * $limit;
            $keywords = $this->request->param('keywords','');
            $type = $this->request->param('type','');
            $where = '';
            if($keywords){
                $where .= " AND B.uname LIKE '%".$keywords."%' ";
            }
            if($type){
                $where  .= " AND A.type = ".$type;
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
            if($data){
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            $this->assign('type', 1);
            return $this->fetch('index');
        }
        
    }
    
     /**
     * 审核管理-审核认证页面
     */
    public function edit(){
        $id = $this->request->get('id');
        $type = $this->request->get('type');
        if($this->request->get('uid')){
            $uid = $this->request->get('uid');
        }
        $uid = '';
        $this->assign('id', $id);
        $this->assign('type', $type);
        $this->assign('uid', $uid);
        return $this->fetch();
    }
    /**
     * 审核管理-审核认证
     */
    public function update(){
        $id = $this->request->get('id');
        $type = $this->request->get('type');
        $post =  $this->request->post();
        $post['checked_time'] = date('Y-m-d H:i:s');
        if($this->request->get('uid')){
            $uid = $this->request->get('uid');
            // var_dump($post);die;
            
        }
        if($type == 1){
            $res = Db::name('authenticate')
            ->where('id',$id)
            ->update($post);
            if($res){
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
                if($post['status'] == 1){
                    $set = Db::name('settings')->where('id = 2')->find();
                    $data =  unserialize($set['val']);
                    Db::name('member')->where('uid',$uid)->setInc('point', $data['card']);
                }
            }
            return json($this->ret);
        }
       if ($type == 2) {
        $res = Db::name('article')
        ->where('id',$id)
        ->update($post);
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
       }
       if ($type == 3) {
        $res = Db::name('cases')
        ->where('id',$id)
        ->update($post);
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
       }
       if ($type == 4) {
        $res = Db::name('joining')
        ->where('id',$id)
        ->update($post);
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
       }
       
        
    }


    public function search(){
        $type = $this->request->get('type');
        $this->assign('type', $type);
        return $this->fetch();
    }

    public function article(){
        if(request()->isAjax()){
            $page = $this->request->param('page',1,'intval');
            $limit = $this->request->param('limit',20,'intval');
            $page_start = ($page - 1) * $limit;
            $keywords = $this->request->param('keywords','');
            $where = '';
            if($keywords){
                $where .= " AND A.title LIKE '%".$keywords."%' ";
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
            if($data){
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            $this->assign('type', 2);
            return $this->fetch('index');
        }
        
    }

    public function cases(){
        if(request()->isAjax()){
            $page = $this->request->param('page',1,'intval');
            $limit = $this->request->param('limit',20,'intval');
            $page_start = ($page - 1) * $limit;
            $keywords = $this->request->param('keywords','');
            $where = '';
            if($keywords){
                $where .= " AND (A.title LIKE '%".$keywords."%' OR B.uname LIKE '%".$keywords."%')";
            }
            // var_dump($where);die;
            $sql = "SELECT A.*,B.uname AS uname
                    FROM (
                        SELECT A.id,A.user_id,A.case_title,A.create_time
                        FROM lg_cases A
                        WHERE  deleted = 0 AND checked = 0 $where
                        ORDER BY id DESC
                        LIMIT $page_start,$limit
                    )A
                    INNER JOIN lg_member B ON A.user_id = B.uid

                    ";
                // var_dump($sql);die;
            $data = Db::query($sql);
            $sql2 = "SELECT count(1) AS count
                        FROM lg_cases A  
                        INNER JOIN lg_member B ON A.user_id = B.uid
                        WHERE  A.deleted = 0 AND checked = 0 $where";
            $count = Db::query($sql2);
            if($data){
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            $this->assign('type', 3);
            return $this->fetch('index');
        }
        
    }

    public function join(){
        if(request()->isAjax()){
            $page = $this->request->param('page',1,'intval');
            $limit = $this->request->param('limit',20,'intval');
            $page_start = ($page - 1) * $limit;
            $keywords = $this->request->param('keywords','');
            $where = '';
            if($keywords){
                $where .= " AND (A.company LIKE '%".$keywords."%' OR A.name LIKE '%".$keywords."%'   OR A.mobile LIKE '%".$keywords."%')";
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
            if($data){
                $this->ret['count'] = $count[0]['count'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            $this->assign('type', 4);
            return $this->fetch('index');
        }
        
    }

   
}
