<?php
/**
 * 微信授权回调跳转.
 */
header("Content-Type: text/html;charset=utf-8");

$code = $_GET['code'];
if(!empty($code))
{
    $redirect_uri = urldecode($_GET['redirect_uri']);
    if (strpos($redirect_uri,'?')!==false){
        $url = $redirect_uri.'&code='.$code.'&state=STATE';
    }else{
        $url = $redirect_uri.'?code='.$code;
    }
    header("location:$url");exit();
}else{
    exit('授权失败');
}


