<?php
namespace app\api\controller;

use think\Db;

class Appointment extends Main
{
    /**
     * 预约技工、工长、设计师、装饰公司
     */
    public function appointmentByUid(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['name']) || !isset($post['times']) || !isset($post['mobile']) || !isset($post['type'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            if($post['type'] == '1'){//技工
                $types = '[技工]';
                $data = Db::name('mechanic')->where('id',$post['apid'])->field('uid,name')->find();
            }else if($post['type'] == '2'){//工长
                $types = '[工长]';
                $data = Db::name('gongzhang')->where('id',$post['apid'])->field('uid,name')->find();
            }else if($post['type'] == '3'){//设计师
                $types = '[设计师]';
                $data = Db::name('designer')->where('id',$post['apid'])->field('uid,name')->find();
            }else{//装饰公司
                $types = '[装饰公司]';
                $data = Db::name('company')->where('id',$post['apid'])->field('uid,name')->find();
            }
            $insert = [
                'name'  =>  $post['name'],
                'uid'   =>  $post['uid'],
                'appointmented_uid'   =>  $data['uid'],
                'appoint_time'  =>  $post['times'],
                'type'  => $post['type'],
                'mobile'    =>  $post['mobile'],
                'content'   =>  $post['content'],
                'province'  =>  $post['province'],
                'city'      =>  $post['city'],
                'county'    =>  $post['county'],
                'create_time'   => date('Y-m-d H:i:s')
            ];
            $datas = Db::name('appointment')->insert($insert);
            if($datas){
                _updatePoint($post['uid'],2,0);
                $msg = '预约：'.$types.'['.$data['name'].']'.'扣除积分';
                _saveUserPoint($post['uid'],2,0,'8',$msg);
                $this->ret['code'] = 200;
                $this->ret['msg'] = '已提交预约申请';
            }else{
                $this->ret['msg'] = '申请提交失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 我的预约
     */
    public function myAppointment(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['status']) || !isset($post['type'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $page = $post['page']>0?$post['page']:1;
            $limit = $post['size']>0?$post['size']:10;
            $page_start = ($page - 1) * $limit;
            if($post['type'] == '0' || $post['type'] == '6'){//会员查看
                $where = [
                    'A.uid'=>$post['uid'],
                    'A.status'=>$post['status']
                ];
                $data = Db::name('appointment')->alias('A')
                    ->join('member B','B.id = A.appointmented_uid','LEFT')
                    ->join('member_rank C','C.id = B.rank_id','LEFT')
                    ->join('member_weixin D','D.openid = B.openid','LEFT')
                    ->where($where)->field('A.id,A.status,B.realname AS name,B.type,B.id AS user,C.rank_name AS level,D.avatar AS img')
                    ->order('A.id DESC')->limit($page_start, $limit)->select();
                $count = Db::name('appointment')->alias('A')->where($where)->count();
                if($data){
                    foreach($data as $key=>$v){
                        if($v['type']=='1'){
                            $data[$key]['cases'] = Db::name('mechanic')->where('uid',$v['user'])->value('case_num');
                            $data[$key]['typer'] = 'mechanic';
                            $data[$key]['type'] = '技工';
                        }else if($v['type']=='2'){
                            $data[$key]['cases'] = Db::name('gongzhang')->where('uid',$v['user'])->value('case_num');
                            $data[$key]['typer'] = 'gongzhang';
                            $data[$key]['type'] = '工长';
                        }else if($v['type']=='3'){
                            $data[$key]['cases'] = Db::name('designer')->where('uid',$v['user'])->value('case_num');
                            $data[$key]['typer'] = 'designer';
                            $data[$key]['type'] = '设计师';
                        }else if($v['type']=='4'){
                            $data[$key]['cases'] = Db::name('company')->where('uid',$v['user'])->value('case_num');
                            $data[$key]['typer'] = 'company';
                            $data[$key]['type'] = '装饰公司';
                        }
                    }
                }
            }else{//技工、工长、设计师查看
                $where = [
                    'A.appointmented_uid'=>$post['uid'],
                    'A.status'=>$post['status']
                ];
                $data = Db::name('appointment')->alias('A')
                    ->where($where)->field('A.id,A.name,A.mobile,A.appoint_time AS time,A.content AS note,A.note AS replay')
                    ->order('A.id DESC')->limit($page_start, $limit)->select();
                $count = Db::name('appointment')->alias('A')->where($where)->count();
            }
            if($data){
                $this->ret['code'] = 200;
                $this->ret['data'] = $data;
                $this->ret['count'] = $count;
            }else{
                $this->ret['msg'] = '获取失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 查看预约详情
     */
    public function myAppointmentInfo(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where = [
                'A.id'=>$post['id'],
            ];
            $data['info'] = Db::name('appointment')->alias('A')
                ->join('member B','B.id = A.appointmented_uid','LEFT')
                ->where($where)->field('A.id,A.appointmented_uid,A.uid,A.name,A.mobile,A.status,A.appoint_time AS time,A.content AS note,A.note AS replay,B.type')->find();
            if($data['info']){
                $has = Db::name('comment_user')->where(['uid'=>$data['info']['appointmented_uid'],'comment_uid'=>$data['info']['uid']])->find();
                if($data['info']['type'] == '1'){
                    $data['art'] = Db::name('mechanic')->alias('A')
                        ->join('member B','B.id = A.uid','LEFT')
                        ->join('member_rank C','C.id = B.rank_id','LEFT')
                        ->join('member_weixin D','D.openid = B.openid','LEFT')
                        ->where('A.uid',$data['info']['appointmented_uid'])->field('B.id,A.name,A.case_num as cases,C.rank_name AS level,D.avatar AS img')->find();
                    if($data['art']){
                        $data['art']['type'] = '技工';
                        $data['art']['typer'] = 'mechanic';
                    }
                }elseif($data['info']['type'] == '2'){
                    $data['art'] = Db::name('gongzhang')->alias('A')
                        ->join('member B','B.id = A.uid','LEFT')
                        ->join('member_rank C','C.id = B.rank_id','LEFT')
                        ->join('member_weixin D','D.openid = B.openid','LEFT')
                        ->where('A.uid',$data['info']['appointmented_uid'])->field('B.id,A.name,A.case_num as cases,C.rank_name AS level,D.avatar AS img')->find();
                    if($data['art']){
                        $data['art']['type'] = '工长';
                        $data['art']['typer'] = 'gongzhang';
                    }
                }elseif($data['info']['type'] == '3'){
                    $data['art'] = Db::name('designer')->alias('A')
                        ->join('member B','B.id = A.uid','LEFT')
                        ->join('member_rank C','C.id = B.rank_id','LEFT')
                        ->join('member_weixin D','D.openid = B.openid','LEFT')
                        ->where('A.uid',$data['info']['appointmented_uid'])->field('B.id,A.name,A.case_num as cases,C.rank_name AS level,D.avatar AS img')->find();
                    if($data['art']){
                        $data['art']['type'] = '设计师';
                        $data['art']['typer'] = 'designer';
                    }
                }
                if($has){
                    $data['art']['comment'] = 1;
                }else{
                    $data['art']['comment'] = 0;
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
     * 处理预约信息
     */
    public function updateAppointmentStatus(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['id']) || !isset($post['status'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $where = [
                'id'=>$post['id'],
            ];
            $update = [
                'status' => $post['status'],
                'note'  =>  $post['replay'],
                'update_time'=>date('Y-m-d H:i:s'), 
            ];
            $data = Db::name('appointment')->where($where)->update($update);
            if($data){
                //更新预约数量
                /* if($post['status'] == '1'){
                    
                } */
                $this->ret['code'] = 200;
                $this->ret['msg'] = '处理成功';
            }else{
                $this->ret['msg'] = '处理失败';
            }
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
    /**
     * 统计预约处理信息条数
     */
    public function getCountsAppointment(){
        if(request()->isPost()){
            $post = $this->request->post();
            if(!isset($post['uid']) || !isset($post['type'])){
                $this->ret['msg'] = '缺少参数';
                return json($this->ret);
            }
            $data = ['pending'=>0,'ok'=>0,'no'=>0];
            if($post['type'] == '0' || $post['type'] == '6'){//会员查看
                $where_pending = [
                    'uid'=>$post['uid'],
                    'status'=>'0'
                ];
                $where_ok = [
                    'uid'=>$post['uid'],
                    'status'=>'1'
                ];
                $where_no = [
                    'uid'=>$post['uid'],
                    'status'=>'2'
                ];
            }else{//技工、工长、设计师查看
                $where_pending = [
                    'appointmented_uid'=>$post['uid'],
                    'status'=>'0'
                ];
                $where_ok = [
                    'appointmented_uid'=>$post['uid'],
                    'status'=>'1'
                ];
                $where_no = [
                    'appointmented_uid'=>$post['uid'],
                    'status'=>'2'
                ];
            }
            $data['pending'] = Db::name('appointment')->where($where_pending)->count();
            $data['ok'] = Db::name('appointment')->where($where_ok)->count();
            $data['no'] = Db::name('appointment')->where($where_no)->count();
            $this->ret['code'] = 200;
            $this->ret['data'] = $data;
        }else{
            $this->ret['msg'] = '请求方式错误';
        }
        return json($this->ret);
    }
}
