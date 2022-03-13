<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%goods}}".
 *
 * @property int $goods_id
 * @property string $goods_name 商品名
 * @property string $cn_name 中文名
 * @property string $original_img 产品小图
 * @property string $mtime 该行内容更新的时间
 * @property float|null $shop_price 售价
 */
class Lygoods extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_name', 'cn_name', 'original_img', 'mtime'], 'required'],
            [['mtime'], 'safe'],
            [['shop_price'], 'number'],
            [['goods_name', 'original_img'], 'string', 'max' => 255],
            [['cn_name'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'goods_id' => 'Goods ID',
            'goods_name' => 'Goods Name',
            'cn_name' => 'Cn Name',
            'original_img' => 'Original Img',
            'mtime' => 'Mtime',
            'shop_price' => 'Shop Price',
        ];
    }
}