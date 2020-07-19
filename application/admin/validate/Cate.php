<?php
namespace app\admin\validate;

use think\Validate;

class Cate extends Validate
{
    protected $rule = [
        'pid'       => 'require',
        'title'     => 'require|min:2|max:15|unique:article_cate',
        'sort'      =>'require|number',
    ];
    protected $message = [
    	'title.require' =>'栏目名称不能为空',
    	'title.min'		=>'栏目名称太短',
    	'title.max'		=>'栏目名称太长',
    	'title.unique'	=>'系统中已经存在该栏目名称',
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
