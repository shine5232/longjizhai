<?php
namespace app\admin\validate;

use think\Validate;

class Notice extends Validate
{
    protected $rule = [
        'title'     => 'require',
        'content'      =>'require',
    ];
    protected $message = [
    	'title.require' =>'标题不能为空',
        'content.require'  =>'内容不能为空'     
    ];
    protected $scene = [
        'edit'  => [
            'title',
            'content',
        ],
    ];

}
