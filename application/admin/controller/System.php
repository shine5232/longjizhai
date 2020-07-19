<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;

class System extends Controller
{
    protected $ret = ['code' => 0, 'msg' => "", 'count' => 0, 'data' => []];
    /**
     * 设置管理列表页面
     */
    public function setting()
    {
        return $this->fetch();
    }
    /**
     * 设置管理接口
     */
    public function setting_list()
    {
        $page = $this->request->param('page', 1, 'intval');
        $limit = $this->request->param('limit', 20, 'intval');
        $page_start = ($page - 1) * $limit;
        $limit = "LIMIT $page_start,$limit";
        $sql = "SELECT * FROM lg_settings ORDER BY id DESC " . $limit;
        $data = Db::query($sql);
        $this->ret['data'] = $data;
        return json($this->ret);
    }

    public function siteConfig()
    {
        return $this->fetch();
    }
    public function setEdit()
    {
        $id = $this->request->param('id');
        $data = Db::name('settings')
            ->where('id', $id)
            ->find();
        $data = preg_replace_callback('#s:(\d+):"(.*?)";#s', function ($match) {
            return 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
        }, $data['val']);
        $data = unserialize($data);
        $data['id'] = $id;
        $logo = "'".$data['logo']."'";
        $wechat = "'".$data['wechat']."'";
        $this->assign('data', $data);
        $this->assign('logo', $logo);
        $this->assign('wechat', $wechat);
        return $this->fetch('set_edit');
    }
    public function set_upd()
    {
        $post = $this->request->param();
        $res = Db::name('settings')
            ->where('id', $post['id'])
            ->field('val')
            ->find();
        $data = serialize($post);
        if ($res['val'] == $data) {
            $this->ret['code'] = 200;
            return json($this->ret);
        }
        $row = Db::name('settings')
            ->where('id', $post['id'])
            ->update(['val' => $data]);
        if ($row) {
            $this->ret['code'] = 200;
            return json($this->ret);
        }
    }
    public function imgUpload()
    {
        $file = request()->file('file');
        if ($file) {
            $info = $file->validate(['ext' => 'jpg,png,jpeg,bmp'])->move(ROOT_PATH .  'public/' . 'uploads/'.'logo');
            if ($info) {
                $this->ret['url'] = "/uploads/logo/".date("Ymd")."/".$info->getFilename();
                $this->ret['msg'] = '上传成功';
            }else{
                $this->ret['msg'] = '上传失败';
            }
        }
        return json($this->ret);
    }
}
