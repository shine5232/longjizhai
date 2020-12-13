<?php
namespace app\api\controller;

use think\Db;

class Budget extends Main
{
    /**
     * 用户新增智能预算
     */
    public function addBudget(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $insert = $post;
            $user = Db::name('member')->where('uid',$post['uid'])->find('mobile,province,city,county');
            if($user){
                $insert['province'] = $user['province'];
                $insert['mobile'] = $user['mobile'];
                $insert['city'] = $user['city'];
                $insert['county'] = $user['county'];
            }
            $insert['create_time'] = date('Y-m-d H:i:s');
            $budget_id = Db::name('budget')->insertGetId($insert);
            if($budget_id){
                $province_name = Db::name('region')->where('region_code',$user['province'])->value('region_name');
                $city_name = Db::name('region')->where('region_code',$user['city'])->value('region_name');
                $county_name = Db::name('region')->where('region_code',$user['county'])->value('region_name');
                $url = 'http://bdyhgg.longjizhai.com/ashx/guanggao/bd/getquote2.ashx';
                $params = [
                    'mark'=>2,
                    'xm'=>'百度用户',
                    'sj'=>$user['mobile'],
                    'sheng'=>$province_name,
                    'shi'=>$city_name,
                    'qu'=>$county_name,
                    'yzm'=>'',
                    'jishi'=>$insert['shi'],
                    'jiting'=>$insert['ting'],
                    'jicanting'=>$insert['can'],
                    'jiwei'=>$insert['wei'],
                    'jichu'=>$insert['chu'],
                    'jiyangtai'=>$insert['yang'],
                    'louceng'=>$insert['storey'],
                    'jiegou'=>$insert['structure'],
                    'jzmj'=>$insert['mianji'],
                    'zxfg'=>$insert['style'],
                    'zxdc'=>$insert['level'],
                    'jhtr'=>0
                ];
                $res = json_decode(_requestPost($url,$params),true);
                if($res['status'] === 'ok'){
                    $insert_info['data_info'] = json_encode($res['message']);
                    $insert_info['budget_id'] = $budget_id;
                    $insert_info['data_sum'] = $res['heji'];
                    $insert_info['uid'] = $post['uid'];
                    $insert_info['create_time'] = date('Y-m-d H:i:s');
                    Db::name('budget_info')->insert($insert_info);
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $budget_id;
            }else{
                $this->ret['msg'] = '操作失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取用户智能预算详情
     */
    public function getBudgetInfoById(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $data = Db::name('budget_info')->where(['budget_id' => $post['id'],'uid'=>$post['uid']])->find();
            if($data){
                $info = json_decode($data['data_info'],true);
                foreach($info as $key=>$vo){
                    $info[$key]['checked'] = true;
                    if($vo['child']){
                        foreach($vo['child'] as $k=>$v){
                            $info[$key]['child'][$k]['checked'] = true;
                        }
                    }
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = [
                    'data'=>$info,
                    'heji'=>$data['data_sum'],
                ];
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
