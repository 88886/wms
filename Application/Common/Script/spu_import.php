<?php
ini_set('memory_limit', '356M');
define('APP_DIR', dirname(__FILE__) . '/../../../');
define('APP_DEBUG', true);
define('APP_MODE', 'cli');
define('APP_PATH', APP_DIR . './Application/');
define('RUNTIME_PATH', APP_DIR . './Runtime_script/'); // 系统运行时目录
require APP_DIR . './ThinkPHP/ThinkPHP.php';

use Wms\Dao\SpuDao;
use Wms\Dao\SkuDao;
use Wms\Dao\SupplierDao;
use Common\Service\ExcelService;

$time = venus_script_begin("初始化Venus数据库的SPUSKU数据");
$url = "C:/Users/gfz_1/Desktop/spu/";
$errorMsg = "";
$insertSpuSql = "";
$insertSkuSql = "";
$updateSpuSql = "";
$updateSkuSql = "";
$errorMsgTxt = $url . "error_msg.txt";
$insertSpuSqlTxt = $url . "insertspusql.txt";
$insertSkuSqlTxt = $url . "insertskusql.txt";
$updateSpuSqlTxt = $url . "updatespusql.txt";
$updateSkuSqlTxt = $url . "updateskusql.txt";
$files = $url . "spu.xlsx";
//echo file_exists($files)?"yes":"no";exit();
if (!file_exists($files)) {
    $errorMsg[] = "文件不存在";
}
$datas = ExcelService::GetInstance()->uploadByShell($files);
if (empty($datas)) {
    $errorMsg[] = "读取excel数据为空";
}
//array_pop($datas);//过滤最后一个类型说明表
$dicts = array(
    "A" => "sku_code",//sku品类编号
    "B" => "spu_code",//spu品类编号
    "C" => "spu_type",//spu二级分类编号
    "E" => "spu_subtype",//spu二级分类编号
    "G" => "spu_storetype",//spu仓储方式
    "I" => "spu_name",//spu货品名称
    "J" => "spu_brand",//spu品牌
    "K" => "spu_from",//spu货品产地
    "L" => "spu_mark",//sku备注
    "M" => "spu_img",//spu图片
    "N" => "spu_cunit",//可计算最小单位
    "O" => "spu_norm",//spu规格
    "P" => "spu_unit",//spu计量单位
    "Q" => "spu_bprice",//spu采购价
    "R" => "spu_sprice",//spu销售价
    "S" => "sup_code",//spu供货商
    "U" => "profit_price",//spu销售价
    "V" => "sku_norm",//sku规格
    "W" => "sku_unit",//sku单位
    "X" => "spu_count",//单位sku含spu数量
    "Y" => "sku_mark"//sku备注
);

$skuList = array();

foreach ($datas as $sheetName => $list) {
    unset($list[0]);
    $skuList = array_merge($skuList, $list);
}
$dataArr = array();
//venus_db_starttrans();//启动事务
//$result = true;
$spuCount = SpuDao::getInstance()->queryCountByCondition();
if ($spuCount > 0) {
    $i = $spuCount;
} else {
    $i = 0;
}

foreach ($skuList as $index => $skuItem) {

    $skuData = array();
    foreach ($dicts as $col => $key) {
        $skuData[$key] = isset($skuItem[$col]) ? $skuItem[$col] : "";
    }
    if (empty($skuData)) {
        $errorMsg[] = "获取数据为空";
    }
    //验证二级分类是否符合规定长度
    if (empty($skuData["sku_code"])) {
        if (!empty($skuData['spu_subtype']) && strlen($skuData['spu_subtype']) == 5) {
            $skuData['spu_type'] = substr($skuData['spu_subtype'], 0, 3);//一级分类编号

        } else if (!empty($skuData['spu_subtype']) && (strlen($skuData['spu_subtype']) > 5 || strlen($skuData['spu_subtype']) < 5)) {
            $skuData['spu_type'] = substr($skuData['spu_subtype'], 0, 3);//一级分类编号
//            venus_throw_exception(5004, $skuData['spu_name']);
            $errorMsg[] = "货品二级分类不符合规定长度：" . $skuData['spu_name'];
        }
        $spType = substr($skuData['spu_subtype'], 0, 3);
        $spuType = venus_spu_type_name($spType);//一级分类
        if (!empty($skuData['spu_subtype']) && empty($spuType)) {
            $errorMsg[] = "货品分类配置里无此一级分类:" . $spType;
        }

        $spuSubtype = venus_spu_catalog_name($skuData['spu_subtype']);//二级分类
        if (!empty($skuData['spu_subtype']) && empty($spuSubtype)) {
            $errorMsg[] = "货品分类配置里无此二级分类:" . $skuData['spu_subtype'];
        }
    }

    if (trim($skuData['spu_name']) == '' || trim($skuData['spu_subtype']) == '' || trim($skuData['spu_storetype']) == '') {
        if (trim($skuData['spu_name']) == '' && trim($skuData['spu_subtype']) == '' && trim($skuData['spu_storetype']) == '') {
            continue;
        } else {
            if (empty($skuData['spu_name'])) {
                venus_throw_exception(1, "货品名称不能为空");
            }

            if (empty($skuData['spu_subtype'])) {
                venus_throw_exception(1, "货品二级分类不能为空");
            }

            if (empty($skuData['sku_norm'])) {
                venus_throw_exception(1, "sku货品规格不能为空");
            }

            if (empty($skuData['spu_unit'])) {
                venus_throw_exception(1, "spu货品单位不能为空");
            }

            if (empty($skuData['spu_storetype'])) {
                venus_throw_exception(1, "货品仓储方式不能为空");
            }
        }
    } else {
        $condition = array(
            "spu_subtype" => $skuData['spu_subtype'],
            "spu_brand" => $skuData['spu_brand'],
            "spu_storetype" => $skuData['spu_storetype'],
            "spu_mark" => $skuData['spu_mark'],
            "spu_count" => $skuData['spu_count'],
            "spu_name" => $skuData['spu_name'],
            "spu_norm" => $skuData['spu_norm'],
            "spu_unit" => $skuData['spu_unit'],
            "sku_norm" => $skuData['sku_norm'],
            "sku_unit" => $skuData['sku_unit'],
            "sku_mark" => $skuData['sku_mark']
        );

        $jsonCon = json_encode($condition);
        if (in_array($jsonCon, $dataArr)) {//检测excel表里是否有重复的数据
            if (empty($skuData["sku_code"])) {
                $redata = json_decode($jsonCon, true);
                $name = $redata['spu_name'];
//                venus_throw_exception(5001, $name);
                $errorMsg[] = "EXCEL表里存在重复数据：" . $name;
            }
        } else {
            $dataArr[] = $jsonCon;
            //检测wms_spu、wms_sku数据表是否已存在该品类
            if (empty($skuData["sku_code"])) {
                $getField = 'spu_name';
                $totalCount = SpuDao::getInstance()->queryOneByCondition($condition, $getField);
                if (!empty($totalCount)) {
//                    venus_throw_exception(5002, $totalCount);
                    $errorMsg[] = "数据库里已存在货品：" . $totalCount;
                }
            }
            if (empty($skuData["sku_code"])) {
                $i++;
            }
            $spuCode = "SP" . str_pad($i, 6, "0", STR_PAD_LEFT);

            if (empty($skuData["sku_code"]) && $skuData['spu_img']) {
                $oldDir = $url . "spuimg/";
                $newDir = $url . "spuimages/";
                if (file_exists($oldDir . $skuData['spu_img'] . ".jpg")) {
                    $files = $oldDir . $skuData['spu_img'] . ".jpg";
                    $newName = $newDir . $spuCode . ".jpg";
                } else if (file_exists($oldDir . $skuData['spu_img'] . ".png")) {
                    $files = $oldDir . $skuData['spu_img'] . ".png";
                    $newName = $newDir . $spuCode . ".jpg";
                } else {
//                    venus_throw_exception(5003, $skuData['spu_img']);
                    $errorMsg[] = "缺少图片：" . $skuData['spu_img'];
                }
                copy($files, $newName);

                $spuImg = $spuCode . ".jpg";
            } else {
                $spuImg = "";
            }

            if($skuData["sku_unit"] == '斤' || $skuData["sku_unit"] == '公斤'){
                $spuCunit = 0.1;
            }else{
                $spuCunit = 1;
            }
//            $spuCunit = empty($skuData["spu_cunit"]) ? 1 : $skuData["spu_cunit"];
            if(empty($skuData["sup_code"])){
                $isSelfsupport = 1;
                $supCode = "SU00000000000001";
            }else{
                $parameter['supcode'] = $skuData['sup_code'];
                $supDataList = SupplierDao::getInstance("WA000001")->queryListByCondition($parameter);
                $supType = $supDataList[0]['sup_type'];
                if ($supType == 1) {//自有供应商1.自有供应商 2.非自有供货商
                    $isSelfsupport = 1;
                } else {
                    $isSelfsupport = 2;
                }
                $supCode = $skuData["sup_code"];
            }

            $spuDatas = array(
                "spu_code" => $spuCode,
                "spu_type" => $skuData['spu_type'],
                "spu_subtype" => $skuData["spu_subtype"],
                "spu_brand" => $skuData["spu_brand"],
                "spu_storetype" => $skuData["spu_storetype"],
                "spu_name" => $skuData["spu_name"],
                "spu_from" => $skuData["spu_from"],
                "spu_norm" => $skuData["spu_norm"],
                "spu_unit" => $skuData["spu_unit"],
                "spu_mark" => $skuData["spu_mark"],
                "spu_cunit" => $spuCunit,
                "spu_img" => $spuImg,
                "is_selfsupport" => $isSelfsupport,//2019-03-21 新增 是否是自营货品 1.自营 2.直采
                "sup_code" => $supCode,
                "spu_bprice" => $skuData["spu_bprice"],
                "spu_sprice" => $skuData["spu_sprice"],
                "profit_price" => $skuData["profit_price"]
            );

            if (empty($skuData["sku_code"])) {
                $skuCode = "SK" . str_pad($i, 7, "0", STR_PAD_LEFT);
                $insertSpuSql[] = SpuDao::GetInstance("WA000001")->insert($spuDatas);
                $skuDatas = array(
                    "sku_code" => $skuCode,
                    "sku_norm" => $skuData["sku_norm"],
                    "sku_unit" => $skuData["sku_unit"],
                    "sku_mark" => $skuData["sku_mark"],
                    "spu_count" => $skuData["spu_count"],
                    "spu_code" => $spuCode,
                    "sku_status" => 1
                );
                $insertSkuSql[] = SkuDao::GetInstance("WA000001")->insert($skuDatas);
            } else {
                $spuExcelData = array(
                    "spu_name" => $skuData["spu_name"],
                    "spu_type" => $skuData["spu_type"],
                    "spu_subtype" => $skuData["spu_subtype"],
                    "spu_storetype" => $skuData["spu_storetype"],
                    "spu_brand" => $skuData["spu_brand"],
                    "spu_mark" => $skuData["spu_mark"],
                    "spu_norm" => $skuData["spu_norm"],
                    "spu_unit" => $skuData["spu_unit"],
                );
                $spCode = $skuData["spu_code"];
                $skCode = $skuData["sku_code"];
                $spuList = SpuDao::getInstance()->queryListByCode($spCode);
                $spuListArr = array(
                    "spu_name" => $spuList["spu_name"],
                    "spu_type" => $spuList["spu_type"],
                    "spu_subtype" => $spuList["spu_subtype"],
                    "spu_storetype" => $spuList["spu_storetype"],
                    "spu_brand" => $spuList["spu_brand"],
                    "spu_mark" => $spuList["spu_mark"],
                    "spu_norm" => $spuList["spu_norm"],
                    "spu_unit" => $spuList["spu_unit"],
                );
                $spuNewArr = array_diff_assoc($spuExcelData, $spuListArr);
                if (!empty($spuNewArr["spu_name"]) || !empty($spuNewArr["spu_type"]) || !empty($spuNewArr["spu_subtype"]) || !empty($spuNewArr["spu_storetype"])
                    || !empty($spuNewArr["spu_brand"]) || !empty($spuNewArr["spu_mark"]) || !empty($spuNewArr["spu_norm"]) || !empty($spuNewArr["spu_unit"])) {
                    $updateSpuSql[] = SpuDao::getInstance()->updateSpuByCode($spCode, $spuNewArr);
                }
                $skuExcelData = array(
                    "sku_norm" => $skuData["sku_norm"],
                    "sku_unit" => $skuData["sku_unit"],
                    "sku_mark" => $skuData["sku_mark"],
                );
                $skuListArr = array(
                    "sku_norm" => $spuList["sku_norm"],
                    "sku_unit" => $spuList["sku_unit"],
                    "sku_mark" => $spuList["sku_mark"],
                );
                $skuNewArr = array_diff_assoc($skuExcelData, $skuListArr);
                if (!empty($skuNewArr["sku_norm"]) || !empty($skuNewArr["sku_unit"]) || !empty($skuNewArr["sku_mark"])) {
                    $updateSkuSql[] = SkuDao::getInstance()->updateSkuByCode($skCode, $skuNewArr);
                }
            }
        }
    }
}
file_put_contents($errorMsgTxt, implode(";" . PHP_EOL, $errorMsg));
file_put_contents($insertSpuSqlTxt, implode(";" . PHP_EOL, $insertSpuSql));
file_put_contents($insertSkuSqlTxt, implode(";" . PHP_EOL, $insertSkuSql));
file_put_contents($updateSpuSqlTxt, implode(";" . PHP_EOL, $updateSpuSql));
file_put_contents($updateSkuSqlTxt, implode(";" . PHP_EOL, $updateSkuSql));
//if ($result) {
//    venus_db_commit();
//    return true;
//} else {
//    venus_db_rollback();
//    return false;
//}







