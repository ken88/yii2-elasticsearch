<?php use yii\widgets\LinkPager;?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>search</title>
    <style type="text/css">
        .goods{margin-top: 60px; width: 800px}
        .info{ float: left; margin: 0px 30px 30px 0px; width: 150px; border: #0b72b8 1px solid; }
        .info div{text-align: center}
        .cn_name{ height: 120px;}
        .price{ color: red}
        .m-pagination i{float: left;}
        .color{color: red;}
        .pagination li{ float: left; margin-left: 5px;list-style:none;}
    </style>
</head>
<body>

<div class="search">
    <form action="/goods/index" method="get" id="form1">
        <div>
            <span>商品关键词：</span>
            <span><input type="text" id="cn_name" name="cn_name" value="<?php echo $cn_name;?>" /></span>
        </div>
        <div>
            <span>价钱：大于<input type="text" id="price1" name="price1" value="<?php echo $price1;?>"> </span>
            <span>价钱：小于<input type="text" id="price2" name="price2" value="<?php echo $price2;?>"> </span>
            <span><input type="submit" value="搜索"> </span>
        </div>
        <div>
            <span>排序：</span>
            <span>
               <input type="submit" value="价钱正序" id="priceOrder1" name="priceOrder">
                |
                <input type="submit" value="价钱倒序" id="priceOrder2" name="priceOrder">
            </span>
        </div>
    </form>
</div>

<?php
if (!empty($pages)) {
    echo LinkPager::widget([
        'pagination' => $pages,
        'nextPageLabel' => '下一页',
        'prevPageLabel' => '上一页',
        'firstPageLabel' => '首页',
        'lastPageLabel' => '尾页',
        'maxButtonCount' => 20,
    ]);
}
?>
<div class="goods">
    <?php if (!empty($info)){foreach ($info as $v){ ?>
    <div class="info">
        <div class="cn_name">
            <?php
                if (!empty($v['highlight'])) {
                    echo $v['highlight']['cn_name'][0];
                }else {
                    echo $v['_source']['cn_name'];
                }
            ?>
        </div>
        <div>
            <img src="https://img7.shpintengoms.com/<?php echo $v['_source']['original_img']; ?>" width="150" height="150">
        </div>
        <div class="price"><?php echo $v['_source']['shop_price']; ?></div>
    </div>
    <?php }} ?>
</div>

</body>
</html>
<script type="text/javascript">
    var priceOrder = '<?= $priceOrder;?>';
    if (priceOrder == '价钱正序') {
        document.getElementById('priceOrder1').className = 'color';
    } else if (priceOrder == '价钱倒序')  {
        document.getElementById('priceOrder2').className = 'color';
    }
</script>