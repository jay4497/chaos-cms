<?php

namespace app\admin\model;

use think\Model;


class Content extends Model
{
    // 表名
    protected $name = 'content';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $weigh = $row->weigh;
            if(empty($weigh)) {
                $pk = $row->getPk();
                $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
            }
        });

        self::beforeInsert(function($row) {
            $now = date('Y-m-d H:i:s');
            $row->created_at = $now;
            $row->updated_at = $now;
        });
        self::beforeUpdate(function ($row) {
            $now = date('Y-m-d H:i:s');
            $row->updated_at = $now;
        });
    }

    public function getStatusList()
    {
        return [
            0 => __('draft'),
            1 => __('published'),
            2 => __('review'),
            3 => __('forbidden')
        ];
    }

    public function getStatusRadios()
    {
        return [
            0 => __('draft'),
            2 => __('publish'),
        ];
    }

}
