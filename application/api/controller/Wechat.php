<?php
namespace app\api\controller;

use think\Db;
use think\Request;
use wechat\Wechat as weixin;

class Wechat extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];

    /**
     * 微信授权获取用户信息
     */
    public function getWxUser(){
        if(request()->isPost()){
            $code = $this->request->post('code');
            $superior_id = $this->request->post('superior_id','');
            $weixin = new weixin();
            $userInfo = $weixin->getUserInfo($code);
            if($userInfo){
                $data = $userInfo;
                $data['superior_id'] = $superior_id;
                $this->weiXinMember($data);
                $where['A.openid'] = ['eq',$data['openid']];
                $res = Db::name('member_weixin')->alias('A')
                        ->join('member B','A.openid = B.openid','LEFT')
                        ->where($where)->field('A.*,B.*,A.id AS wxid')->find();
                $this->ret['data'] = $res;
                $this->ret['code'] = 200;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 将数据插入用户信息表
     */
    public function weiXinMember($data){
        $has = Db::name('member_weixin')->where('openid',$data['openid'])->find();
        if($has){
            $upd = [
                'nickname'=>$data['nickname'],
                'avatar'=>$data['headimgurl'],
                'sex'=>$data['sex'],
            ];
            Db::name('member_weixin')->where('openid',$data['openid'])->update($upd);
        }else{
            $insert = [
                'openid' => $data['openid'],
                'nickname'=>$data['nickname'],
                'avatar'=>$data['headimgurl'],
                'sex'=>$data['sex'],
                'pid'=>$data['superior_id'],
                'create_time'=>date('Y-m-d H:i:s')
            ];
            Db::name('member_weixin')->insert($insert);
        }
    }
    /**
     * 获取微信js-SDK配置签名
     */
    public function getSignPackage(){
        if(request()->isPost()){
            $url = $this->request->post('url');
            $weixin = new weixin();
            $package = $weixin->getSignPackage($url);
            if($package){
                $this->ret['data'] = $package;
                $this->ret['code'] = 200; 
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}