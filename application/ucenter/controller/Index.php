<?php
namespace app\ucenter\controller;
use \think\Db;

class Index extends Main
{
    public function index()
    {
        $session = session('ucenter');
        if($session){
            $user = Db::name('member')->where('id',$session['uid'])->find();
            $this->assign('user',$user);
            if($user['type'] == '4'){
                $this->assign('type','公司');
            }else if($user['type'] == '5'){
                $this->assign('type','商铺');
            }
            return $this->fetch();
        }else{
            echo '请登录';
        }
    }
}
