<?php

namespace console\controllers;

use yii\console\Controller;
use common\models\Elastic;
use common\models\Lygoods;
class EsController extends Controller
{
    public function actionIndex() {
        ini_set("memory_limit","2048M");
        $columns = 'goods_id,goods_name,cn_name,shop_price,original_img,mtime';
        $goods = Lygoods::find()->select($columns)->asArray()->limit(1000)->all();

        $ch = array_chunk($goods,100);
        dd($ch);
//        foreach ($goods as $k => $v) {
//            $es = new Elastic();
//            $es->_id = $v['goods_id'];
//            $es->goods_id = $v['goods_id'];
//            $es->goods_name = $v['goods_name'];
//            $es->cn_name = $v['cn_name'];
//            $es->shop_price = $v['shop_price'];
//            $es->original_img = $v['original_img'];
//            $es->mtime = $v['mtime'];
//            $res = $es->save();
//            echo "d第{$k}条数据返回结果：".$res.PHP_EOL;
//        }
       echo "ok";
    }
}