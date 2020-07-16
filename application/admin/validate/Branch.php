<?php
namespace app\admin\validate;

use think\Validate;

class Branch extends Validate
{
    protected $rule = [
        'branch_name'       => 'require|min:2|max:30|unique:branch',
        'branch_pinyin'     => 'require|min:2|max:8|unique:branch',
        'province'          => 'require',
        'city'              => 'require',
        'county'            => 'require'
    ];
    protected $message = [
        'branch_name.require'       => '分站名称必须',
        'branch_name.unique'        => '分站名称重复',
        'branch_name.min'           => '分站名称最短2位',
        'branch_name.max'           => '分站名称最长30位',
        'branch_pinyin.require'     => '分站拼音必须',
        'branch_pinyin.unique'      => '分站拼音重复',
        'branch_pinyin.min'         => '分站拼音最短2位',
        'branch_pinyin.max'         => '分站拼音最长8位',
        'province.require'          => '请选择省份',
        'city.require'              => '请选择城市',
        'county.require'            => '请选择区/县',
    ];
}
