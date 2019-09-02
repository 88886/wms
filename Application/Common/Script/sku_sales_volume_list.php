<?php
ini_set('memory_limit', '2028M');
define('APP_DIR', dirname(__FILE__) . '/../../../');
define('APP_DEBUG', true);
define('APP_MODE', 'cli');
define('APP_PATH', APP_DIR . './Application/');
define('RUNTIME_PATH', APP_DIR . './Runtime_script/'); // 系统运行时目录
require APP_DIR . './ThinkPHP/ThinkPHP.php';

use Common\Service\ExcelService;

$time = venus_script_begin("SKU销售量排行榜");
$stime = date('Y-m-d', strtotime(date('Y-m-01') . ' -1 month'));
$etime = date('Y-m-d', strtotime(date('Y-m-01') . ' -1 day'));
$dbName = "zwdb_wms";
$spuSql = "SELECT * FROM $dbName.wms_spu spu LEFT JOIN $dbName.wms_sku sku ON sku.spu_code = spu.spu_code";
$spuSql .= " WHERE spu.is_selfsupport = 1";
//$spuSql .= " LEFT JOIN $dbName.wms_supplier sup ON sup.sup_code = spu.sup_code where spu.sup_code = 'SU00000000000001'";
$spuList = M()->query($spuSql);

$goodsSql = "SELECT SUM(sku_count) as sku_count, sku.sku_code, sku.sku_unit, spu.spu_name, spu.spu_type, spu.spu_subtype FROM $dbName.wms_order o LEFT JOIN $dbName.wms_ordergoods goods ON goods.order_code = o.order_code";
$goodsSql .= " LEFT JOIN $dbName.wms_sku sku ON sku.sku_code = goods.sku_code";
$goodsSql .= " LEFT JOIN $dbName.wms_spu spu ON spu.spu_code = goods.spu_code";
$goodsSql .= " LEFT JOIN $dbName.wms_supplier sup ON sup.sup_code = goods.supplier_code";
$goodsSql .= " WHERE o.order_ctime >= '$stime' AND o.order_ctime <= '$etime'";
$goodsSql .= " AND o.w_order_status = 3 AND goods.supplier_code = 'SU00000000000001' group by sku.sku_code";
$orderGoodsList = M()->query($goodsSql);
$ordergoodsSkuCodeData = array_column($orderGoodsList, "sku_code");
$skuCountList = array_column($orderGoodsList, "sku_count");
$skuCountList = array_unique($skuCountList);
rsort($skuCountList);
$skuCountList = array_values($skuCountList);
$skuDataList = array();
$spuTypeSort = array();
$spuSubTypeSort = array();
$allLine=0;
foreach ($orderGoodsList as $item) {
    $skCount = $item['sku_count'];
    $key = array_keys($skuCountList, $skCount);
    $skuDataList[$item['sku_code']] = $key[0] + 1;
    if($allLine<$key[0] + 1)$allLine=$key[0] + 1;
    $spuTypeSort[$item['spu_type']][$item['sku_code']] = $skCount;
    $spuSubTypeSort[$item['spu_subtype']][$item['sku_code']] = $skCount;
}
$skuCountTypedata = array();
foreach ($spuTypeSort as $type => $oitem) {
    $skuCountArrOne = array_values($oitem);
    $skuCountArrOne = array_unique($skuCountArrOne);
    rsort($skuCountArrOne);
    $skuCountArrOne = array_values($skuCountArrOne);
    $skuCountTypedata[$type] = $skuCountArrOne;
}
$skuCountSubTypedata = array();
foreach ($spuSubTypeSort as $subtype => $oitem) {
    $skuCountArrTwo = array_values($oitem);
    $skuCountArrTwo = array_unique($skuCountArrTwo);
    rsort($skuCountArrTwo);
    $skuCountArrTwo = array_values($skuCountArrTwo);
    $skuCountSubTypedata[$subtype] = $skuCountArrTwo;
}

$skuData = array();
foreach ($orderGoodsList as $index => $spuItem) {
    $spuType = $spuItem['spu_type'];
    $spuSubType = $spuItem['spu_subtype'];
    $skCount = $spuItem['sku_count'];
    $key = array_keys($skuCountTypedata[$spuType], $skCount);
    $keys = array_keys($skuCountSubTypedata[$spuSubType], $skCount);
    $skuData[$skuDataList[$spuItem['sku_code']]][] = array(
        $spuItem['sku_code'], $spuItem['spu_name'], $spuItem['sku_unit'], $spuItem['sku_count'],
        $spuItem['spu_type'], $key[0] + 1, $spuItem['spu_subtype'],
        $keys[0] + 1, $skuDataList[$spuItem['sku_code']]
    );
}

$fname = "SKU销量排行榜";
$header = array("货号", "名称", "单位", "销量", "所属一级分类", "所属一级分类销量排名", "所属二级分类", "所属二级分类销量排名", "总销量排名");
foreach ($spuList as $index => $spuItem) {
    $skCode = $spuItem['sku_code'];
    if (!in_array($skCode, $ordergoodsSkuCodeData)) {
        $skuCount = empty($spuItem['sku_count']) ? 0 : $spuItem['sku_count'];
        $skuData[($allLine+1)][] = array(
            $spuItem['sku_code'], $spuItem['spu_name'], $spuItem['sku_unit'], $skuCount,
            $spuItem['spu_type'], (count($skuCountTypedata[$spuItem['spu_type']]) + 1), $spuItem['spu_subtype'],
            (count($skuCountSubTypedata[$spuItem['spu_subtype']]) + 1), ($allLine + 1)
        );
    }

}
$excelData = array();
$skTotalSalesList = array();
foreach ($skuData as $skuDatum) {
    foreach ($skuDatum as $index => $item) {
        $excelData[] = array(
            "skCode" => $item[0],
            "spName" => $item[1],
            "skUnit" => $item[2],
            "skCount" => $item[3],
            "spType" => $item[4],
            "spVtype" => $item[5],
            "spSubtype" => $item[6],
            "spVsubtype" => $item[7],
            "spTotal" => $item[8],
        );
    }
}
$totalSalesRanking = array_column($excelData,'spTotal');
array_multisort($totalSalesRanking,SORT_ASC,$excelData);
foreach($excelData as $index => $val){
    $skTotalSalesList[$fname][] = array(
        "0" => $val['skCode'],
        "1" => $val['spName'],
        "2" => $val['skUnit'],
        "3" => $val['skCount'],
        "4" => venus_spu_type_name($val['spType']),
        "5" => $val['spVtype'],
        "6" => venus_spu_catalog_name($val['spSubtype']),
        "7" => $val['spVsubtype'],
        "8" => $val['spTotal'],
    );
}

$fileName = ExcelService::getInstance()->exportExcel($skTotalSalesList, $header, "001");

if ($fileName) {
    $title = "sku销售量排行榜";
    $content = "sku销售量排行榜";
    $address = array("wenlong.yang@shijijiaming.com","xiaolong.hu@shijijiaming.com","jinwei.cao@shijijiaming.com");
    $attachment = array(
        "sku销售量排行榜.xlsx" => C("FILE_SAVE_PATH")."001/".$fileName,
    );
//$attachment = array(
//    "$fileName.zip" =>$a ,
//);
    if (sendMailer($title, $content, $address, $attachment)) {
        echo "(发送成功)";
    } else {
        echo "(发送失败)";
    }
//    $saveFile = "skuSalesVolume.xlsx";
//    $moveFileRes = rename("/home/wms/app/Public/files/001/$fileName", "/home/wms/app/Public/orderfiles/$saveFile");
//    if (!$moveFileRes) {
//        echo "移动失败" . "$saveFile";
//    }
} else {
    $success = false;
    $data = "";
    $message = "下载失败";
}
venus_script_finish($time);
exit();




