<?php
namespace app\api\controller;

use think\Db;

class MyAddress extends Main
{
    /**
     * 获取用户收货地址列表
     */
    public function getAddressLis(){
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
                'uid' => $post['uid'],
            ];
            $data = Db::name('member_address')->where($where)->order('type DESC')->limit($page_start, $limit)->select();
            if($data){
                foreach($data as $key=>$v){
                    $data[$key]['attr'] = $v['province'].'-'.$v['city'].'-'.$v['county'];
                }
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
     * 获取用户的默认收货地址
     */
    public function getDefaultAddress(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            if($post['id'] == 0){
                $where = [
                    'uid' => $post['uid'],
                    'type' => 1,
                ];
            }else{
                $where = [
                    'id' => $post['id'],
                ];
            }
            $data = Db::name('member_address')->where($where)->find();
            if($data){
                $data['attr'] = $data['province'].'-'.$data['city'].'-'.$data['county'];
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
     * 创建收货地址
     */
    public function creatAddress(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $insert = [
                'uid' => $post['uid'],
                'province' => $post['province'],
                'city' => $post['city'],
                'county' => $post['county'],
                'address' => $post['address'],
                'name' => $post['name'],
                'mobile' => $post['mobile'],
                'create_time' => date('Y-m-d H:i:s'),
                'type' => $post['is_default']?1:0
            ];
            $data = Db::name('member_address')->insert($insert);
            if($data){
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
            }else{
                $this->ret['msg'] = '创建失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 编辑收货地址
     */
    public function updateAddress(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id']) || !isset($post['uid'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $insert = [
                'province' => $post['province'],
                'city' => $post['city'],
                'county' => $post['county'],
                'address' => $post['address'],
                'name' => $post['name'],
                'mobile' => $post['mobile'],
                'update_time' => date('Y-m-d H:i:s'),
                'type' => $post['is_default']?1:0
            ];
            $data = Db::name('member_address')->where('id',$post['id'])->update($insert);
            if($post['is_default']){
                $upd2 = [
                    'type'=>0,
                    'update_time'=>date('Y-m-d H:i:s')
                ];
                $data = Db::name('member_address')->where(['id'=>['neq',$post['id']],'uid'=>$post['uid']])->update($upd2);
            }
            if($data){
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
     * 删除收货地址
     */
    public function delAddress(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $data = Db::name('member_address')->where('id',$post['id'])->delete();
            if($data){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '删除成功';
            }else{
                $this->ret['msg'] = '删除失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 设置默认收货地址
     */
    public function setDefaultAddress(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id']) || !isset($post['uid'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $upd1 = [
                'is_default'=>1,
                'update_time'=>date('Y-m-d H:i:s')
            ];
            $upd2 = [
                'is_default'=>0,
                'update_time'=>date('Y-m-d H:i:s')
            ];
            $data = Db::name('member_address')->where(['id'=>$post['id']])->update($upd1);
            $data = Db::name('member_address')->where(['id'=>['neq',$post['id']],'uid'=>$post['uid']])->update($upd2);
            if($data){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '设置成功';
            }else{
                $this->ret['msg'] = '设置失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
