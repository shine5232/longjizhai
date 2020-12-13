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
                'style'  =>  isset($post['style'])?$post['style']:'',
                'home_id'  =>  $post['home'],
                'price_id'  =>  isset($post['price'])?$post['price']:'',
                'create_time'   =>  date('Y-m-d H:i:s'),
            ];
            $cases_id = Db::name('cases')->insertGetId($insert);
            if($cases_id){
                foreach($post['fileLists'] as $key=>$v){
                    if($post['type'] == 3){//设计师，有位置
                        if($v){
                            foreach($v as $k=>$s){
                                $has = Db::name('cases')->where(['id'=>$cases_id])->value('thumb');
                                if(!$has){
                                    Db::name('cases')->where(['id'=>$cases_id])->update(['thumb'=>$s['img']]);
                                }
                                $img = [
                                    'case_id' => $cases_id,
                                    'position_id' => $s['id'],
                                    'img'   =>  $s['img'],
                                    'create_time'   =>  date('Y-m-d H:i:s')
                                ];
                                Db::name('case_img')->insert($img);
                            }
                        }
                    }else{//技工、工长没有位置
                        if($v){
                            foreach($v as $k=>$s){
                                $has = Db::name('cases')->where(['id'=>$cases_id])->value('thumb');
                                if(!$has){
                                    Db::name('cases')->where(['id'=>$cases_id])->update(['thumb'=>$s['img']]);
                                }
                                $img = [
                                    'case_id' => $cases_id,
                                    'img'   =>  $s['img'],
                                    'create_time'   =>  date('Y-m-d H:i:s')
                                ];
                                Db::name('case_img')->insert($img);
                            }
                        }
                    }
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
    /**
     * 用户收藏案例
     */
    public function collectCases(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['case_id']) || !isset($post['uid'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $insert = [
                'uid' => $post['uid'],
                'collected_id' => $post['case_id'],
                'type'  =>  1,
            ];
            $collect = Db::name('collect')->where($insert)->find();
            if($collect){
                $upd['status'] = $collect['status']?0:1;
                $upd['update_time'] = date('Y-m-d H:i:s');
                $data = Db::name('collect')->where($insert)->update($upd);
                if($upd['status']){//减少1
                    Db::name('cases')->where('id',$post['case_id'])->setDec('collect_num',1);
                }else{//增加1
                    Db::name('cases')->where('id',$post['case_id'])->setInc('collect_num',1);
                }
            }else{
                $insert['create_time'] = date('Y-m-d H:i:s');
                $data = Db::name('collect')->insert($insert);
                Db::name('cases')->where('id',$post['case_id'])->setInc('collect_num',1);
            }
            if($data){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '操作成功';
            }else{
                $this->ret['msg'] = '操作失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 手机端获取推荐案例数据
     */
    public function getRecommendCasesLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['county']) || !isset($post['style'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            if($post['county']){
                $where['A.county'] = $post['county'];
            }else{
                $where['A.is_zong'] = 1;
            }
            $where['A.status'] = 1;
            $where['A.recommend_id'] = 3;
            $end_time = date('Y-m-d H:i:s');
            $where['B.checked'] = 1;
            $where['B.deleted'] = 0;
            $where['B.style'] = $post['style'];
            $data = Db::name('recommend_data')->alias('A')
                ->join('cases B','B.id = A.object_id','INNER')
                ->where($where)
                ->where("A.end_time >= '".$end_time."' OR A.end_time = ''")
                ->field('A.object_id AS id,A.img,A.id AS recom_id')
                ->order('A.sort DESC')
                ->select();
            if($data){
                foreach($data as &$v){
                    $v['img'] = _getServerName().$v['img'];
                }
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
     * 获取案例列表
     */
    public function getCasesLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['county']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            if($post['county']>0){
                $where['C.county'] = $post['county'];
            }else{
                $where['C.is_zong'] = 1;
            }
            if(isset($post['home']) && $post['home']){//户型
                $where['C.home_id'] = $post['home'];
            }
            if(isset($post['style']) && $post['style']){//风格
                $where['C.style'] = $post['style'];
            }
            if(isset($post['price']) && $post['price']){//价格
                $where['C.price_id'] = $post['price'];
            }
            $where['C.type']=['eq',3];
            $where['C.deleted']=['eq',0];
            $where['C.checked']=['eq',1];
            $data = Db::name('cases')->alias('C')
                ->join('cases_attr B','B.id = C.home_id','INNER')
                ->join('cases_attr D','D.id = C.price_id','INNER')
                ->where($where)->field("C.id,C.case_title,C.style,C.style AS style_name,B.title AS home_name,D.title AS price")->order('C.sort ASC,C.id ASC')->limit($page_start, $limit)->select();
            $datas = [];
            if($data){
                $wheres = [];
                if(isset($post['position']) && $post['position']){//位置
                    $wheres['A.position_id'] = $post['position'];
                }
                foreach($data as $key=>$vo){
                    $img = Db::name('case_img')->alias('A')
                        ->join('cases_attr B','B.id = A.position_id','INNER')
                        ->where(['A.case_id'=>$vo['id']])
                        ->where($wheres)
                        ->field('A.id,A.img,A.position_id,B.title AS position_name')->order('A.id ASC')->group('A.position_id')->select();
                    if($img){
                        if($vo['style']==1){
                            $style_name = '中式风格';
                        }else if($vo['style']==2){
                            $style_name = '欧式风格';
                        }else if($vo['style']==3){
                            $style_name = '现代风格';
                        }else if($vo['style']==4){
                            $style_name = '田园风格';
                        }else if($vo['style']==5){
                            $style_name = '地中海风格';
                        }else if($vo['style']==6){
                            $style_name = '东南亚风格';
                        }else if($vo['style']==7){
                            $style_name = '混搭风格';
                        }
                        foreach($img as $k=>$v){
                            $datas[] = [
                                'id' => $v['id'],
                                'case_id' => $vo['id'],
                                'case_title' => $vo['case_title'],
                                'style_name' => $style_name,
                                'home_name' => $vo['home_name'],
                                'price' => $vo['price'],
                                'thumb' => _getServerName().$v['img'],
                                'position_name' => $v['position_name'],
                                'position_id' => $v['position_id'],
                            ];
                        }
                    }
                }
                if($datas){
                    $this->ret['code'] = 200;
                    $this->ret['data'] = $datas;
                }else{
                    $this->ret['msg'] = '获取失败';
                }
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 根据案例id获取案例详情图片
     */
    public function getCasesImgLis(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['case_id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where['case_id']=$post['case_id'];
            if(isset($post['position_id'])){
                $where['position_id']=$post['position_id'];
            }
            $data = Db::name('case_img')->where($where)->field("img")->select();
            if($data){
                foreach($data as &$v){
                    $datas[] = _getServerName().$v['img'];
                }
                $this->ret['code'] = 200;
                $this->ret['data'] = $datas;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 获取用户案例列表
     */
    public function getCasesLisByUid(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['page']) || !isset($post['size'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            $where['A.user_id']=['eq',$post['uid']];
            $where['A.deleted']=['eq',0];
            $where['A.checked']=['eq',1];
            $data = Db::name('cases')->alias('A')
                ->join('cases_attr B','B.id = A.home_id','LEFT')
                ->where($where)->field("A.id,A.case_title,A.thumb,A.style,A.style AS style_name,B.title AS home_name,A.create_time")->order('A.id DESC,A.sort DESC')->limit($page_start, $limit)->select();
            if($data){
                foreach($data as &$v){
                    if($v['thumb']){
                        $v['thumb'] = _getServerName().$v['thumb'];
                    }
                    if($v['style']==1){
                        $v['style_name'] = '中式风格';
                    }else if($v['style']==2){
                        $v['style_name'] = '欧式风格';
                    }else if($v['style']==3){
                        $v['style_name'] = '现代风格';
                    }else if($v['style']==4){
                        $v['style_name'] = '田园风格';
                    }else if($v['style']==5){
                        $v['style_name'] = '地中海风格';
                    }else if($v['style']==6){
                        $v['style_name'] = '东南亚风格';
                    }else if($v['style']==7){
                        $v['style_name'] = '混搭风格';
                    }
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
     * 根据案例获取案例详情
     */
    public function getCasesInfoById(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $data['cases'] = Db::name('cases')->alias('A')
                ->join('village B','B.id = A.area_id','INNER')
                ->join('cases_attr D','D.id = A.home_id','LEFT')
                ->join('cases_attr F','F.id = A.price_id','LEFT')
                ->where(['A.id'=>$post['id']])
                ->field('A.*,B.village_name,D.title AS home,F.title AS price')->find();
            if($data['cases']){
                $cases_id = $data['cases']['id'];
                if($data['cases']['type'] == 3){
                    $position = Db::name('case_img')->alias('A')
                        ->join('cases_attr B','B.id = A.position_id','LEFT')->where(['case_id'=>$cases_id])->field('A.position_id,B.title AS position')->group('position_id')->select();
                    if($position){
                        foreach($position as $key=>$v){
                            $position[$key]['img'] = Db::name('case_img')->where(['case_id'=>$cases_id,'position_id'=>$v['position_id']])->field('img')->select();
                        }
                    }
                    $data['cases_img'] = $position;
                }else{
                    $data['cases_img'] = Db::name('case_img')->where(['case_id'=>$cases_id])
                        ->order(['sort' => 'DESC', 'id' => 'ASC'])->field('id,img,position_id')->select();
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
}
