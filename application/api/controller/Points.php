<?php
namespace app\api\controller;

use think\Db;

class Points extends Main
{
    /**
     * 获取积分排行名次(前100名)
     */
    public function getPointsRank(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $where = [
                'A.status'=>['neq',1],
                'A.subscribe'=>['eq',1],
            ];
            if($page_start <= 10){
                $data = Db::name('member')->alias('A')
                    ->join('member_weixin B','B.openid = A.openid','INNER')
                    ->where($where)->field("A.id,A.point,A.thumb,A.realname,B.avatar,B.nickname,(CASE WHEN A.type = 0 THEN '会员' WHEN A.type = 1 THEN '技工' WHEN A.type = 2 THEN '工长' WHEN A.type = 3 THEN '设计师' WHEN A.type = 4 THEN '装饰公司' WHEN A.type = 5 THEN '商家' ELSE '业主' END) AS typer")->order('A.point DESC')->limit($page_start, $limit)->select();
                if($data){
                    foreach($data as $key=>$v){
                        if($v['thumb']){
                            $data[$key]['thumb'] = _getServerName().$v['thumb'];
                        }
                    }
                    $this->ret['code'] = 200;
                    $this->ret['data'] = $data;
                }else{
                    $this->ret['msg'] = '获取失败';
                }
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
