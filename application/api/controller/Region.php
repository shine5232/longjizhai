<?php
namespace app\api\controller;

use think\Db;

class Region extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 获取地区信息
     */
    public function index(){
        $post = $this->request->post();
        if($post['type'] == '1'){
            $data = _getRegion();
        }else{
            if(isset($post['is_open']) && $post['is_open'] == '1'){
                $data = _getRegion($post['code'],true);
            }elseif(isset($post['is_open']) && $post['is_open'] == '2'){
                $data = _getRegion($post['code'],false,true);
            }else{
                $data = _getRegion($post['code'],false,false);
            }
        }
        $this->ret['data'] = $data;
        return json($this->ret);
    }
    /**
     * 前端获取城市列表数据
     */
    public function getRegion(){
        if(request()->isPost()){
            $region_list = cache('region_list');
            if($region_list){
                $this->ret['code'] = 200;
                $this->ret['data'] = $region_list;
            }else{
                $region_list = Db::name('region')->where('region_level',1)->field('region_code as code,region_name as text,region_note as children')->order('region_code ASC')->select();
                if($region_list){
                    foreach($region_list as &$vo){
                        $city = Db::name('region')->where('region_superior_code',$vo['code'])->field('region_code as code,region_name as text,region_note as children')->order('region_code ASC')->select();
                        $vo['children'] = $city;
                        if($vo['children']){
                            foreach($vo['children'] as &$v){
                                $county = Db::name('region')->where('region_superior_code',$v['code'])->field('region_code as code,region_name as text')->order('region_code ASC')->select();
                                $v['children'] = $county;
                            }
                        }
                    }
                    cache('region_list',$region_list,86400);
                    $this->ret['code'] = 200;
                    $this->ret['data'] = $region_list;
                }
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
