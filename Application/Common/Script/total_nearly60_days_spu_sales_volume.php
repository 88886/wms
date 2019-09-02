<?php
/*
 * liuguofang
 * 2019-05-07
 * 查询每个货品近60天的销售量数据
 */
ini_set('memory_limit', '2028M');
define('APP_DIR', dirname(__FILE__) . '/../../../');
define('APP_DEBUG', true);
define('APP_MODE', 'cli');
define('APP_PATH', APP_DIR . './Application/');
define('RUNTIME_PATH', APP_DIR . './Runtime_script/'); // 系统运行时目录
require APP_DIR . './ThinkPHP/ThinkPHP.php';

$time = venus_script_begin("查询每个货品近60天的销售量数据");

use Wms\Dao\SpuDao;
use Wms\Dao\OrdergoodsDao;

$spuSalesVolume = array();
//$newArr = array();
$sctime = date("Y-m-d", strtotime("-60 day"));
$ectime = date("Y-m-d", time());
$fileUrl = '../../../Public/spufiles/spusalesvolume60.txt';
//$spuDataList = SpuDao::getInstance()->queryAllList();
//foreach ($spuDataList as $index => $spuItem) {
//    $cond = array(
//        "sctime" => $sctime,//开始时间
//        "ectime" => $ectime //结束时间
//    );
//    $orderGoodsList = OrdergoodsDao::getInstance()->queryListBySkuCode($cond);
//    foreach ($orderGoodsList as $ordergoods => $orderGoodsItem) {
//        if(in_array($spuItem['sku_code'],$orderGoodsItem)){
//            if (!array_key_exists($orderGoodsItem['sku_code'], $newArr)) {
//                $newArr[$orderGoodsItem['sku_code']] = $orderGoodsItem['sku_count'];
//            } else {
//                $newArr[$orderGoodsItem['sku_code']] += $orderGoodsItem['sku_count'];
//            }
//        }
//    }
//}
$newArr = array("1","2","3","4","5","6","7","8","9","10","6","7","8","9","10");
$spuResult = json_encode($newArr);
file_put_contents($fileUrl, $spuResult);

venus_script_finish($time);
exit();