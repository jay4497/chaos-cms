<?php

namespace app\admin\validate;

use think\Validate;

class Content extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'category_id' => 'require',
        'title' => 'require|max:150',
        'content' => 'require'
    ];
    /**
     * 提示消息
     */
    protected $message = [
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['category_id', 'title', 'content'],
        'edit' => ['category_id', 'title', 'content'],
    ];
    
}
