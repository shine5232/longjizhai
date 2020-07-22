<?php
namespace app\admin\validate;

use think\Validate;

class Cate extends Validate
{
    protected $rule = [
        'pid'       => 'require',
        'title'     => 'require|min:2|max:50',
        'sort'      =>'require|number',
    ];
    protected $message = [
    	'title.require' =>'栏目名称不能为空',
    	'title.min'		=>'栏目名称太短',
    	'title.max'		=>'栏目名称太长',
        'sort.require'  =>'排序不能为空',
        'sort.number'   =>'排序必须为数字',     
    ];
    protected $scene = [
        'edit'  => [
            'title',
            'sort',
        ],
    ];

}
