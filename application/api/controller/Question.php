<?php
namespace app\api\controller;

use think\Db;

class Question extends Main
{
    /**
     * 用户平台留言
     */
    public function addQuestion(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['name']) || !isset($post['uid']) || !isset($post['mobile']) || !isset($post['content'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $insert = $post;
            $insert['create_time'] = date('Y-m-d H:i:s');
            $data = Db::name('question')->insert($insert);
            if($data){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '留言成功，等待回复';
            }else{
                $this->ret['msg'] = '留言失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
