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
        // $data['score'] = '1';
        // $data['card'] = '1';
        // $data['phone'] = '1';
        // $data['look'] = '1';
        // $data['vip1'] = '1';
        // $data['vip2'] = '1';
        // $data['vip3'] = '1';
        // $data['buy_proportion'] = '1';
        // $data['com_proportion'] = '1';
        // $data['shops_proportion'] = '1';
        // $data['rate'] = '1';
        // $data['zong_min'] = '1';
        // $data['fen_min'] = '1';
        // $data = serialize($data);
        // var_dump($data);die;
        $id = $this->request->param('id');
        $data = Db::name('settings')
            ->where('id', $id)
            ->find();
        /* $data = preg_replace_callback('#s:(\d+):"(.*?)";#s', function ($match) {
            return 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
        }, $data['val']); */
        $data = unserialize($data['val']);
        $data['id'] = $id;
        $this->assign('data', $data);
        if($id == 1){
            return $this->fetch('set_edit');
        }elseif($id == 2){
            return $this->fetch('score_edit');
        }elseif($id == 3){
            return $this->fetch('wechat_edit');
        }
    }
    public function set_upd()
    {
        // var_dump('111');die;
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
}
