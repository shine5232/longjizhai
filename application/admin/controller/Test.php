<?php

namespace app\admin\controller;

use \think\Db;
use \think\Reuquest;

class Test extends Main
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 测评管理-试卷管理
     */
    public function index(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $data = Db::name('test')->where('deleted',0)->order('id DESC')->limit($page_start, $limit)->select();
            $count = Db::name('test')->where('deleted',0)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch();
        }
    }
    /**
     * 测评管理-试卷添加
     */
    public function testAdd(){
        if(request()->isPost()){
            $post = $this->request->post();
            $status = isset($post['status'])?$post['status']:0;
            $insert = [
                'title'=>$post['title'],
                'status'=>$status,
                'create_time'=>date('Y-m-d H:i:s')
            ];
            $res = Db::name('test')->insert($insert);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            return $this->fetch('test_add');
        }
    }
    /**
     * 测评管理-试卷编辑
     */
    public function testEdit(){
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $status = isset($post['status'])?$post['status']:0;
            $upd = [
                'title'=>$post['title'],
                'status'=>$status,
                'update_time'=>date('Y-m-d H:i:s')
            ];
            $res = Db::name('test')->where('id',$id)->update($upd);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '更新成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $test = Db::name('test')->where('id',$id)->find();
            $this->assign('test',$test);
            return $this->fetch('test_edit');
        }
    }
    /**
     * 测评管理-试卷修改状态
     */
    public function updateStatus(){
        $id = $this->request->param('id');
        $test = Db::name('test')->where('id', $id)->find();
        $data = [];
        if ($test) {
            $data = [
                'status'        =>  $test['status'] == 0 ? 1 : 0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('test')->where('id', $id)->update($data);
        if ($result) {
            $this->ret['code'] = 200;
        }
        return json($this->ret);
    }
    /**
     * 测评管理-试卷删除
     */
    public function deleteTest()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'deleted'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('test')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('test')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = 'success';
        }
        return json($this->ret);
    }
    /**
     * 测评管理-题目列表
     */
    public function option(){
        $test_id = $this->request->get('test_id');
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $where['deleted'] = ['eq','0'];
            $where['test_id'] = ['eq',$test_id];
            $data = Db::name('test_option')->where($where)->order('sort ASC,id ASC')->limit($page_start,$limit)->select();
            $count = Db::name('test_option')->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            $this->assign('test_id',$test_id);
            return $this->fetch('option');
        }
    }
    /**
     * 测评管理-题目添加
     */
    public function optionAdd(){
        if(request()->isPost()){
            $post = $this->request->post();
            $post['answer'] = strtoupper($post['answer']);
            $post['status'] = isset($post['status'])?$post['status']:0;
            $post['create_time'] = date('Y-m-d H:i:s');
            $post['option'] = isset($post['option'])?serialize($post['option']):'';
            $res = Db::name('test_option')->insert($post);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '添加成功';
            }
            return json($this->ret);
        }else{
            $test_id = $this->request->get('test_id');
            $this->assign('test_id',$test_id);
            return $this->fetch('option_add');
        }
    }
    /**
     * 测评管理-题目编辑
     */
    public function optionEdit(){
        if(request()->isPost()){
            $post = $this->request->post();
            $id = $post['id'];
            $post['answer'] = strtoupper($post['answer']);
            $post['status'] = isset($post['status'])?$post['status']:0;
            $post['create_time'] = date('Y-m-d H:i:s');
            $post['option'] = isset($post['option'])?serialize($post['option']):'';
            unset($post['id']);
            $res = Db::name('test_option')->where('id',$id)->update($post);
            if ($res) {
                $this->ret['code'] = 200;
                $this->ret['msg'] = '修改成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $option = Db::name('test_option')->where('id',$id)->find();
            $option['option'] = unserialize($option['option']);
            $this->assign('option',$option);
            return $this->fetch('option_edit');
        }
    }
    /**
     * 测评管理-状态改变
     */
    public function changeStatus(){
        $id = $this->request->param('id');
        $option = Db::name('test_option')->where('id', $id)->find();
        $data = [];
        if ($option) {
            $data = [
                'status'        =>  $option['status'] == 0 ? 1 : 0,
                'update_time'   =>  date('Y-m-d H:i:s')
            ];
        }
        $result = Db::name('test_option')->where('id', $id)->update($data);
        if ($result) {
            $this->ret['code'] = 200;
        }
        return json($this->ret);
    }
    /**
     * 测评管理-选项删除
     */
    public function deleteOption()
    {
        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $upd = [
            'deleted'    =>  1,
            'delete_time'   =>  date('Y-m-d H:i:s')
        ];
        if (count($ids) > 1) {
            $res = Db::name('test_option')->where('id', 'in', $id)->update($upd);
        } else {
            $res = Db::name('test_option')->where('id', $ids[0])->update($upd);
        }
        if ($res) {
            $this->ret['code'] = 200;
            $this->ret['msg'] = '删除成功';
        }
        return json($this->ret);
    }
    /**
     * 测评管理-测评二维码列表
     */
    public function code(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $company_name = $this->request->param('company_name','');
            $company_tel = $this->request->param('company_tel','');
            $type = $this->request->param('type','');
            $where = [];
            if($company_name){
                $where['company_name'] = ['eq',$company_name];
            }
            if($company_tel){
                $where['company_tel'] = ['eq',$company_tel];
            }
            if($type){
                $where['type'] = ['eq',$type];
            }
            $data = Db::name('test_company')->where($where)->order('id DESC')->limit($page_start,$limit)->select();
            $count = Db::name('test_company')->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch('code');
        }
    }
    /**
     * 测评管理-测评二维码搜索
     */
    public function searchCode(){
        return $this->fetch('code_search');
    }
    /**
     * 测评管理-测评二维码添加
     */
    public function codeAdd(){
        if(request()->isPost()){
            $post = $this->request->post();
            $post['create_time'] = date('Y-m-d H:i:s');
            $post['is_money'] = isset($post['is_money'])?$post['is_money']:0;
            $id = Db::name('test_company')->insertGetId($post);
            if($id){
                import('phpcode/phpqrcode', EXTEND_PATH);
                $value = $_SERVER['HTTP_HOST'].'/test/index/index?id='.$id; //内容 
                $errorCorrectionLevel = 'L';//容错级别 
                $matrixPointSize = 7;//生成图片大小 
                $filename = '/public/uploads/codes/test/test'.$id.'.png';
                $filepath = ROOT_PATH.$filename;
                //生成图片 
                $QRcode = new \QRcode();
                $QRcode->png($value, $filepath, $errorCorrectionLevel, $matrixPointSize, 2); //参数二  生成位置
                $logo = ROOT_PATH.'/logo231.png'; 
                if ($logo !== FALSE) { 
                    $logo = imagecreatefromstring(file_get_contents($logo)); 
                    $QR = imagecreatefromstring(file_get_contents($filepath)); 
                    $logo_width2 = imagesx($QR);//图片宽度 
                    $logo_height2 = imagesy($QR);//图片高度 
                    $logo_qr_width2 = 40; 
                    $logo_qr_height2 = 40; 
                    imagecopyresampled($QR,$logo, 96, 94, 0, 0, $logo_qr_width2, $logo_qr_height2, $logo_width2, $logo_height2);
                    //背景图 前图 左边距 上边距 未知 未知  前图嵌入宽度 前图嵌入高度 前图宽度 前图高度
                    imagepng($QR,$filepath);
                    //输出图片
                    $upd = [
                        'update_time'=>date('Y-m-d H:i:s'),
                        'code'=>$filename
                    ];
                    $res = Db::name('test_company')->where('id',$id)->update($upd);
                    if($res){
                        $this->ret['code'] = 200;
                        $this->ret['msg'] = '添加成功';
                    }
                }
            }
            return json($this->ret);
        }else{
            $where = ['status'=>1,'deleted'=>0];
            $test = Db::name('test')->where($where)->field('id,title')->order('id DESC')->select();
            $this->assign('test',$test);
            return $this->fetch('code_add');
        }
    }
    /**
     * 测评管理-测评二维码编辑
     */
    public function codeEdit(){
        if(request()->isPost()){
            $post = $this->request->post();
            $post['update_time'] = date('Y-m-d H:i:s');
            $post['is_money'] = isset($post['is_money'])?$post['is_money']:0;
            $id = $post['id'];
            unset($post['id']);
            $res = Db::name('test_company')->where('id',$id)->update($post);
            if($res){
                $this->ret['code'] = 200;
                $this->ret['msg'] = '修改成功';
            }
            return json($this->ret);
        }else{
            $id = $this->request->get('id');
            $data = Db::name('test_company')->where('id',$id)->find();
            $where = ['status'=>1,'deleted'=>0];
            $test = Db::name('test')->where($where)->field('id,title')->order('id DESC')->select();
            $this->assign('test',$test);
            $this->assign('data',$data);
            return $this->fetch('code_edit');
        }
    }
    /**
     * 测评管理-测评二维码下载
     */
    public function codeDownload(){
        $id = $this->request->get('id');
        $data = Db::name('test_company')->where('id',$id)->find();
        $file = ROOT_PATH.$data['code']; 
        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        }
    }
    /**
     * 测评管理-测评数据列表
     */
    public function company(){
        if(request()->isAjax()){
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 20, 'intval');
            $page_start = ($page - 1) * $limit;
            $name = $this->request->param('name','');
            $mobile = $this->request->param('mobile','');
            $where = [];
            if($name){
                $where['name'] = ['like',"%$name%"];
            }
            if($mobile){
                $where['mobile'] = ['eq',$mobile];
            }
            $data = Db::name('test_log')->where($where)->order('id DESC')->limit($page_start,$limit)->select();
            $count = Db::name('test_log')->where($where)->count();
            if ($data) {
                $this->ret['count'] = $count;
                $this->ret['data'] = $data;
            }
            return json($this->ret);
        }else{
            return $this->fetch('company');
        }
    }
    /**
     * 测评管理-测评搜索
     */
    public function searchCompany(){
        return $this->fetch('company_search');
    }
    /**
     * 测评管理-测评数据解锁
     */
    public function changeCompany(){
        $id = $this->request->post('id');
        $upd = ['status'=>0,'update_time'=>date('Y-m-d H:i:s')];
        $res = Db::name('test_log')->where('id',$id)->update($upd);
        if($res){
            $this->ret['code'] = 200;
            $this->ret['msg'] = '解锁成功';
        }
        return json($this->ret);
    }
    /**
     * 测评管理-测评数据详情
     */
    public function companyDetail(){
        $id = $this->request->get('id');
        $data = Db::name('test_log')->where('id',$id)->find();
        $data['options'] = unserialize($data['options']);
        $province_name = _getRegionNameByCode($data['province']);
        $city_name = _getRegionNameByCode($data['city']);
        $county_name = _getRegionNameByCode($data['county']);
        $data['area'] = $province_name['region_short_name'].'-'.$city_name['region_short_name'].'-'.$county_name['region_short_name'];
        if($data){
            $data['option'] = Db::name('test_option')->where('test_id',$data['test_id'])->order('sort ASC,id ASC')->select();
            if($data['option']){
                foreach($data['option'] as &$v){
                    $v['option'] = unserialize($v['option']);
                }
            }
        }
        $this->assign('data',$data);
        return $this->fetch('company_detail');
    }
}
