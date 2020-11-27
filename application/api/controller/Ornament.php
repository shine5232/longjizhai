<?php
namespace app\api\controller;

use think\Db;

class Ornament extends Main
{
    /**
     * 装修需求-添加
     */
    public function addOrnamentInfo(){
        if(request()->isPost()){
            $post = $this->request->post();
            $insert = $post;
            $insert['create_time'] = date('Y-m-d H:i:s');
            $data = Db::name('ornament')->insert($insert);
            if($data){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '发布成功';
            }else{
                $this->ret['msg'] = '发布失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
