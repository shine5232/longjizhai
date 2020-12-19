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
            $user = Db::name('member')->where('uid',$post['uid'])->field('mobile,province,city,county,uname')->find();
            if($user){
                $insert['province'] = $user['province'];
                $insert['mobile'] = $user['mobile'];
                $insert['city'] = $user['city'];
                $insert['county'] = $user['county'];
            }
            $insert['fushi'] = $insert['fushi']?"是":"否";
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
                    'uname'=>$user['uname'],
                    'jiting'=>$insert['ting'],
                    'jicanting'=>$insert['can'],
                    'jiwei'=>$insert['wei'],
                    'jichu'=>$insert['chu'],
                    'sffs'=>$insert['fushi'],
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
                    $info = $res['message'];
                    if($insert['style'] == '中式'){
                        $insert_info['style_id'] = 1;
                    }else if($insert['style'] == '欧式'){
                        $insert_info['style_id'] = 2;
                    }else if($insert['style'] == '现代'){
                        $insert_info['style_id'] = 3;
                    }else if($insert['style'] == '田园'){
                        $insert_info['style_id'] = 4;
                    }else if($insert['style'] == '地中海'){
                        $insert_info['style_id'] = 5;
                    }else if($insert['style'] == '东南亚'){
                        $insert_info['style_id'] = 6;
                    }else if($insert['style'] == '混搭'){
                        $insert_info['style_id'] = 7;
                    }
                    foreach($info as $key=>$vo){
                        $info[$key]['checked'] = $vo['pm'] != 0 ? true : false;
                        if($vo['pid'] > 1){//非基础装修部分，根据类型查询品牌
                            //34-50为主材部分；
                            //51-86为成品购买部分；
                            //87-97为家居部分；
                            //98-102为家电部分；
                            //103-109为环保部分；
                            //110-118为奢侈品部分
                            $brands = [];
                            $info[$key]['sj'] = 0;
                            if($vo['child']){
                                foreach($vo['child'] as $k=>$v){
                                    $info[$key]['child'][$k]['checked'] = $info[$key]['checked'];
                                    $info[$key]['child'][$k]['sj_price'] = 0;
                                    $info[$key]['child'][$k]['sj_hj'] = 0;
                                    $info[$key]['child'][$k]['price'] = $v['dj'];
                                    $info[$key]['child'][$k]['sj_brands'] = [];
                                    $info[$key]['child'][$k]['cate'] = 0;
                                    if($v['xuhao'] >= 34 && $v['xuhao'] <= 38){//地砖、墙砖 cate=59
                                        $brands = _getBrandsByCate(59);
                                        $info[$key]['child'][$k]['cate'] = 59;
                                    }else if($v['xuhao'] == 39){//地板 cate=15
                                        $brands = _getBrandsByCate(15);
                                        $info[$key]['child'][$k]['cate'] = 15;
                                    }else if($v['xuhao'] >= 40 && $v['xuhao'] <= 45){//门、踢脚线、套口 cate=120
                                        $brands = _getBrandsByCate(120);
                                        $info[$key]['child'][$k]['cate'] = 120;
                                    }else if($v['xuhao'] >= 46 && $v['xuhao'] <= 48){//集成吊顶 cate=16
                                        $brands = _getBrandsByCate(16);
                                        $info[$key]['child'][$k]['cate'] = 16;
                                    }else if($v['xuhao'] == 49){//集成吊顶送暖系统 cate=166
                                        $brands = _getBrandsByCate(166);
                                        $info[$key]['child'][$k]['cate'] = 166;
                                    }else if($v['xuhao'] == 50){//壁纸 cate=515 壁布 cate=516
                                        $brands = _getBrandsByCate(5);
                                        $info[$key]['child'][$k]['cate'] = 5;
                                    }else if($v['xuhao'] >= 51 && $v['xuhao'] <= 56){//灯 cate=17
                                        $brands = _getBrandsByCate(17);
                                        $info[$key]['child'][$k]['cate'] = 17;
                                    }else if($v['xuhao'] == 57){//开关插座 cate=181
                                        $brands = _getBrandsByCate(181);
                                        $info[$key]['child'][$k]['cate'] = 181;
                                    }else if($v['xuhao'] >= 58 && $v['xuhao'] <= 59){//窗帘 cate=514
                                        $brands = _getBrandsByCate(514);
                                        $info[$key]['child'][$k]['cate'] = 514;
                                    }else if($v['xuhao'] == 60){//窗帘附件 cate=522
                                        $brands = _getBrandsByCate(522);
                                        $info[$key]['child'][$k]['cate'] = 522;
                                    }else if($v['xuhao'] >= 61 && $v['xuhao'] <= 62){//吸油烟机 cate=237
                                        $brands = _getBrandsByCate(237);
                                        $info[$key]['child'][$k]['cate'] = 237;
                                    }else if($v['xuhao'] >= 63 && $v['xuhao'] <= 64){//橱柜吊柜 cate=219
                                        $brands = _getBrandsByCate(219);
                                        $info[$key]['child'][$k]['cate'] = 219;
                                    }else if(($v['xuhao'] >= 65 && $v['xuhao'] <= 66) || $v['xuhao'] == 78){//洗菜盆 cate=222
                                        $brands = _getBrandsByCate(222);
                                        $info[$key]['child'][$k]['cate'] = 222;
                                    }else if($v['xuhao'] == 67){//燃气热水器 cate=221
                                        $brands = _getBrandsByCate(221);
                                        $info[$key]['child'][$k]['cate'] = 221;
                                    }else if($v['xuhao'] >= 68 && $v['xuhao'] <= 69){// cate=225
                                        $brands = _getBrandsByCate(225);
                                        $info[$key]['child'][$k]['cate'] = 225;
                                    }else if($v['xuhao'] == 70 || $v['xuhao'] == 71 || $v['xuhao'] == 73 || $v['xuhao'] == 79 || ($v['xuhao'] >= 82 && $v['xuhao'] <= 86)){// cate=226
                                        $brands = _getBrandsByCate(226);
                                        $info[$key]['child'][$k]['cate'] = 226;
                                    }else if($v['xuhao'] == 72){// cate=226
                                        $brands = _getBrandsByCate(223);
                                        $info[$key]['child'][$k]['cate'] = 223;
                                    }else if($v['xuhao'] == 74){// cate=226
                                        $brands = _getBrandsByCate(224);
                                        $info[$key]['child'][$k]['cate'] = 224;
                                    }else if($v['xuhao'] == 75 || $v['xuhao'] == 76){// cate=266
                                        $brands = _getBrandsByCate(266);
                                        $info[$key]['child'][$k]['cate'] = 266;
                                    }else if($v['xuhao'] == 77){// cate=266
                                        $brands = _getBrandsByCate(321);
                                        $info[$key]['child'][$k]['cate'] = 321;
                                    }else if($v['xuhao'] == 80 || $v['xuhao'] == 81){// cate=266
                                        $brands = _getBrandsByCate(288);
                                        $info[$key]['child'][$k]['cate'] = 288;
                                    }else if($v['xuhao'] >= 87 && $v['xuhao'] <= 89){// cate=266
                                        $brands = _getBrandsByCate(310);
                                        $info[$key]['child'][$k]['cate'] = 310;
                                    }else if($v['xuhao'] == 90){// cate=266
                                        $brands = _getBrandsByCate(317);
                                        $info[$key]['child'][$k]['cate'] = 317;
                                    }else if($v['xuhao'] == 91){// cate=266
                                        $brands = _getBrandsByCate(318);
                                        $info[$key]['child'][$k]['cate'] = 318;
                                    }else if($v['xuhao'] == 92){// cate=266
                                        $brands = _getBrandsByCate(382);
                                        $info[$key]['child'][$k]['cate'] = 382;
                                    }else if($v['xuhao'] == 93){// cate=266
                                        $brands = _getBrandsByCate(384);
                                        $info[$key]['child'][$k]['cate'] = 384;
                                    }else if($v['xuhao'] == 94){// cate=266
                                        $brands = _getBrandsByCate(383);
                                        $info[$key]['child'][$k]['cate'] = 383;
                                    }else if($v['xuhao'] == 95){// cate=266
                                        $brands = _getBrandsByCate(381);
                                        $info[$key]['child'][$k]['cate'] = 381;
                                    }else if($v['xuhao'] == 96 || $v['xuhao'] == 97){// cate=266
                                        $brands = _getBrandsByCate(385);
                                        $info[$key]['child'][$k]['cate'] = 385;
                                    }else if($v['xuhao'] == 98 || $v['xuhao'] == 99 || $v['xuhao'] == 100 || $v['xuhao'] == 102){// cate=266
                                        $brands = _getBrandsByCate(4);
                                        $info[$key]['child'][$k]['cate'] = 4;
                                    }else if($v['xuhao'] == 101){// cate=266
                                        $brands = _getBrandsByCate(473);
                                        $info[$key]['child'][$k]['cate'] = 473;
                                    }else if($v['xuhao'] == 103 || $v['xuhao'] == 104 || $v['xuhao'] == 105){// cate=266
                                        $brands = _getBrandsByCate(559);
                                        $info[$key]['child'][$k]['cate'] = 559;
                                    }else if($v['xuhao'] == 106){// cate=266
                                        $brands = _getBrandsByCate(560);
                                        $info[$key]['child'][$k]['cate'] = 560;
                                    }else if($v['xuhao'] == 107 || $v['xuhao'] == 108 || $v['xuhao'] == 109){// cate=266
                                        $brands = _getBrandsByCate(561);
                                        $info[$key]['child'][$k]['cate'] = 561;
                                    }else if($v['xuhao'] >= 110 && $v['xuhao'] <= 118){// cate=266
                                        $brands = _getBrandsByCate(580);
                                        $info[$key]['child'][$k]['cate'] = 580;
                                    }
                                    $data_brands = [];
                                    if($brands){
                                        foreach($brands as $ks=>$vs){
                                            $where = array();
                                            $where[] = ['exp','FIND_IN_SET('.$insert_info['style_id'].',A.style)'];
                                            $where[] = ['exp','FIND_IN_SET('.$vs['cate_id'].',A.cate_id)'];
                                            $where['A.brand_id'] = $vs['id'];
                                            $where['A.status']=['eq',0];
                                            $where['A.online']=['eq',1];
                                            $price_top = $v['dj'] * 1.16;
                                            $price_down = $v['dj'] * 0.84;
                                            $where1['C.shop_price'] = ['>=',$price_down];
                                            $where2['C.shop_price'] = ['<=',$price_top];
                                            $data = Db::name('shop_goods')->alias('A')
                                                ->join('shop_goods_attr B','B.goods_id = A.id','INNER')
                                                ->join('shop_goods_attr C','C.id = B.pid','INNER')
                                                ->where($where)->where($where1)->where($where2)
                                                ->field("A.id,A.name AS goods_name,B.name AS attr_name,C.shop_price,C.name AS cate_name")->order('A.sort ASC')->select();
                                                /* if($v['xuhao'] == 50 && $vs['id'] == 188){
                                                    echo Db::name('shop_goods')->getLastSql();die;
                                                } */
                                            if($data){
                                                foreach($data as $kk=>$vv){
                                                    $data[$kk]['brands_name'] = $vs['name'];
                                                }
                                                $data_brands = $data;
                                            }
                                        }
                                    }
                                    $info[$key]['child'][$k]['tj_brands'] = $info[$key]['child'][$k]['brands'] = $data_brands;
                                }
                            }
                        }else{//基础装修部分
                            $info[$key]['sj'] = $vo['je'];
                            if($vo['child']){
                                foreach($vo['child'] as $k=>$v){
                                    $info[$key]['child'][$k]['checked'] = $info[$key]['checked'];
                                    $info[$key]['child'][$k]['sj_price'] = $v['dj'];
                                    $info[$key]['child'][$k]['sj_hj'] = $v['hj'];
                                    $info[$key]['child'][$k]['price'] = $v['dj'];
                                }
                            }
                        }
                    }
                    $insert_info['data_info'] = json_encode($info);
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
            $data = Db::name('budget_info')->alias('A')
                ->join('budget B','B.id = A.budget_id','INNER')
                ->where(['A.budget_id' => $post['id'],'A.uid'=>$post['uid']])
                ->field('A.*,B.style')->find();
            if($data){
                $info = json_decode($data['data_info'],true);
                $this->ret['code'] = 200;
                $this->ret['data'] = [
                    'data'=>$info,
                    'heji'=>$data['data_sum'],
                    'sj_sum'=>$data['sj_sum'],
                    'style_id'=>$data['style_id'],
                ];
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 更新智能预算推荐商品信息
     */
    public function updateInfoById(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['budget_id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $data = Db::name('budget_info')->where(['budget_id' => $post['budget_id']])->find();
            if($data){
                $info = json_decode($data['data_info'],true);
                $where = [
                    'A.id' => $post['goods_attr_id'],
                    'A.online' => 1,
                    'A.status' => 0,
                ];
                $goods = Db::name('shop_goods_attr')->alias('A')
                    ->join('shop_goods B','B.id = A.goods_id','INNER')
                    ->join('shop_goods_attr D','D.id = A.pid','INNER')
                    ->join('brands C','C.id = B.brand_id','LEFT')
                    ->where($where)
                    ->field('A.shop_price,A.name AS attr_name,B.name AS goods_name,C.name AS brands_name,D.name AS cate_name')->select();
                $child = $post['child'];
                $key = $post['key'];
                if($goods){
                    $info[$key]['child'][$child]['sj_brands'] = $goods;
                    $info[$key]['child'][$child]['sj_price'] = $goods[0]['shop_price'];
                    $info[$key]['child'][$child]['sj_hj'] = $post['num'] * $goods[0]['shop_price'];
                    $sj_sum = 0;
                    foreach($info as $key=>$vo){
                        $sj_he = 0;
                        foreach($vo['child'] as $k=>$v){
                            $sj_sum += $v['sj_hj'];
                            $sj_he += $v['sj_hj'];
                        }
                        $info[$key]['sj'] = $sj_he;
                    }
                    $info = json_encode($info);
                    $ids = Db::name('budget_info')->where('budget_id',$post['budget_id'])->update(['data_info'=>$info,'sj_sum'=>$sj_sum]);
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = '操作成功';
                }
            }else{
                $this->ret['msg'] = '操作失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
