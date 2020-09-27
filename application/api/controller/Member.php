<?php
namespace app\api\controller;

use think\Db;

class Member extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 根据openid获取用户信息
     */
    public function getUserInfoByOpenid(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['openid'])){
                $this->ret['msg'] = '缺少参数openid';
                return json($this->ret);
            }
            $openid = $post['openid'];
            $where['A.openid'] = ['eq',$openid];
            $data = Db::name('member_weixin')->alias('A')
                    ->join('member B','A.openid = B.openid','LEFT')
                    ->where($where)->field('A.*,B.*,A.id AS wxid')->find();
            if($data){
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '暂无数据';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
