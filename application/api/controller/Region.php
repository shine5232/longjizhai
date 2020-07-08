<?php
namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Session;
use think\Validate;

class Region extends Controller
{

    public function index(){
        $post = $this->request->post();
        if($post['type'] == '1'){
            $data = _getRegion();
        }else{
            $data = _getRegion($post['code']);
        }
        return json(200,'success',$data);
    }

}
