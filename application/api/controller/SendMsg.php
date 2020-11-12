<?php
namespace app\api\controller;

use think\Db;
use aliyun\Send;

class SendMsg extends Main
{
    /**
     * 发送短信验证码
     */
    public function sendCode(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['mobile'])){
                $this->ret['msg'] = '缺少参数mobile';
                return json($this->ret);
            }
            $aliyun = new Send();
            $code = $this->random(6,1);
            cache('code_'.$post['mobile'],$code,300);
            $data = $aliyun->send($post['mobile'],['code'=>$code]);
            if($data['code'] == 200){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '发送成功';
            }else{
                $this->ret['msg'] = '发送失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 生成验证码
     */
    public function random($len, $type='', $extchars='')
	{
		switch($type){
			case 1:
				$chars= str_repeat('0123456789',3);
				break;
			case 2:
				$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 3:
				$chars='abcdefghijklmnopqrstuvwxyz';
				break;
			case 4:
				$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
				break;				
			default :
				$chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
		}
		$chars .= $extchars;
		if($len>10 ) {//位数过长重复字符串一定次数
			$chars= $type==1? str_repeat($chars,$len) : str_repeat($chars,5);
		}
		$chars = str_shuffle($chars);
		$charslen = strlen($chars);
		$start = mt_rand(0,$charslen-$len);
		$hash = substr($chars,$start,$len);
		return $hash;
	}
}
