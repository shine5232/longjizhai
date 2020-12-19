<?php
namespace app\api\controller;

use think\Db;

class Company extends Main
{
    /**
     * 获取装饰公司列表
     */
    public function getCompanyLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['county']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            if($post['county']>0){
                $where['A.county'] = $post['county'];
            }else{
                $where['A.is_zong'] = 1;
            }
            if(isset($post['keyword']) && $post['keyword'] != ''){//关键词
                $where['A.short_name'] = ['like','%'.$post['keyword'].'%'];
            }
            if(isset($post['rank']) && $post['rank']){//会员等级
                $where['B.rank_id'] = $post['rank'];
            }
            $order = 'A.sort DESC';
            if(isset($post['order']) && $post['order']){
                if($post['order'] == '1'){//热门排序
                    $order = 'A.hot DESC';
                }elseif($post['order'] == '2'){//口碑排序
                    $order = 'A.kou DESC';
                }
            }
            $where['A.status']=['eq',0];
            $where['A.checked']=['eq',1];
            $where['A.deleted']=['eq',0];
            $end_time = date('Y-m-d H:i:s');
            $data = Db::name('company')->alias('A')
                ->join('member B','B.id = A.uid','INNER')
                ->join('member_rank C','C.id = B.rank_id','INNER')
                ->where($where)
                ->where("A.endtime >= '".$end_time."' OR A.endtime IS NULL")
                ->field("B.id,A.short_name,A.case_num,A.site_num,A.thumb,A.logo,A.address,B.rank_id,C.rank_name")
                ->order($order)->limit($page_start, $limit)->select();
            if($data){
                foreach($data as &$v){
                    $v['thumb'] = _getServerName().'/public'.$v['thumb'];
                    $v['logo'] = _getServerName().'/public'.$v['logo'];
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取装饰公司详情
     */
    public function getCompanyInfoById(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where['A.uid']=['eq',$post['id']];
            $where['A.status']=['eq',0];
            $where['A.checked']=['eq',1];
            $where['A.deleted']=['eq',0];
            $end_time = date('Y-m-d H:i:s');
            $data = Db::name('company')->alias('A')
                ->join('member B','B.id = A.uid','INNER')
                ->join('member_rank C','C.id = B.rank_id','INNER')
                ->where($where)
                ->where("A.endtime >= '".$end_time."' OR A.endtime IS NULL")
                ->field("B.id,A.short_name,A.case_num,A.site_num,A.thumb,A.logo,A.address,A.phone,A.contact,A.content,B.area,B.authed,B.rank_id,C.rank_name")
                ->find();
            if($data){
                $data['thumb'] = _getServerName().'/public'.$data['thumb'];
                $data['logo'] = _getServerName().'/public'.$data['logo'];
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取装饰公司案例列表
     */
    public function getCompanyCasesLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id']) || !isset($post['uid']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $where['A.user_id']=['eq',$post['id']];
            $where['A.type']=['eq',4];
            $where['A.checked']=['eq',1];
            $where['A.deleted']=['eq',0];
            $data = Db::name('cases')->alias('A')
                ->where($where)->field("A.id,A.case_title,A.view_num,A.collect_num")->order('A.id DESC')->limit($page_start, $limit)->select();
            if($data){
                foreach($data as $key=>$v){
                    Db::name('cases')->where('id',$v['id'])->setInc('view_num',1);
                    $img = Db::name('case_img')->where('case_id',$v['id'])->field('img')->select();
                    foreach($img as &$vi){
                        $vi['img'] = _getServerName().$vi['img'];
                    }
                    $data[$key]['img'] = array_column($img,'img');
                    $where_coll = [
                        'uid'=>$post['uid'],
                        'collected_id'=> $v['id'],
                        'type'  =>  1,
                    ];
                    $has = Db::name('collect')->where($where_coll)->find();
                    if($has && $has['status'] == 0){
                        $data[$key]['collected'] = 1;
                    }else{
                        $data[$key]['collected'] = 0;
                    }
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取装饰公司团队列表
     */
    public function getCompanyTeamLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id']) || !isset($post['uid']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $where['uid']=['eq',$post['id']];
            $company_id = Db::name('company')->where($where)->value('id');
            $comp_where = [
                'company_id' => $company_id,
            ];
            $data = Db::name('company_team')->where($comp_where)->field('type,uid')->limit($page_start, $limit)->select();
            if($data){
                foreach($data as $key=>$v){
                    if($v['type'] == 1){
                        $user = Db::name('mechanic')->alias('A')
                            ->join('member B','B.id = A.uid','LEFT')
                            ->where('A.id',$v['uid'])
                            ->field('A.id,A.name,A.case_num,B.thumb,B.type')
                            ->find();
                        $data[$key]['typer'] = '技工';
                    }elseif($v['type'] == 2){
                        $user = Db::name('gongzhang')->alias('A')
                            ->join('member B','B.id = A.uid','LEFT')
                            ->where('A.id',$v['uid'])
                            ->field('A.id,A.name,A.case_num,B.thumb,B.type')
                            ->find();
                        $data[$key]['typer'] = '工长';
                    }elseif($v['type'] == 3){
                        $user = Db::name('designer')->alias('A')
                            ->join('member B','B.id = A.uid','LEFT')
                            ->where('A.id',$v['uid'])
                            ->field('A.id,A.name,A.case_num,B.thumb,B.type')
                            ->find();
                        $data[$key]['typer'] = '设计师';
                    }
                    $data[$key]['id'] = $user['id'];
                    $data[$key]['name'] = $user['name'];
                    $data[$key]['case_num'] = $user['case_num'];
                    $data[$key]['type'] = $user['type'];
                    $data[$key]['thumb'] = _getServerName().$user['thumb'];
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
