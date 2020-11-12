<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use think\Db;

function isMobile()
{
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    if (isset($_SERVER['HTTP_VIA'])) {
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}
/**
 * 子元素计数器
 * @param array $array
 * @param int   $pid
 * @return array
 */
function array_children_count($array, $pid)
{
    $counter = [];
    foreach ($array as $item) {
        $count = isset($counter[$item[$pid]]) ? $counter[$item[$pid]] : 0;
        $count++;
        $counter[$item[$pid]] = $count;
    }
    return $counter;
}
/**
 * 数组层级缩进转换
 * @param array $array 源数组
 * @param int   $pid
 * @param int   $level
 * @return array
 */
function array2level($array, $pid = 0, $level = 1)
{
    static $list = [];
    foreach ($array as $v) {
        if ($v['pid'] == $pid) {
            $v['level'] = $level;
            $list[]     = $v;
            array2level($array, $v['id'], $level + 1);
        }
    }
    return $list;
}
/**
 * 构建层级（树状）数组
 * @param array  $array          要进行处理的一维数组，经过该函数处理后，该数组自动转为树状数组
 * @param string $pid_name       父级ID的字段名
 * @param string $child_key_name 子元素键名
 * @return array|bool
 */
function array2tree(&$array, $pid_name = 'pid', $child_key_name = 'children')
{
    $counter = array_children_count($array, $pid_name);
    if (!isset($counter[0]) || $counter[0] == 0) {
        return $array;
    }
    $tree = [];
    while (isset($counter[0]) && $counter[0] > 0) {
        $temp = array_shift($array);
        if (isset($counter[$temp['id']]) && $counter[$temp['id']] > 0) {
            array_push($array, $temp);
        } else {
            if ($temp[$pid_name] == 0) {
                $tree[] = $temp;
            } else {
                $array = array_child_append($array, $temp[$pid_name], $temp, $child_key_name);
            }
        }
        $counter = array_children_count($array, $pid_name);
    }
    return $tree;
}
/**
 * 把元素插入到对应的父元素$child_key_name字段
 * @param        $parent
 * @param        $pid
 * @param        $child
 * @param string $child_key_name 子元素键名
 * @return mixed
 */
function array_child_append($parent, $pid, $child, $child_key_name)
{
    foreach ($parent as &$item) {
        if ($item['id'] == $pid) {
            if (!isset($item[$child_key_name])) {
                $item[$child_key_name] = [];
            }

            $item[$child_key_name][] = $child;
        }
    }
    return $parent;
}
/**
 * 手机号格式检查
 * @param string $mobile
 * @return bool
 */
function check_mobile_number($mobile)
{
    if (!is_numeric($mobile)) {
        return false;
    }
    $reg = '#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#';

    return preg_match($reg, $mobile) ? true : false;
}
//获取客户端真实IP
function getClientIP()
{
    global $ip;
    if (getenv("HTTP_CLIENT_IP")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } else if (getenv("HTTP_X_FORWARDED_FOR")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    } else if (getenv("REMOTE_ADDR")) {
        $ip = getenv("REMOTE_ADDR");
    } else {
        $ip = "Unknow";
    }

    return $ip;
}
/**
 * 获取当前服务器域名
 */
function _getServerName(){
    $server_url = $_SERVER['SERVER_NAME']?"http://".$_SERVER['SERVER_NAME']:"http://".$_SERVER['HTTP_HOST'];
    return $server_url;
}
/**
 * 获取 IP  地理位置
 * 淘宝IP接口
 * @Return: array
 */
function getCity($ip = '')
{
    if($ip == ''){
        $url = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json";
        $ip=json_decode(file_get_contents($url),true);
        $data = $ip;
    }else{
        $url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
        $ip=json_decode(file_get_contents($url));   
        if((string)$ip->code=='1'){
           return false;
        }
        $data = (array)$ip->data;
    }
    
    return $data;   
}
/**
 * 获取地区数据
 */
function _getRegion($parent_code=false,$is_open = false,$all=false){
    if($parent_code){
        $where['region_superior_code'] = $parent_code;
        if($all){
            $data = Db::name('region')->where($where)->field('region_id,region_code,region_name,region_short_name,is_open')->order('region_sort DESC')->select();
        }else{
            if($is_open){
                $where['is_open'] = 1;
                $data = Db::name('region')->where($where)->field('region_id,region_code,region_name,region_short_name')->order('region_sort DESC')->select();
            }else{
                $where['is_open'] = 0;
                $data = Db::name('region')->where($where)->field('region_id,region_code,region_name,region_short_name')->order('region_sort DESC')->select();
            }
        }
    }else{
        $data = Db::name('region')->where('region_level',1)->field('region_id,region_code,region_name,region_short_name')->select();
    }
    return $data; 
}
/**
 * 根据地区code获取地区数据
 */
function _getRegionNameByCode($code = false){
    $data = '';
    if($code){
        $where['region_code'] = $code;
        $data = Db::name('region')->where($where)->field('region_id,region_code,region_name,region_short_name')->find();
    }
    return $data;
}
/**
 * 获取商品分类信息
 */
function _getGoodsCate($pid=false,$parents=false){
    if($parents){//获取当前分类的父分类
        $where = ['id'=>$pid,'status'=>1];
        $data = Db::name('goods_cate')->where($where)->value('pid');
    }else{//获取当前分类的子分类
        if($pid){
            $where = ['pid'=>$pid,'status'=>1];
        }else{
            $where = ['level'=>1,'status'=>1];
        }
        $data = Db::name('goods_cate')->where($where)->field('id,title')->select();
    }
    return $data;
}
/**
 * 获取所有上级分类的名称 【xxx】【xxx】
 */
function _getAllCateTitle($pid,$title=''){
    while($pid != 0){
        $cate_p = Db::name('goods_cate')->where('id',$pid)->where('status',1)->field('id,title,pid')->find();
        $title = '【'.$cate_p['title'].'】'.$title;
        $pid = $cate_p['pid'];
    }
    return $title;
}
/**
 * 根据分类获取品牌列表
 */
function _getBrandsByCate($cate_id){
    $brands = Db::name('goods_cate')->where('id',$cate_id)->value('brands');
    $where = [
        'status'=>0,
        'id'=>['in',$brands]
    ];
    $brandlis = Db::name('brands')->where($where)->field('id,name')->select();
    return $brandlis;
}
/**
 * 根据当前人员分组获取子类
 */
function _getLevel($pid){
    $data = Db::name('member_type')->where('pid',$pid)->field('id,type_title')->select();
    return $data;
}
/**
 * 根据当前工种获取子类
 */
function _getChildrenGong($pid){
    $data = Db::name('member_attr')->where('pid',$pid)->field('id,title')->select();
    return $data;
}
/**
 * api返回json数据
 */
function json($data=array('code'=>0,'msg'=>'error','data'=>'')){
    //输出json  
    echo json_encode($data);
}
/**
 * 数组参数以&符拼接成字符串 
 */
function _paramArrayToStr($param){
    $str = '?';
    if(is_array($param)){
        if(count($param)){
            foreach($param as $key=>$v){
                $str .= $key.'='.$v.'&';
            }
        }else{
            $str = '';
        }
    }
    $str = rtrim($str,'&');
    return $str;
}
/**
 * 计算签到获得积分
 */
function _getSignPoint($new_time,$sign_time,$max_time,$sign_fres){
    if($new_time  > ( $max_time + 86400 )){//超过1天再次签到
        $point = 1;
    }else{
        if($new_time < $max_time){//24小时之内再次签到
            $point = 0;
        }else{
            if($sign_fres == '5'){
                $point = 1;
            }else{
                $point = $sign_fres + 1;
            }
        }
    }
    return $point;
}
/**
 * 记录用户积分数据
 */
function _saveUserPoint($uid,$point,$type,$from,$msg){
    if($type == 0){
        $point = '-'.$point;
    }
    $insert = [
        'uid'=>$uid,
        'point' => $point,
        'point_from' => $from,
        'remark' => $msg,
        'create_time'=>date('Y-m-d H:i:s'),
    ];
    $res = Db::name('point_log')->insert($insert);
    return $res;
}
/**
 * 更新用户积分
 */
function _updatePoint($uid,$point,$type){
    $points = Db::name('member')->where('id',$uid)->value('point');
    if($type == 0){
        $upd['point'] = $points - $point;
    }else{
        $upd['point'] = $points + $point;
    }
    $res = Db::name('member')->where('id',$uid)->update($upd);
    return $res;
}
/**
 * 检查用户账号是否存在
 */
function _checkUserAccount($uname){
    $user = Db::name('member')->where('uname',$uname)->find();
    if($user){
        return true;
    }else{
        return false;
    }
}
/**
 * 更新用户下级数量
 */
function _updateSubor($uid,$type){
    $subor = Db::name('member')->where('id',$uid)->value('subor');
    if($type == 0){
        $upd['subor'] = $subor - 1;
    }else{
        $upd['subor'] = $subor + 1;
    }
    $res = Db::name('member')->where('id',$uid)->update($upd);
    return $res;
}
/**
 * 更新预约数量
 */
function _updateAppointNum($type,$uid,$num){
    if($type == 'mechanic'){
        $mechanic = Db::name('mechanic')->where('uid',$uid)->field('yuyue_num')->find();
        $yuyue_new = (int)$mechanic['yuyue_num'] + $num;
        $upd = [
            'yuyue_num' => $yuyue_new,
        ];
        return Db::name('mechanic')->where('uid',$uid)->update($upd);
    }elseif($type == 'gongzhang'){
        $gongzhang = Db::name('gongzhang')->where('uid',$uid)->field('yuyue_num')->find();
        $yuyue_new = (int)$gongzhang['yuyue_num'] + $num;
        $upd = [
            'yuyue_num' => $yuyue_new,
        ];
        return Db::name('gongzhang')->where('uid',$uid)->update($upd);
    }elseif($type == 'designer'){
        $designer = Db::name('designer')->where('uid',$uid)->field('yuyue_num')->find();
        $yuyue_new = (int)$designer['yuyue_num'] + $num;
        $upd = [
            'yuyue_num' => $yuyue_new,
        ];
        return Db::name('designer')->where('uid',$uid)->update($upd);
    }
}
/**
 * 更新评价得分
 */
function _updateScore($type,$uid,$score1=0,$score2=0,$score3=0,$score4=0,$score5=0){
    if($type == 'mechanic'){
        $mechanic = Db::name('mechanic')->where('uid',$uid)->field('score,score1,score2,score3')->find();
        $score1_new = (int)$mechanic['score1'] + (int)$score1;
        $score2_new = (int)$mechanic['score2'] + (int)$score2;
        $score3_new = (int)$mechanic['score3'] + (int)$score3;
        $score_new = $score1_new + $score2_new + $score3_new;
        $upd = [
            'score' => $score_new,
            'score1'=> $score1_new,
            'score2'=> $score2_new,
            'score3'=> $score3_new,
        ];
        return Db::name('mechanic')->where('uid',$uid)->update($upd);
    }elseif($type == 'gongzhang'){
        $gongzhang = Db::name('gongzhang')->where('uid',$uid)->field('score,score1,score2,score3')->find();
        $score1_new = (int)$gongzhang['score1'] + (int)$score1;
        $score2_new = (int)$gongzhang['score2'] + (int)$score2;
        $score3_new = (int)$gongzhang['score3'] + (int)$score3;
        $score_new = $score1_new + $score2_new + $score3_new;
        $upd = [
            'score' => $score_new,
            'score1'=> $score1_new,
            'score2'=> $score2_new,
            'score3'=> $score3_new,
        ];
        return Db::name('gongzhang')->where('uid',$uid)->update($upd);
    }elseif($type == 'designer'){
        $designer = Db::name('designer')->where('uid',$uid)->field('score,score1,score2,score3')->find();
        $score1_new = (int)$designer['score1'] + (int)$score1;
        $score2_new = (int)$designer['score2'] + (int)$score2;
        $score3_new = (int)$designer['score3'] + (int)$score3;
        $score_new = $score1_new + $score2_new + $score3_new;
        $upd = [
            'score' => $score_new,
            'score1'=> $score1_new,
            'score2'=> $score2_new,
            'score3'=> $score3_new,
        ];
        return Db::name('designer')->where('uid',$uid)->update($upd);
    }
}
/**
 * 更新评论数量
 */
function _updateCommentNum($type,$uid,$num){
    if($type == 'mechanic'){
        $mechanic = Db::name('mechanic')->where('uid',$uid)->field('comments')->find();
        $comments_new = (int)$mechanic['comments'] + $num;
        $upd = [
            'comments' => $comments_new,
        ];
        return Db::name('mechanic')->where('uid',$uid)->update($upd);
    }elseif($type == 'gongzhang'){
        $gongzhang = Db::name('gongzhang')->where('uid',$uid)->field('comments')->find();
        $comments_new = (int)$gongzhang['comments'] + $num;
        $upd = [
            'comments' => $comments_new,
        ];
        return Db::name('gongzhang')->where('uid',$uid)->update($upd);
    }elseif($type == 'designer'){
        $designer = Db::name('designer')->where('uid',$uid)->field('comments')->find();
        $comments_new = (int)$designer['comments'] + $num;
        $upd = [
            'comments' => $comments_new,
        ];
        return Db::name('designer')->where('uid',$uid)->update($upd);
    }
}
/**
 * 更新案例数量
 */
/**
 * 更新收藏数量
 */