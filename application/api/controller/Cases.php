<?php
namespace app\api\controller;

use think\Db;

class Cases extends Main
{
    /**
     * 根据条件获取案例属性分类
     */
    public function getCasesAttr(){
        if(request()->isPost()){
            $post = $this->request->post();
            $type = isset($post['type'])?$post['type']:0;
            $where = ['type'=>$type,'status'=>0,'deleted'=>0];
            $data = Db::name('cases_attr')->where($where)->order('sort ASC,id DESC')->field('id AS value,title AS text')->select();
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
     * 技工、工长、设计师上传案例
     */
    public function addCases(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['county']) || !isset($post['title']) || !isset($post['type'])){
                $this->ret['msg'] = '缺少必要参数';
                return json($this->ret);
            }
            $insert = [
                'user_id' => $post['uid'],
                'province'=>    $post['province'],
                'city'  =>  $post['city'],
                'county'  =>  $post['county'],
                'case_title'  =>  $post['title'],
                'area_id'  =>  $post['community'],
                'area'  =>  $post['area'],
                'type'  =>  $post['type'],
                'style'  =>  $post['style'],
                'home_id'  =>  $post['home'],
                'position_id'  =>  $post['position'],
                'price_id'  =>  $post['price'],
                'thumb' => $post['fileLists'][0],
                'create_time'   =>  date('Y-m-d H:i:s'),
            ];
            $cases_id = Db::name('cases')->insertGetId($insert);
            if($cases_id){
                foreach($post['fileLists'] as $key=>$v){
                    $img = [
                        'case_id' => $cases_id,
                        'img'   =>  $v,
                        'create_time'   =>  date('Y-m-d H:i:s')
                    ];
                    Db::name('case_img')->insert($img);
                }
                $this->ret['code'] = 200;
                $this->ret['msg'] = '上传成功，等待审核';
            }else{
                $this->ret['msg'] = '上传失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
