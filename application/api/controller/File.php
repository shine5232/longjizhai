<?php
namespace app\api\controller;

use think\Controller;
use files\Image;

class File extends Controller
{
    /**
     * 文件上传
     */
    public function upload($type='article'){
        if($this->request->isPost()){
            $file = $this->request->file('imgFile');
            if($type == 'article'){
                //文章内容图片上传
                $src = '/uploads/article';
                $info = $file->move(ROOT_PATH . 'public' . $src);
            }else if($type == 'logo'){
                //logo上传
                $src = '/uploads/logo';
                $info = $file->validate(['ext' => 'jpg,png,jpeg,bmp'])->move(ROOT_PATH . 'public' . $src);
            }else if($type == 'brands'){
                //品牌上传
                $src = '/uploads/brands';
                $info = $file->validate(['ext' => 'jpg,png,jpeg,bmp'])->move(ROOT_PATH . 'public' . $src);
            }else if($type == 'goods'){
                //商品上传
                $src = '/uploads/goods';
                $info = $file->validate(['ext' => 'jpg,png,jpeg,bmp'])->move(ROOT_PATH . 'public' . $src);
            }else if($type == 'thumb'){
                //缩略图上传
                $src = '/uploads/thumb';
                $info = $file->validate(['ext' => 'jpg,png,jpeg,bmp'])->move(ROOT_PATH . 'public' . $src);
                if($info){
                    $url = ROOT_PATH.'/public'.$src.'/'.$info->getSaveName();
                    $Image = new Image();
                    $Image->resizeimage($url,150,150,0,$url);
                    return json(array('error' => 0, 'url' => $src.'/'.$info->getSaveName(),'msg'=>'上传成功'));
                }else{
                    return json(array('error'=>1,'msg'=>'文件格式不合法'));
                }
            }
            if($info){
                $url = '/public'.$src.'/'.$info->getSaveName();
                return json(array('error' => 0, 'url' => $url,'msg'=>'上传成功'));
            }
            return json(array('error' => 1,'msg'=>'上传失败'));
        }
    }

}

