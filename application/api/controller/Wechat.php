<?php
namespace app\api\controller;

use think\Db;
use think\Request;
use wechat\Wechat as weixin;

class Wechat extends Main
{
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
                        ->where($where)->field('A.*,B.*,A.id AS wxid,A.openid AS openids,A.sex AS sexs')->find();
                $res['uper'] = Db::name('member')->where('uid',$res['superior_id'])->value('uname');
                if($res['type'] == '1'){
                    $res['typer'] = '技工';
                }else if($res['type'] == '2'){
                    $res['typer'] = '工长';
                }else if($res['type'] == '3'){
                    $res['typer'] = '设计师';
                }else if($res['type'] == '4'){
                    $res['typer'] = '装饰公司';
                }else if($res['type'] == '5'){
                    $res['typer'] = '商家';
                }else if($res['type'] == '6'){
                    $res['typer'] = '业主';
                }else{
                    $res['typer'] = '会员';
                }
                $res['get_point'] = _getSignPoint(time(),$res['sign_time'],$res['max_time'],$res['sign_fres']);
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