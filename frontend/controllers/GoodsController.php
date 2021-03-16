<?php


namespace frontend\controllers;


use common\models\Elastic;
use Yii;
use yii\data\Pagination;

class GoodsController extends BaseController
{
    public function actionIndex()
    {
        $data = [];

        # 1. es实例化
        $es = new Elastic();
        $res = $es::find();
        $hightlight = []; # 高亮


        if (Yii::$app->request->get()) {

            # 2. 接收参数
            $cnName = Yii::$app->request->get('cn_name','');
            $price1 = Yii::$app->request->get('price1',0);
            $price2 = Yii::$app->request->get('price2',0);

            if (!empty($cnName)) {
                # 2.1 高亮查询
                $hightlight = [
                    # 左标签，配合前端.highlight这个类来实现高亮
                    "pre_tags"=>['<span style="color:red">'],
                    "post_tags"=>['</span>'],

                    # 在原生api中写的是{}表示空对象，因此使用php的stdClass来表示空对象
                    "fields"=>[
                        "cn_name"=>new \stdClass(),
                        'descr'=>new \stdClass()
                    ]
                ];

                # 2.2 通过DSL 查询es数据
                $res = $res->query([
                    'match' => [
                        'cn_name' => [
                            "query" => "{$cnName}",
                            "operator" => 'and'
                        ]
                    ]
                ]);
            }
        }
        # 2.3 获取总数
        $count = $res->search(); # search 与 all 区别在于 all是在search基础上处理再拿出结果

        $pages = new Pagination(['totalCount' => $count['hits']['total']['value']]);

        $resInfo = $res->source(['cn_name','shop_price','original_img']) # 只显示的字段信息
        ->highlight($hightlight) # 高亮数据
        ->orderBy('goods_id asc') # 排序
        ->offset($pages->offset)
            ->limit(60)# 显示条数
            ->asArray()
            ->all();


        $data = [
            'info' => $resInfo,
            'cn_name' => $cnName,
            'pages' => $pages
        ];

       return $this->renderPartial("index",$data);
    }

}