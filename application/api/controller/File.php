<?php
namespace app\api\controller;

use think\Controller;
use files\Image;

class File extends Main
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
            }else if($type == 'goodsimg'){
                //商品上传
                $src = '/uploads/goodsimg';
                $info = $file->validate(['ext' => 'jpg,png,jpeg,bmp'])->move(ROOT_PATH . 'public' . $src);
            }else if($type == 'thumb'){
                //缩略图上传
                $src = '/uploads/thumb';
                $info = $file->validate(['ext' => 'jpg,png,jpeg,bmp'])->move(ROOT_PATH . 'public' . $src);
                if($info){
                    $url = ROOT_PATH.'/public'.$src.'/'.$info->getSaveName();
                    $Image = new Image();
                    $Image->resizeimage($url,430,430,0,$url);
                    return json(array('error' => 0, 'url' => $src.'/'.$info->getSaveName(),'msg'=>'上传成功'));
                }else{
                    return json(array('error'=>1,'msg'=>'文件格式不合法'));
                }
            }else if($type == 'video'){
                //视频上传
                $src = '/uploads/video';
                $info = $file->validate(['ext' => 'mp4'])->move(ROOT_PATH . 'public' . $src);
            }
            if($info){
                if($type == 'article' || $type == 'goods'){
                    $url = _getServerName().'/public'.$src.'/'.$info->getSaveName();
                }else{
                    $url = '/public'.$src.'/'.$info->getSaveName();
                }
                return json(array('error' => 0, 'url' => $url,'msg'=>'上传成功'));
            }
            return json(array('error' => 1,'msg'=>'上传失败'));
        }
    }
    /**
     * 移动端图片上传
     */
    public function uploadFile(){
        $file = $this->request->file('file');
        $data = $this->request->post();
        if(isset($data['type'])){
            if($data['type'] == 'cases'){
                $src = '/uploads/cases';
            }else if($data['type'] == 'avatar'){
                $src = '/uploads/avatar';
            }else if($data['type'] == 'auths'){
                $src = '/uploads/auths';
            }else if($data['type'] == 'article'){
                //文章内容图片上传
                $src = '/uploads/article';
            }
        }else{
            $src = '/uploads/ping';
        }
        $info = $file->validate(['ext' => 'jpg,png,jpeg,bmp'])->move(ROOT_PATH . 'public' . $src);
        if($info){
            $url = '/public'.$src.'/'.$info->getSaveName();
            return json(array('code' => 200, 'data' => ['url'=>$url],'msg'=>'上传成功'));
        }
        return json(array('error' => 0,'msg'=>'上传失败'));
    }
}

