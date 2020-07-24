<?php
namespace app\admin\validate;

use think\Validate;

class MemberType extends Validate
{
    protected $rule = [
        'type_title'       => 'require|min:2|unique:member_type',
    ];
    protected $message = [
        'type_title.require'       => '类别名称必须',
        'type_title.unique'        => '类别名称重复',
        'type_title.min'           => '类别名称最短2位',
    ];
}
