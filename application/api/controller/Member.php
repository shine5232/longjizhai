<?php
namespace app\api\controller;

use think\Db;
use phpcode\QRcode;

class Member extends Main
{
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
            $data = _getUserInfoByOpenid($post['openid']);
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
    /**
     * 根据条件获取人员属性数据
     */
    public function getMechanicAttr(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['pid']) || !isset($post['utype']) || !isset($post['type'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where = [
                'pid'=>$post['pid'],
                'utype'=>$post['utype'],
                'type'=>$post['type'],
                'status'=>0,
                'deleted'=>0,
            ];
            $data = Db::name('member_attr')->where($where)->field('id AS value,title AS text')->order('sort ASC,id DESC')->select();
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
    /**
     * 用户签到
     */
    public function signUser(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where = [
                'id'=>$post['id'],
                'status'=>['neq',1],
            ];
            $data = Db::name('member')->where($where)->field('point,sign_time,max_time,sign_fres')->find();
            if($data){
                $point = _getSignPoint(time(),$data['sign_time'],$data['max_time'],$data['sign_fres']);
                if($post){
                    $upd = [
                        'sign_time' => time(),
                        'max_time'  => strtotime(date('Ymd')) + 86400,
                        'sign_fres' => $point,
                        'point'=>(int)$point + (int)$data['point'],
                    ];
                    $res = Db::name('member')->where('id',$post['id'])->update($upd);
                    if($res){
                        _saveUserPoint($post['id'],$point,1,5,'会员签到');
                        $this->ret['code'] = 200;
                        $this->ret['msg'] = '签到成功';
                    }
                }else{
                    $this->ret['msg'] = '今天已签到';
                }
            }else{
                $this->ret['msg'] = '暂无数据';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取用户签到记录
     */
    public function getPointByUid(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $where = [
                'uid'=>$post['uid'],
            ];
            $data = Db::name('point_log')->where($where)->order('id DESC')->limit($page_start, $limit)->select();
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
    /**
     * 修改用户基本信息(只能修改一次)
     */
    public function myInfo(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['name']) || !isset($post['type']) || !isset($post['mobile']) || !isset($post['code']) || !isset($post['openid']) || !isset($post['pid'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $code = cache('code_'.$post['mobile']);
            if($code != $post['code']){
                $this->ret['msg'] = '验证码不正确';
                return json($this->ret);
            }
            cache('code_'.$post['mobile'],'');
            $uname = 'wx'.rand(10000000,99999999);
            while(_checkUserAccount($uname)){
                $uname = 'wx'.rand(10000000,99999999);
            }
            $type = (int)$post['type'] + 1;
            $insert = [
                'uname' =>  $uname,
                'mobile'    =>  trim($post['mobile']),
                'password'    =>  md5('longjizhai'),
                'superior_id'  =>  $post['pid'],
                'province'  =>  $post['province'],
                'city'  =>  $post['city'],
                'county'=>  $post['county'],
                'type'  =>  $type,
                'area'  =>  $post['area'],
                'realname'  =>  $post['name'],
                'lastlogin' =>  date('Y-m-d H:i:s'),
                'loginip'   =>  getClientIP(),
                'regip'     =>  getClientIP(),
                'create_time'=> date('Y-m-d H:i:s'),
                'openid'    =>  $post['openid'],
                'uid'   =>  $post['wxid'],
                'sex'   =>  $post['sex'],
                'source' => $post['pid']>0?4:2,
                'city_lock'=>1,
                'type_lock'=>1,
            ];
            if($type<=3){
                $insert['avatar_lock'] = 1;
                $insert['thumb'] = $post['fileLists'][0];
            }
            $member_id = Db::name('member')->insertGetId($insert);
            if($member_id){
                _updatePoint($member_id,10,1);
                _saveUserPoint($member_id,10,1,'1','用户注册');
                $mechanic = [
                    'uid'       =>  $member_id,
                    'province'  =>  $insert['province'],
                    'city'      =>  $insert['city'],
                    'county'    =>  $insert['county'],
                    'name'      =>  $insert['realname'],
                    'mobile'    =>  $insert['mobile'],
                    'create_time'=> date('Y-m-d H:i:s'),
                ];
                if($type == '1'){
                    Db::name('mechanic')->insert($mechanic);
                }elseif($type == '2'){
                    Db::name('gongzhang')->insert($mechanic);
                }elseif($type == '3'){
                    Db::name('designer')->insert($mechanic);
                }elseif($type == '4'){
                    Db::name('company')->insert($mechanic);
                }elseif($type == '5'){
                    unset($mechanic['name']);
                    $mechanic['user']=$insert['realname'];
                    Db::name('shop')->insert($mechanic);
                }
                $this->ret['code'] = 200;
                $this->ret['msg'] = '修改成功';
            }else{
                $this->ret['msg'] = '修改失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 用户查看技工、工长、设计师电话
     */
    public function lookUserMobile(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['userid']) || !isset($post['type'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $insert = [
                'uid'=>$post['uid'],
                'user_id'=>$post['userid'],
                'crate_time'=>date('Y-m-d H:i:s'),
            ];
            $data = Db::name('look_mobile')->insert($insert);
            if($data){
                if($post['type'] == '1'){
                    $typer = '技工';
                }elseif($post['type'] == '2'){
                    $typer = '工长';
                }elseif($post['type'] == '3'){
                    $typer = '设计师';
                }   
                //更新积分
                _updatePoint($post['uid'],2,0);
                //记录积分日志
                _saveUserPoint($post['uid'],2,0,'9','查看['.$typer.']电话');
                $this->ret['code'] = 200;
                $this->ret['msg'] = '查看成功';
            }else{
                $this->ret['msg'] = '查看失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取用户二维码
     */
    public function getMyCode(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where = [
                'id'=>$post['uid'],
            ];
            $img = Db::name('member')->where($where)->value('code_img');
            if(!$img){
                $img = $this->makeCode($post['uid']);
                $upd = [
                    'code_img' => $img
                ];
                Db::name('member')->where('id',$post['uid'])->update($upd);
            }
            $this->ret['code'] = 200;
            $this->ret['data'] = _getServerName().'/'.$img;
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 生成用户专属二维码
     */
    public function makeCode($uid){
        $member = Db::name('member')->alias('A')
            ->join('member_weixin B','B.openid = A.openid','INNER')
            ->where('A.id',$uid)->field('A.id,A.type,B.nickname,B.avatar')
            ->find();
        $avatar = 'public/uploads/avatar/avatar'.$member['id'].'.jpg';
        if(!file_exists($avatar)){
            $QR2 = imagecreatefromstring(file_get_contents($member['avatar']));
            imagejpeg($QR2,'public/uploads/avatar/avatar'.$member['id'].'.jpg'); //输出头像图片
        }
        $url = 'http://mobile.harus.icu';
        $value = $url.'/home?superior_id='.$member['id'];
        if($member['type'] == '1'){
            $value = $url.'/store/jigongdetail/'.$member['id'].'?superior_id='.$member['id'];
        }elseif($member['type'] == '2'){
            $value = $url.'/store/gongzhangdetail/'.$member['id'].'?superior_id='.$member['id'];
        }elseif($member['type'] == '3'){
            $value = $url.'/store/designerdetail/'.$member['id'].'?superior_id='.$member['id'];
        }elseif($member['type'] == '4'){
            $value = $url.'/store/companydetail/'.$member['id'].'?superior_id='.$member['id'];
        }elseif($member['type'] == '5'){
            $value = $url.'/store/detail/'.$member['id'].'?superior_id='.$member['id'];
        }
        $QRcode = new QRcode();
        $errorCorrectionLevel = 'L';//容错级别 
		$matrixPointSize = 6;//生成图片大小 
        $QRcode->png($value,'public/codes/qrcodem'.$member['id'].'.png', $errorCorrectionLevel, $matrixPointSize, 2);
        $wechat_bind_qr = 'public/codes/qrcodem'.$member['id'].'.png';
		$QR2 = 'public/static/images/background.png';
		if ($QR2 !== FALSE) { 
			$QR2 = imagecreatefromstring(file_get_contents($QR2)); 
			$logo2 = imagecreatefromstring(file_get_contents($wechat_bind_qr)); 
			$QR_width2 = imagesx($QR2);//图片宽度 
			$QR_height2 = imagesy($QR2);//图片高度 
			$logo_width2 = imagesx($logo2);//logo图片宽度 
			$logo_height2 = imagesy($logo2);//logo图片高度 
			//$scale2 = $logo_width2/$logo_qr_width2; 
			$logo_qr_width2 = 790; 
			$logo_qr_height2 = 790; 
			//$from_width2 = ($QR_width2 - $logo_qr_width2) / 2; 
			imagecopyresampled($QR2, $logo2, 5, 5, 0, 0, $logo_qr_width2, $logo_qr_height2, $logo_width2, $logo_height2); //重新组合图片并调整大小 
			                //背景图 前图 左边距 上边距 未知 未知  前图嵌入宽度 前图嵌入高度 前图宽度 前图高度
			//imagepng($QR2, 'themes/green/ucenter/code/2helloweba'.$id.'.png'); //输出图片 
			imagepng($QR2, 'public/codes/2helloweba'.$member['id'].'.png'); //输出图片 
        }
        $logos2 = 'public/static/images/logo231.jpg'; 
		$logos2 = imagecreatefromstring(file_get_contents($logos2)); 
		$QR_width2 = imagesx($QR2);//图片宽度 
		$QR_height2 = imagesy($QR2);//图片高度 
		$logo_width2 = imagesx($logos2);//logo图片宽度 
		$logo_height2 = imagesy($logos2);//logo图片高度 
		//$scale2 = $logo_width2/$logo_qr_width2; 
		$logo_qr_width2 = 200; 
		$logo_qr_height2 = 200; 
		//$from_width2 = ($QR_width2 - $logo_qr_width2) / 2; 
		imagecopyresampled($QR2 , $logos2 , 300, 300, 0, 0, $logo_qr_width2, $logo_qr_height2, $logo_width2, $logo_height2); //重新组合图片并调整大小 
		//背景图 前图 左边距 上边距 未知 未知  前图嵌入宽度 前图嵌入高度 前图宽度 前图高度
		//imagepng($QR2 , 'themes/green/ucenter/code/2helloweba'.$id.'.png'); //输出图片 
		imagepng($QR2 , 'public/codes/2helloweba'.$member['id'].'.png'); //输出图片 
		imagedestroy($QR2);
        $src2 = 'public/codes/2helloweba'.$member['id'].'.png';

        $QR = 'public/static/images/code_bj0103.png';  //准备好的背景图片
		//$logo = 'themes/green/ucenter/code/qrcode'.$id.'.png'; //已经生成的原始图
		if ($QR !== FALSE) { 
		 $QR = imagecreatefromstring(file_get_contents($QR)); 
		 $logo = imagecreatefromstring(file_get_contents($wechat_bind_qr)); 
		 $QR_width = imagesx($QR);//图片宽度 
		 $QR_height = imagesy($QR);//图片高度 
		 $logo_width = imagesx($logo);//logo图片宽度 
		 $logo_height = imagesy($logo);//logo图片高度 
		 //$scale = $logo_width/$logo_qr_width; 
		 $logo_qr_width = 172; 
		 $logo_qr_height = 172; 
		 //$from_width = ($QR_width - $logo_qr_width) / 2; 
		 imagecopyresampled($QR, $logo, 343, 480, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height); //重新组合图片并调整大小 
			                //背景图 前图 左边距 上边距 未知 未知  前图嵌入宽度 前图嵌入高度 前图宽度 前图高度
		 //imagepng($QR, 'themes/green/ucenter/code/helloweba'.$id.'.png'); //输出图片 
		 imagepng($QR, 'public/codes/helloweba'.$member['id'].'.png'); //输出图片  
        }
        $logos = 'public/static/images/logo231.png'; 
		$logos = imagecreatefromstring(file_get_contents($logos)); 
		$QR_width = imagesx($QR);//图片宽度 
		$QR_height = imagesy($QR);//图片高度 
		$logo_width = imagesx($logos);//logo图片宽度 
		$logo_height = imagesy($logos);//logo图片高度 
		//$scale = $logo_width/$logo_qr_width; 
		$logo_qr_width = 30; 
		$logo_qr_height = 30; 
		//$from_width = ($QR_width - $logo_qr_width) / 2; 
		imagecopyresampled($QR , $logos , 414, 551, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height); //重新组合图片并调整大小 
		//背景图 前图 左边距 上边距 未知 未知  前图嵌入宽度 前图嵌入高度 前图宽度 前图高度
		//imagepng($QR , 'themes/green/ucenter/code/helloweba'.$id.'.png'); //输出图片 
		imagepng($QR , 'public/codes/helloweba'.$member['id'].'.png'); //输出图片 
        imagedestroy($QR);
        
        //$QT = 'themes/green/ucenter/code/helloweba'.$id.'.png';
		$QT = 'public/codes/helloweba'.$member['id'].'.png';
		if($member['avatar']){
			$markImg = $avatar;
		}else{
			$markImg = 'public/static/images/logo231.jpg';
		}		
		$markImg = $this->yuan_img($markImg); //圆图
		$QT = imagecreatefromstring(file_get_contents($QT)); //带图片
		//$markImg = imagecreatefromstring(file_get_contents($markImg)); //用户头像 
		imagecopyresampled($QT, $markImg, 50, 19, 0, 0, 70, 70, 472, 472); //重新组合图片并调整大小 
		//imagepng($QT, 'themes/green/ucenter/code/helloweba'.$id.'.png'); //输出图片 
		imagepng($QT, 'public/codes/helloweba'.$member['id'].'.png'); //输出图片 
		imagedestroy($QT); 
		//$QS = 'themes/green/ucenter/code/helloweba'.$id.'.png';
		$QS = 'public/codes/helloweba'.$member['id'].'.png';
		$text = $member['nickname'];
		$font = 'public/static/fonts/watermark/msyh.ttf';
		$QS = imagecreatefromstring(file_get_contents($QS));
		$black = imagecolorallocatealpha($QS,255,255,255,0);
		imagefttext($QS, 22, 0, 140, 44, $black, 'public/static/fonts/watermark/simkai.ttf','龍吉宅代言人');
		imagefttext($QS, 20, 0, 140, 80, $black, $font, $text);
		//imagepng($QS, 'themes/green/ucenter/code/helloweba'.$id.'.png'); //输出图片 
		imagepng($QS, 'public/codes/helloweba'.$member['id'].'.png'); //输出图片 
		imagedestroy($QS); 
		//$src = 'themes/green/ucenter/code/helloweba' . $id . '.png';
        $src = 'public/codes/helloweba'.$member['id'].'.png';
        return $src;
    }
    /**
     * 图片圆形处理
     */
    public function yuan_img($imgpath) {
		$ext     = pathinfo($imgpath);
        $src_img = null;
		switch ($ext['extension']) {
			case 'jpg':
			    $src_img = imagecreatefromjpeg($imgpath);
			    break;
			case 'png':
			    $src_img = imagecreatefrompng($imgpath);
			    break;
		}
        // $wh  = getimagesize($imgpath);
		list($width, $height) = getimagesize($imgpath);
		$new_width = 472;
		$new_height = 472;
		$image_p = imagecreatetruecolor($new_width, $new_height);
		imagecopyresampled($image_p, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		$w   = 472;
		$h   = 472;
		$w   = min($w, $h);
		$h   = $w;
		$img = imagecreatetruecolor($w, $h);
		//这一句一定要有
		imagesavealpha($img, true);
		//拾取一个完全透明的颜色,最后一个参数127为全透明
		$bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
		imagefill($img, 0, 0, $bg);
		$r   = $w / 2; //圆半径
		$y_x = $r; //圆心X坐标
		$y_y = $r; //圆心Y坐标
		for ($x = 0; $x < $w; $x++) {
			for ($y = 0; $y < $h; $y++) {
				$rgbColor = imagecolorat($image_p, $x, $y);
				if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
				imagesetpixel($img, $x, $y, $rgbColor);
				}
			}
		}
		return $img;
    }
    /**
     * 获取会员排行榜(前100名)
     */
    public function getUsersRank(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            if($page_start <= 10){
                $data = _getMyUsersByUid($post['uid'],0,$page_start,$limit);
                if($data){
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
    /**
     * 获取我的会员数据
     */
    public function getMyUserLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['type']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $type = (int)$post['type'] + 1;
            $data = _getMyUsersByUid($post['uid'],$type,$page_start,$limit);
            if($data){
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
     * 根据用户uid统计一级、二级、三级会员总数
     */
    public function getMyUsersCount(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $data['one'] = $data['two'] = $data['three'] = 0;
            $where1 = [
                'superior_id'=>$post['uid'],
                'status'=>['neq',1],
                'subscribe'=>1
            ];
            $data['one'] = Db::name('member')->where($where1)->count();//一级会员数量
            $where2 = [
                'superior2_id'=>$post['uid'],
                'status'=>['neq',1],
                'subscribe'=>1
            ];
            $data['two'] = Db::name('member')->where($where2)->count();//二级会员数量
            $where3 = [
                'superior3_id'=>$post['uid'],
                'status'=>['neq',1],
                'subscribe'=>1
            ];
            $data['three'] = Db::name('member')->where($where3)->count();//三级会员数量
            $this->ret['code'] = 200;
            $this->ret['data'] = $data;
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取同城会员数据
     */
    public function getCityUsersLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['county']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $data = _getUsersByCounty($post['uid'],$post['county'],$page_start,$limit);
            if($data){
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
     * 更新技工、工长、设计师头像
     */
    public function updateUserThumb(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['fileLists'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $upd = [
                'thumb' => $post['fileLists'][0],
                'avatar_lock' => 1,
            ];
            $data = Db::name('member')->where('id',$post['uid'])->update($upd);
            if($data){
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
     * 会员认证
     */
    public function userCertification(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['img1']) || !isset($post['img2'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $insert = [
                'uid'   =>  $post['uid'],
                'type'  =>  $post['type'],
                'province'  =>  $post['province'],
                'city'      =>  $post['city'],
                'county'    =>  $post['county'],
                'credentials_code'  =>  $post['card_num'],
                'credentials_img1' => $post['img1'],
                'credentials_img2' => $post['img2'],
                'create_time'   =>  date('Y-m-d H:i:s'),
            ];
            $authenticate = Db::name('authenticate')->where('uid',$post['uid'])->value('id');
            if($authenticate){
                $upd = [
                    'county'    =>  $post['county'],
                    'credentials_code'  =>  $post['card_num'],
                    'credentials_img1' => $post['img1'],
                    'credentials_img2' => $post['img2'],
                    'update_time'   =>  date('Y-m-d H:i:s'),
                    'checked'   =>  0,
                ];
                $data = Db::name('authenticate')->where('uid',$post['uid'])->update($upd);
            }else{
                $data = Db::name('authenticate')->insert($insert);
            }
            if($data){
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
