<?php
namespace app\api\controller;

use think\Db;

class Comment extends Main
{
    /**
     * 用户评论技工、工长、设计师
     */
    public function assessToUser(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['userid']) || !isset($post['type'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $insert = [
                'comment_uid'=>$post['uid'],
                'uid'=>$post['userid'],
                'design_point'=>$post['zong'],
                'care_point'=>$post['product'],
                'service_point'=>$post['serve'],
                'content'=>$post['message'],
                'create_time'=>date('Y-m-d H:i:s'),
            ];
            //插入评论表
            $comment_id = Db::name('comment_user')->insertGetId($insert);
            if($comment_id){
                //插入评论图片表
                if(isset($post['img']) && count($post['img'])){
                    foreach($post['img'] as $key=>$v){
                        $insert_img = [
                            'comment_user_id' => $comment_id,
                            'comment_img'   =>  $v,
                            'create_time'   =>  date('Y-m-d H:i:s'),
                        ];
                        Db::name('comment_user_img')->insert($insert_img);
                    }
                }
                //更新用户数据
                if($post['type'] == 'mechanic'){
                    _updateScore('mechanic',$post['userid'],$post['zong'],$post['serve'],$post['product']);
                    _updateCommentNum('mechanic',$post['userid'],1);
                }elseif($post['type'] == 'gongzhang'){
                    _updateScore('gongzhang',$post['userid'],$post['zong'],$post['serve'],$post['product']);
                    _updateCommentNum('gongzhang',$post['userid'],1);
                }elseif($post['type'] == 'designer'){
                    _updateScore('designer',$post['userid'],$post['zong'],$post['serve'],$post['product']);
                    _updateCommentNum('designer',$post['userid'],1);
                }
                $this->ret['code'] = 200;
                $this->ret['msg'] = '评论成功';
            }else{
                $this->ret['msg'] = '评论失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
