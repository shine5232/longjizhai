<?php
namespace app\admin\validate;

use think\Validate;

class Region extends Validate
{
    protected $rule = [
        'region_name'       => 'require',
        'city'              => 'require'
    ];
    protected $message = [
        'region_name.require'       => '区县名称必填',
        'city.require'              => '请选择城市',
    ];
}
