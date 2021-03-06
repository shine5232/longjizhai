<?php
namespace app\api\controller;

use think\Db;

class GongZhang extends Main
{
    /**
     * 获取工长列表
     */
    public function getGongZhangLis(){
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
                $where['A.name'] = ['like','%'.$post['keyword'].'%'];
            }
            if(isset($post['rank']) && $post['rank']){//会员等级
                $where['B.rank_id'] = $post['rank'];
            }
            if(isset($post['ages']) && $post['ages']){//工长经验
                $where['A.ages'] = $post['ages'];
            }
            $where['A.status']=['eq',0];
            $where['A.checked']=['eq',1];
            $where['A.deleted']=['eq',0];
            $data = Db::name('gongzhang')->alias('A')
                ->join('member B','B.id = A.uid','INNER')
                ->join('member_rank C','C.id = B.rank_id','INNER')
                ->where($where)->field("B.id,A.name,A.case_num,B.thumb,B.rank_id,C.rank_name")->order('B.id DESC')->limit($page_start, $limit)->select();
            if($data){
                foreach($data as &$v){
                    $v['thumb'] = _getServerName().$v['thumb'];
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
     * 获取工长详情
     */
    public function getGongZhangInfoById(){
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
            $data = Db::name('gongzhang')->alias('A')
                ->join('member B','B.id = A.uid','INNER')
                ->join('member_rank C','C.id = B.rank_id','INNER')
                ->where($where)->field("B.id,A.name,A.case_num,A.mobile,A.content,A.slogan,B.area,B.thumb,B.authed,B.rank_id,C.rank_name")->find();
            if($data){
                $date = date('Y-m-d H:i:s',time() - 86400);
                $uid = isset($post['uid'])?$post['uid']:0;
                $where_look = [
                    'uid' => $uid,
                    'user_id' => $post['id'],
                    'crate_time' => ['>',$date],
                ];
                $look = Db::name('look_mobile')->where($where_look)->order('id DESC')->find();
                if($look){
                    $data['looked'] = 1;
                }else{
                    $data['looked'] = 0;
                }
                $data['mobiled'] = substr_replace($data['mobile'],'****',3,4);
                $data['thumb'] = _getServerName().$data['thumb'];
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
     * 获取工长案例列表
     */
    public function getGongZhangCasesLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id']) || !isset($post['uid']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $where['A.type']=['eq',2];
            $where['A.user_id']=['eq',$post['id']];
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
}
