<?php
namespace app\admin\validate;

use think\Validate;

class MemberRank extends Validate
{
    protected $rule = [
        'rank_name'       => 'require|min:2',
    ];
    protected $message = [
        'rank_name.require'       => '类别名称必须',
        'rank_name.min'           => '类别名称最短2位',
    ];
}
