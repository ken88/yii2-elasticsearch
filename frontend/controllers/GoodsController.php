<?php


namespace frontend\controllers;


use common\models\Elastic;
use common\models\Lygoods;
use Yii;
use yii\data\Pagination;

class GoodsController extends BaseController
{

    /**
     * 查询es数据
     * @return string 结果集
     * @throws \yii\elasticsearch\Exception
     */
    public function actionIndex()
    {
        $data = [];

        # 1. es实例化
        $es = new Elastic();
        $res = $es::find();
        $hightlight = []; # 高亮

        # 2. 接收参数
        $cnName = Yii::$app->request->get('cn_name',''); # 商品名子
        $price1 = Yii::$app->request->get('price1','');   # 价钱大于
        $price2 = Yii::$app->request->get('price2','');   # 价钱小于
        $priceOrder = Yii::$app->request->get('priceOrder',null);   # 价钱排序

        $orderby = null;

        # 2.1 排序
        if (empty($priceOrder)) { # 没有排序条件默认 id倒序
            $orderby = 'id desc';
        } else {
            $orderby = $priceOrder == '价钱正序' ? 'shop_price asc' : 'shop_price desc';
        }

        if (Yii::$app->request->get()) {
            $query = []; # 查询条件
            $i = 0;      # 记录搜素条件的下标
            $k = 0;      # 记录搜素条件的下标

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
                # 过滤查询
                $query['bool']['filter'][$k] = [
                    "range" => [
                        "shop_price" => [
                            "gte" => $price1
                        ]
                    ]
                ];
                $k++;
            }

            # 3.2 价钱小于某值 条件
            if (!empty($price2)) {
                # 过滤查询
                $query['bool']['filter'][$k] = [
                    "range" => [
                        "shop_price" => [
                            "lte" => $price2
                        ]
                    ]
                ];
                $k++;
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

            # 3.4 通过DSL 查询es数据
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

        $data['res'] = [
            'info' => $resInfo,
            'cn_name' => $cnName,
            'price1' => $price1,
            'price2' => $price2,
            'priceOrder' => $priceOrder,
            'pages' => $pages
        ];

        # 6. 聚合查询
        $options = [
            "terms" =>array( # terms  表示分组的意思
                "field" => "shop_price", # 通过那个字段分组
                "size"=>10, # 返回的条数
                "order"=>array("_term"=>"desc") # 当前字段排序
            ),
            'aggregations' =>[
                'max_price' => [ # 分组后每组求最大值
                    'max' => [
                        'field' => 'shop_price'
                    ]
                ],
                'sum_price' => [ # 分组后每组总价钱
                    'sum' => [
                        'field' => 'shop_price'
                    ]
                ],
                'avg_price' => [ # 分组后每组平均价价钱
                    'avg' => [
                        'field' => 'shop_price'
                    ]
                ]
            ]
        ];
        $jh = $es::find()->addAggregate('acddd',$options)->limit(0);
        $command = $jh->createCommand();
        $rows = $command->search([],'post');
        $data_arr = $rows['aggregations']['acddd']['buckets'];
        $data['jh'] = $data_arr;
//        dd($data['jh']);
       return $this->renderPartial("index",$data);
    }

    /**
     * 创建索引与mapping
     */
    public function actionAddIndexMapping() {

        $res = Elastic::createIndex();
        dd($res);
    }


    /**
     * 新增es数据
     */
    public function actionAdd() {
        ini_set("memory_limit","2048M");
        $columns = 'id,goods_name,cn_name,shop_price,original_img,mtime';
        $goods = Lygoods::find()->select($columns)->where("id>10001")->asArray()->all();
        dd($goods);

        foreach ($goods as $v) {
            $es = new Elastic();
            $es->_id = $v['id'];
            $es->id = $v['id'];
            $es->goods_name = $v['goods_name'];
            $es->cn_name = $v['cn_name'];
            $es->shop_price = $v['shop_price'];
            $es->original_img = $v['original_img'];
            $es->mtime = $v['mtime'];
            $res = $es->save();

        }
        dd('ok');
    }
}