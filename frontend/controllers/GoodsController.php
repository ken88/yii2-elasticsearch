<?php


namespace frontend\controllers;


use common\models\Elastic;
use function Webmozart\Assert\Tests\StaticAnalysis\object;
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

        # 2. 接收参数
        $cnName = Yii::$app->request->get('cn_name',''); # 商品名子
        $price1 = Yii::$app->request->get('price1',0);   # 价钱大于
        $price2 = Yii::$app->request->get('price2',0);   # 价钱小于
        $priceOrder = Yii::$app->request->get('priceOrder',null);   # 价钱排序

        $orderby = null;

        # 2.1 排序
        if (empty($priceOrder)) { # 没有排序条件默认 id倒序
            $orderby = 'goods_id desc';
        } else {
            $orderby = $priceOrder == '价钱正序' ? 'shop_price asc' : 'shop_price desc';
        }

        if (Yii::$app->request->get()) {
            $query = []; # 查询条件
            $i = 0;      # 记录搜素条件的下标

            # 3. 条件生成，商品名条件
            if (!empty($cnName)) {
                $query['bool']['must'][$i] = [
                    "match" => [
                        "cn_name" => [
                            "query" => "{$cnName}",
                            "operator" => 'and'
                        ]
                    ]
                ];
                $i++;
            }

            # 3.1 价钱大于某值 条件
            if (!empty($price1)) {
                $query['bool']['must'][$i] = [
                    "range" => [
                        "shop_price" => [
                            "gte" => $price1
                        ]
                    ]
                ];
                $i++;
            }

            # 3.2 价钱小于某值 条件
            if (!empty($price2)) {
                $query['bool']['must'][$i] = [
                    "range" => [
                        "shop_price" => [
                            "lte" => $price2
                        ]
                    ]
                ];
                $i++;
            }
//            dd($query);

            # 3.3  高亮查询
            $hightlight = [
                # 左标签，配合前端.highlight这个类来实现高亮
                "pre_tags"=>['<span style="color:red">'],
                "post_tags"=>['</span>'],

                # 在原生api中写的是{}表示空对象，因此使用php的stdClass来表示空对象
                "fields"=>[
                    "cn_name" => new \stdClass()
                ]
            ];

            # 2.2 通过DSL 查询es数据
            $res = $res->query($query);
        }

        # 4. 获取总数
        $count = $res->search(); # search 与 all 区别在于 all是在search基础上处理再拿出结果

        # 4.1 获取分页
        $pages = new Pagination(['totalCount' => $count['hits']['total']['value']]);

        # 4.2 数据获取
        $resInfo = $res->source(['cn_name','shop_price','original_img']) # source 只显示的字段信息
            ->highlight($hightlight) # 高亮数据
            ->orderBy($orderby) # 排序
            ->offset($pages->offset)
            ->limit($pages->limit)# 显示条数
            ->asArray()
            ->all();

        $data = [
            'info' => $resInfo,
            'cn_name' => $cnName,
            'price1' => $price1,
            'price2' => $price2,
            'priceOrder' => $priceOrder,
            'pages' => $pages
        ];

       return $this->renderPartial("index",$data);
    }

}