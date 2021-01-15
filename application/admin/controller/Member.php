<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;
use app\admin\model\MemberRank as MemberRankModel;

class Member extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 会员管理-会员列表页
     */
    public function index()
    {
        $user = session('user');
        if (request()->isAjax()) {
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $realname = $this->request->param('realname', '');
            $mobile = $this->request->param('mobile', '');
            $uname = $this->request->param('uname', '');
            $province = $this->request->param('province', '');
            $city = $this->request->param('city', '');
            $county = $this->request->param('county', '');
            $type = $this->request->param('type', '');
            $uid = $this->request->param('uid', '');
            $subscribe = $this->request->param('subscribe', '');
            $status = $this->request->param('status', '');
            $checked = $this->request->param('checked', '');
            $page_start = ($page - 1) * $limit;
            $where = 'A.deleted = 0';
            if ($realname) {
                $where .= " AND A.realname = '$realname'";
            }
            if ($mobile) {
                $where .= " AND A.mobile = '$mobile'";
            }
            if ($uname) {
                $where .= " AND A.uname = '$uname'";
            }
            if ($province) {
                $where .= " AND A.province = $province";
            }
            if ($city) {
                $where .= " AND A.city = $city";
            }
            if ($county) {
                $where .= " AND A.county = $county";
            }
            if ($uid) {
                $where .= " AND A.uid = $uid";
            }
            if ($subscribe != '') {
                $where .= " AND A.subscribe = $subscribe";
            }
            if ($status != '') {
                $where .= " AND A.status = $status";
            }
            if ($checked != '') {
                $where .= " AND A.checked = $checked";
            }
            if ($type != '') {
                $where .= " AND A.type = $type";
            }
            if($user['county']){
                $where .= " AND A.county = ".$user['county'];
            }
            $sql1 = "SELECT A.*,B.rank_name,C.uname AS topname,A.province AS province_name,A.city AS city_name,A.county AS county_name FROM lg_member A
                    LEFT JOIN lg_member_rank B ON A.rank_id = B.id
                    LEFT JOIN lg_member C ON A.superior_id = C.uid
                    WHERE $where
                    ORDER BY A.id DESC 
                    limit $page_start, $limit";
            $sql2 = "SELECT COUNT(0) AS num FROM lg_member AS A WHERE $where";
            $data = Db::query($sql1);
            $count = Db::query($sql2);
            if ($data) {
                foreach($data as &$v){
                    $v['province_name'] = Db::name('region')->where('region_code',$v['province'])->value('region_name');
                    $v['city_name'] = Db::name('region')->where('region_code',$v['city'])->value('region_name');
                    $v['county_name'] = Db::name('region')->where('region_code',$v['county'])->value('region_name');
                }
                $this->ret['count'] = $count[0]['num'];
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        } else {
            return $this->fetch();
        }
    }
    /**
     * 会员管理-添加会员页面
     */
    public function articleAdd()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $admin_user = session('user');
            if ($admin_user['county'] != null) {
                $post['county'] = $admin_user['county'];
            }
            $post['create_time']   = date('Y-m-d H:i:s');
            $db = Db::name('article')->insert($post);
            if ($db) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        } else {
            $cate = Db::name('article_cate')->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
            $cate = array2Level($cate);
            $this->assign('cate', $cate);
            return $this->fetch('article_add');
        }
    }
    /**
     * 会员管理-编辑会员页面
     */
    public function edit()
    {
        if (request()->isPost()) {
            $post     = $this->request->post();
            $id = $post['id'];
            $post['update_time']  = date('Y-m-d H:i:s');
            $uname = $post['uname'];
            unset($post['id'],$post['uname']);
            $type = $post['type'];
            $has = false;
            $typer = 'member';
            if($type == '1'){
                $typer = 'mechanic';
                $has = Db::name('mechanic')->where('uid',$id)->find();
            }elseif($type == '2'){
                $typer = 'gz';
                $has = Db::name('gongzhang')->where('uid',$id)->find();
            }elseif($type == '3'){
                $typer = 'designer';
                $has = Db::name('designer')->where('uid',$id)->find();
            }elseif($type == '4'){
                $typer = 'company';
                $has = Db::name('company')->where('uid',$id)->find();
            }elseif($type == '5'){
                $typer = 'shop';
                $has = Db::name('shop')->where('uid',$id)->find();
            }elseif($type == '6'){
                $typer = 'proprietor';
            }
            if(!$has){
                $mechanic = [
                    'uid'       =>  $id,
                    'province'  =>  $post['province'],
                    'city'      =>  $post['city'],
                    'county'    =>  $post['county'],
                    'name'      =>  $post['realname'],
                    'mobile'    =>  $post['mobile'],
                    'create_time'=> date('Y-m-d H:i:s'),
                ];
                if($type == '1'){
                    Db::name('mechanic')->insert($mechanic);
                }elseif($type == '2'){
                    Db::name('gongzhang')->insert($mechanic);
                }elseif($type == '3'){
                    Db::name('designer')->insert($mechanic);
                }elseif($type == '4'){
                    Db::name('company')->insert($mechanic);
                }elseif($type == '5'){
                    unset($mechanic['name']);
                    $mechanic['user']=$post['realname'];
                    Db::name('shop')->insert($mechanic);
                }
            }
            $db = Db::name('member')->where('id', $id)->update($post);
            if ($db) {
                $url = "https://yhgg.longjizhai.com/ashx/member/account/update.ashx";
                $city = _getRegionNameByCode($post['city']);
                $county = _getRegionNameByCode($post['county']);
                if($city && $county){
                    $area = $city['region_name'].$county['region_name'];
                }else{
                    $area = '总站';
                }
                $params = [
                    'u'=>$uname,
                    't'=>$typer,
                    'r'=>$post['realname'],
                    'd'=>$area,
                    'c'=>$post['mobile'],
                ];
                $res = json_decode(_requestPost($url,$params),true);
                $this->ret['code'] = 200;
                $this->ret['msg'] = 'success';
            }
            return json($this->ret);
        } else {
            $id  = $this->request->get('id');
            $member = Db::name('member')->where('id', $id)->find();
            $province = _getRegion();
            $city = _getRegion($member['province']);
            $county = _getRegion($member['city'],false,true);
            $this->assign('province', $province);
            $this->assign('city', $city);
            $this->assign('county', $county);
            $this->assign('member', $member);
            return $this->fetch('edit');
        }
    }
    /**
     * 会员管理-会员搜索页面
     */
    public function search()
    {
        $user = session('user');
        $province = _getRegion(); //获取省份数据
        $this->assign('province', $province);
        $this->assign('user', $user);
        return $this->fetch();
    }
    /**
     * 会员管理-会员删除
     */
    public function deleteUser()
    {
        $id = $this->request->post('id');
        $upd = [
            'deleted'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        $res = Db::name('member')->where('id', $id)->update($upd);
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 会员管理-会员积分日志
     */
    public function point(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $uid = $this->request->param('uid');
            $page_start = ($page - 1) * $limit;
            $data = Db::name('point_log')->where('uid',$uid)->order('id DESC')->limit($page_start, $limit)->select();
            $count = Db::name('point_log')->where('uid',$uid)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            $id  = $this->request->get('id');
            $this->assign('uid',$id);
            return $this->fetch('point');
        }
    }
    /**
     * 会员管理-会员充值
     */
    public function recharge()
    {
        if (request()->isPost()) {
            $post = $this->request->post();
            $upd = [
                'point' => (int)$post['yue'] + (int)$post['point']
            ];
            $insert = [
                'uid' => $post['id'],
                'point' => (int)$post['point'],
                'point_from' => 2,
                'remark' => $post['remark'],
                'create_time' => date('Y-m-d H:i:s')
            ];
            Db::startTrans();
            try {
                $res1 = Db::name('member')->where('id', $post['id'])->update($upd);
                $res2 = Db::name('point_log')->insert($insert);
                if($res1 && $res2){
                    Db::commit();
                    $this->ret['code'] = 200;
                    $this->ret['msg'] = 'success';
                }
            } catch (\Exception $e) {
                Db::rollback();
            }
            return json($this->ret);
        } else {
            $id  = $this->request->get('id');
            $member = Db::name('member')->where('id', $id)->find();
            $this->assign('member', $member);
            return $this->fetch('recharge');
        }
    }
}
