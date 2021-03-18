<?php

namespace common\models;

use yii\elasticsearch\ActiveRecord;

class Elastic extends ActiveRecord
{


    # 定义db链接 这个就是第二步配置的组件的名字（key值）
    public static function getDb()
    {
        return \Yii::$app->get('elasticsearch');
    }

    public static  function index()
    {
        return "oms";
    }

    # 需要返回的字段
    public function attributes()
    {
        # 这里就是你要查询的字段，你要查什么写什么字段就好了
        return ['goods_name','cn_name','shop_price','original_img','mtime','goods_id'];
    }

    /**
     * @return array This model's mapping
     */
    public static function mapping()
    {
        # es7.x 版本去掉了type
        return ['properties' =>
            [
                'goods_id'      => ['type' => 'integer'],
                'goods_name'    => ['type' => 'text'],
                'cn_name'       => ['type' => 'text','analyzer'=>'ik_smart'],
                'shop_price'    => ['type'=>'float'],
                'original_img'  => ['type' => 'keyword'],
                'mtime'         => ['type' => 'date']
            ]
        ];
    }




    /**
     * 获取映射
     *
     * @return mixed
     */
    public static function getMapping()
    {
        $db = self::getDb();
        $command = $db->createCommand();
        return $command->getMapping(static::index(), static::type());
    }

    /**
     * 更新映射
     */
    public static function updateMapping()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->setMapping(static::index(), static::type(), static::mapping());
    }


    /**
     * 创建索引
     */
    public static function createIndex()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        return $command->createIndex(static::index(), [
            //'aliases' => [ /* ... */ ],
            'mappings' => static::mapping(),
            //'settings' => [ /* ... */ ],
        ]);
    }

    /**
     * 删除索引
     */
    public static function deleteIndex()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->deleteIndex(static::index(), static::type());
    }
}