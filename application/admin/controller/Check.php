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
                        SELECT B.uname,credentials_code,credentials_img,CASE WHEN A.type = 1 THEN '技工' WHEN A.type = 2 THEN '工长' WHEN A.type = 3 THEN '设计师'WHEN  A.type = 4 THEN '装饰公司' WHEN A.type = 5 THEN '商家' END AS type,A.create_time,A.id,B.county
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
            return $this->fetch('authenticate');
        }
        
    }
    
     /**
     * 审核管理-审核认证页面
     */
    public function editA(){
        $id = $this->request->get('id');
        $this->assign('id', $id);
        return $this->fetch();
    }
    /**
     * 审核管理-审核认证
     */
    public function updateA(){
        $id = $this->request->get('id');
        $post =  $this->request->post();
        $post['checked_time'] = date('Y-m-d H:i:s');
        // var_dump($post);die;
        $res = Db::name('authenticate')
        ->where('id',$id)
        ->update($post);
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
        
    }


    public function searchA(){
        return $this->fetch();
    }

   
}
