<?php
namespace app\admin\controller;

use \think\Db;
use app\admin\model\Notice as NoticeModel;
use think\Session;

class notice extends Main
{
    protected $ret = ['code'=>0,'msg'=>"",'count'=>0,'data'=>[]];
    /**
     * 公告管理管理-公告列表页面
     */
    public function index(){
        return $this->fetch();
    } /**
    * 公告管理管理-公告列表页面接口数据
    */
    public function noticeList(){
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        $keywords = $this->request->param('keywords','');
        $page_start = ($page - 1) * $limit;
        $where = [];
        if($keywords){
            $where['title'] = ['like',"%$keywords%"];
        }
        $user = Session::get('user');
        $data = Db::name('notice')->alias('a')
            ->join('region b','a.province = b.region_code','LEFT')
            ->join('region c','a.city = c.region_code','LEFT')
            ->join('region d','a.county = d.region_code','LEFT')
            ->where($where)
            ->where('province',$user['province'])
            ->where('city',$user['city'])
            ->where('county',$user['county'])
            ->field('a.*,b.region_name as province_name,c.region_name as city_name,d.region_name as country_name')
            ->order('a.create_time DESC')
            ->limit($page_start,$limit)
            ->select();
        $count = Db::name('notice')
            ->where($where)
            ->where('province',$user['province'])
            ->where('city',$user['city'])
            ->where('county',$user['county'])
            ->count();
        foreach ($data as $k => $v) {
            if($v['county'] == 0 && $v['city'] == 0){
                $data[$k]['name'] = '总站';
            }else{
                $data[$k]['name'] = $v['province_name'].'-'.$v['city_name'].'-'.$v['country_name'];
            }
        }
        if($data){
            $this->ret['count'] = $count;
            $this->ret['data'] = $data;
        }
        return json($this->ret);
    }
    /**
     * 公告管理-公告添加页面
     */
    public function noticeAdd(){
    	return  $this->fetch('add');
    }


    /**
     * 公告管理-公告添加处理
     */
    public function addNotice(){
    	$post = $this->request->post();
    	$validate = validate('notice');
    	$res = $validate->check($post);
		if($res!==true){
			$this->error($validate->getError());
		}else{
            $user = Session::get('user');
            $post['province'] = $user['province'];
            $post['city'] = $user['city'];
            $post['county'] =  $user['county'];
            $post['create_time'] =  date('Y-m-d H:i:s');
			$res = Db::name('notice')->insert($post);
			if($res){
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
		}
    }


    /**
     * 公告管理-公告编辑页面
     */
    public function noticeEdit(){
        $id  = $this->request->get('id');
        $data = Db::name('notice')
        ->where('id',$id)
        ->find();
        return  $this->fetch('edit',['data'=>$data]);
    }


    /**
     * 公告管理-公告编辑处理
     */
    public function editNotice(){
        $post =  $this->request->post();
        $id = $post['id'];
        $validate = validate('notice');
        $res = $validate->check($post);
        if($res!==true){
            $this->error($validate->getError());
        }else{
            $NoticeModel = new NoticeModel;
            // save方法第二个参数为更新条件
            $res = $NoticeModel->save($post,['id',$id]);
            if($res){
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        }
    }

    /**
     * 公告管理-公告删除处理
     */
    public function deleteNotice(){
        $id = $this->request->post('id');
        $res = Db::name('notice')
        ->where('id',$id)
        ->delete();
        // var_dump($res);die;
        // var_dump(Db::name('notice')->getLastSql());die;
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }

    /**
     * 公告管理-公告删除处理(批量)
     */
    public function delAll(){
        // var_dump($method);die;
        $delList = $this->request->post('delList');
        $delList = json_decode($delList,true);
        foreach ($delList as $k => $v) {
            $res = Db::name('notice')->delete($v);
        }
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }

    
    /**
     * 公告管理-公告更新状态
     */
    public function updateStatus(){
        $id = $this->request->param('id');
        $notice = Db::name('notice')->where('id',$id)->find();
        $data = [];
        if($notice){
            $data = [
                'status'        =>  $notice['status']==0?1:0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('notice')->where('id',$id)->update($data);
        if($result){
            $this->ret['code'] = 200;
        }
        $this->ret['data'] = $data['status'];
        return json($this->ret);
    }

    /**
     * 公告管理-公告查询
     */
    public function search(){
        return $this->fetch();
    }
}
