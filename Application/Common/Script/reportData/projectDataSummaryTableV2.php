<?php
/**
 * Created by PhpStorm.
 * User: lilingna
 * Date: 2019/4/28
 * Time: 10:26
 * 市场部项目销售数据表
 */

//在命令行中输入 chcp 65001 回车, 控制台会切换到新的代码页,新页面输出可为中文
venus_script_begin("开始获取市场部项目销售数据表数据");


$frequencyAllData=array();
$frequencyWarData=array();
$projectSummarydata=array();
$warData=array();
$warExcelData=array();
$warFileData=array();

$frequencyAllData = getFrequencyAllDataByStimeAndEtime($stime, $etime);
$frequencyWarData = getFrequencyWarData($frequencyAllData);

$projectSummarydata = getMonthData($frequencyWarData, $stime, $etime);
$warData['市场部项目销售数据表数据'] = $projectSummarydata["war"];//此为包含退货数据的货品

//echo json_encode($data).PHP_EOL;

$warExcelData = get_war_excel_data($warData, $stime, $etime);

//echo json_encode($warExcelData).PHP_EOL;
//exit();
$warFileData = export_report($warExcelData, "0501");

$fileArr["市场部项目销售数据表"]["0501"] = $warFileData;

//$supData = getSupMonthData($frequencyWarData, $stime, $etime);
//$warSupData = $supData["war"];
//$warSupExcelData = get_war_excel_data($warSupData, $stime, $etime);
//$warSupFileData = export_report($warSupExcelData, "0501");
//echo $warFileData . PHP_EOL;
//echo "sup:" . $warSupFileData . PHP_EOL;
//exit();


function getFrequencyWarData($frequencyAllData)
{
    $frequencyData = array();
    foreach ($frequencyAllData as $frequencyAllDatum) {
        $warCode = $frequencyAllDatum['war_code'];
        $spuType = $frequencyAllDatum['spu_type'];
        $orderCtime = $frequencyAllDatum['order_ctime'];
        $dayCtime = date("Y-m-d", strtotime($orderCtime));
        if (!in_array($dayCtime, $frequencyData[$warCode][$spuType])) {
            $frequencyData[$warCode][$spuType][] = $dayCtime;
        }
    }
    $frequencyWarData = array();
    foreach ($frequencyData as $warCode => $frequencyDatum) {
        foreach ($frequencyDatum as $spuType => $times) {
            $frequencyWarData[$warCode][$spuType] = count($times);
        }
    }
    return $frequencyWarData;
}


/**
 * @param $stime
 * @param $etime
 * @return array
 * 获取订单中货品信息
 */
function getMonthData($frequencyWarData, $stime, $etime)
{
    $condition = array();
    $condition["order_ctime"] = array(
        array('EGT', $stime),
        array('ELT', $etime),
        'AND'
    );
    $condition["w_order_status"] = array('EQ', 3);
    $orderData = M("order")->where($condition)->field("order_code,order_ctime")->order("order_code desc")->limit(0, 1000000)->fetchSql(false)->select();
    $orderCodeArr = array_column($orderData, "order_code");
    $orderTimeArr = array();
    foreach ($orderData as $orderDatum) {
        $orderTimeArr[$orderDatum['order_code']] = $orderDatum['order_ctime'];
    }
    $ordergoodsCount = M("ordergoods")->alias("goods")
        ->field("*,goods.spu_code,goods.sku_code,goods.war_code,goods.supplier_code,
        goods.spu_sprice,goods.profit_price,goods.spu_bprice spu_bprice,goods.spu_count spu_count")
        ->join("left join wms_sku sku on sku.sku_code=goods.sku_code")
        ->join("left join wms_spu spu on spu.spu_code=sku.spu_code")
        ->where(array("goods.order_code" => array("in", $orderCodeArr)))
        ->count();
    $ordergoodsData = M("ordergoods")->alias("goods")
        ->field("*,goods.spu_code,goods.sku_code,goods.war_code,goods.supplier_code,
        goods.spu_sprice sprice,goods.profit_price,goods.spu_bprice bprice")
        ->join("left join wms_sku sku on sku.sku_code=goods.sku_code")
        ->join("left join wms_spu spu on spu.spu_code=sku.spu_code")
        ->where(array("goods.order_code" => array("in", $orderCodeArr)))
        ->order('goods.goods_code desc')->limit(0, $ordergoodsCount)->fetchSql(false)->select();

    $warData = array();
    $timeData = array();
    $spuTypeData = array();
    $returnDataArr = array();
    foreach ($ordergoodsData as $ordergoodsDatum) {
        if ($ordergoodsDatum['sku_count'] == 0) continue;
        $warCode = $ordergoodsDatum['war_code'];
        $dbName = C('WMS_CLIENT_DBNAME');
        $warName = M("$dbName.warehouse")->where(array("war_code" => $warCode))->getField("war_name");
        if (empty($warName)) {
            echo M("$dbName.warehouse")->where(array("war_code" => $warCode))->fetchSql(true)->getField("war_name");
            echo $warCode;
            exit();
        }
        $orderCode = $ordergoodsDatum['order_code'];
        $orderTime = date("m/d", strtotime($orderTimeArr[$orderCode]));
        $spuName = $ordergoodsDatum['spu_name'];
        $spuType = venus_spu_type_name($ordergoodsDatum['spu_type']);
        $spuBprice = $ordergoodsDatum['bprice'];
        $spuSprice = $ordergoodsDatum['sprice'];
        $spuPprice = $ordergoodsDatum["profit_price"];
        $skuCode = $ordergoodsDatum["sku_code"];
        if ($spuType == "鲜鱼水菜") continue;

        $skuCount = floatval($ordergoodsDatum['sku_count']);
        $spuCount = $ordergoodsDatum['spu_count'];
        $skuSprice = floatval(bcmul($spuSprice, $spuCount, 8));
        $skuBprice = floatval(bcmul($spuBprice, $spuCount, 8));
        $skuPprice = floatval(bcmul($spuPprice, $spuCount, 8));
        $sprice = floatval(bcmul($skuSprice, $skuCount, 8));
        $bprice = floatval(bcmul($skuBprice, $skuCount, 8));
        $pprice = floatval(bcmul($skuPprice, $skuCount, 8));

        $warData[$warName][$spuType]['money'] = floatval(bcadd($warData[$warName][$spuType]['money'], $sprice, 8));
        $warData[$warName][$spuType]['bprice'] = floatval(bcadd($warData[$warName][$spuType]['bprice'], $bprice, 8));
        $warData[$warName][$spuType]['frequency'] = $frequencyWarData[$warCode][$ordergoodsDatum['spu_type']];
    }
    $data = array(
        "war" => $warData,
    );

    return $data;
}

/**
 * @param $warData项目维度数据
 * @param $stime开始时间
 * @param $etime结束时间
 * @return array
 */
function get_war_excel_data($warDataArr, $stime, $etime)
{
    $excelData = array();
//    echo json_encode($warDataArr);
//    exit();

    foreach ($warDataArr as $sheetName => $warData) {
        $timeCell = "C2";
        $excelData[$sheetName][$timeCell] = "制表期间:" . $stime . "-" . $etime;
        $line = 6;
        foreach ($warData as $warName => $warDatum) {
            $numCell = 'A' . $line;
            $excelData[$sheetName][$numCell] = $line - 5;
            $warCell = 'B' . $line;
            $excelData[$sheetName][$warCell] = $warName;
            foreach ($warDatum as $spuType => $warItem) {
                if ($spuType == "鸡鸭禽蛋") {
                    $spriceCell = 'C' . $line;//销售额
                    $bpriceCell = 'D' . $line;//采购成本
                    $ppriceCell = 'E' . $line;//毛利
                    $pppriceCell = 'F' . $line;//毛利率
                    $frequencyCell = 'G' . $line;//订货频次
                } elseif ($spuType == "酒水饮料") {
                    $spriceCell = 'H' . $line;//销售额
                    $bpriceCell = 'I' . $line;//采购成本
                    $ppriceCell = 'J' . $line;//毛利
                    $pppriceCell = 'K' . $line;//毛利率
                    $frequencyCell = 'L' . $line;//订货频次
                } elseif ($spuType == "调味干货") {
                    $spriceCell = 'M' . $line;//销售额
                    $bpriceCell = 'N' . $line;//采购成本
                    $ppriceCell = 'O' . $line;//毛利
                    $pppriceCell = 'P' . $line;//毛利率
                    $frequencyCell = 'Q' . $line;//订货频次
                } elseif ($spuType == "米面粮油") {
                    $spriceCell = 'R' . $line;//销售额
                    $bpriceCell = 'S' . $line;//采购成本
                    $ppriceCell = 'T' . $line;//毛利
                    $pppriceCell = 'U' . $line;//毛利率
                    $frequencyCell = 'V' . $line;//订货频次
                } elseif ($spuType == "水产冻货") {
                    $spriceCell = 'W' . $line;//销售额
                    $bpriceCell = 'X' . $line;//采购成本
                    $ppriceCell = 'Y' . $line;//毛利
                    $pppriceCell = 'Z' . $line;//毛利率
                    $frequencyCell = 'AA' . $line;//订货频次
                } elseif ($spuType == "休闲食品") {
                    $spriceCell = 'AB' . $line;//销售额
                    $bpriceCell = 'AC' . $line;//采购成本
                    $ppriceCell = 'AD' . $line;//毛利
                    $pppriceCell = 'AE' . $line;//毛利率
                    $frequencyCell = 'AF' . $line;//订货频次
                } elseif ($spuType == "猪牛羊肉") {
                    $spriceCell = 'AG' . $line;//销售额
                    $bpriceCell = 'AH' . $line;//采购成本
                    $ppriceCell = 'AI' . $line;//毛利
                    $pppriceCell = 'AJ' . $line;//毛利率
                    $frequencyCell = 'AK' . $line;//订货频次
                } else {
                    echo json_encode($warDatum).PHP_EOL;
                    echo "war" . PHP_EOL;
                    echo json_encode($warName) . PHP_EOL;
                    echo json_encode($spuType) . PHP_EOL;
                    echo "此一级分类不存在" . PHP_EOL;
                    exit();
                }
                $excelData[$sheetName][$spriceCell] = $warItem['money'];
                $excelData[$sheetName][$bpriceCell] = $warItem['bprice'];
                $excelData[$sheetName][$ppriceCell] = "=$spriceCell-$bpriceCell";
                $excelData[$sheetName][$pppriceCell] = "=$ppriceCell/$spriceCell";
                $excelData[$sheetName][$frequencyCell] = $warItem['frequency'];
            }
            $totalSpriceCell = 'AL' . $line;//销售额
            $totalBpriceCell = 'AM' . $line;//采购成本
            $totalPpriceCell = 'AN' . $line;//毛利
            $totalPppriceCell = 'AO' . $line;//毛利率
            $excelData[$sheetName][$totalSpriceCell] = "=C$line+H$line+M$line+R$line+W$line+AB$line+AG$line";
            $excelData[$sheetName][$totalBpriceCell] = "=D$line+I$line+N$line+S$line+X$line+AC$line+AH$line";
            $excelData[$sheetName][$totalPpriceCell] = "=$totalSpriceCell-$totalBpriceCell";
            $excelData[$sheetName][$totalPppriceCell] = "=$totalPpriceCell/$totalSpriceCell";
            $line++;
        }

        $excelData[$sheetName]["line"] = $line - 6;
    }
    return $excelData;
}

/**
 * @param $data
 * @param $typeName
 * @return string
 */
function export_report($data, $typeName)
{
    $template = C("FILE_TPLS") . $typeName . ".xlsx";
    $saveDir = C("FILE_SAVE_PATH") . $typeName;

    $fileName = md5(json_encode($data)) . ".xlsx";
    if (file_exists($fileName)) {
        return $fileName;
    }
    vendor('PHPExcel.class');
    vendor('PHPExcel.IOFactory');
    vendor('PHPExcel.Writer.Excel2007');
    vendor("PHPExcel.Reader.Excel2007");
    $objReader = new \PHPExcel_Reader_Excel2007();
    $objPHPExcel = $objReader->load($template);    //加载excel文件,设置模板

    $templateSheet = $objPHPExcel->getSheet(0);


    foreach ($data as $sheetName => $list) {
        $line = $list['line'];
        unset($list['line']);

        $excelSheet = $templateSheet->copy();

        $excelSheet->setTitle($sheetName);
        //创建新的工作表
        $sheet = $objPHPExcel->addSheet($excelSheet);
        if ($typeName != "053" && $line > 11) {
            $addLine = $line - 11;
            $sheet->insertNewRowBefore(11, $addLine);   //在行3前添加n行
        }
        if ($typeName == "053") {

            if (isset($list['mell'])) {
                $mellList = $list['mell'];
                unset($list['mell']);
            }
            if (isset($list['insert'])) {
                foreach ($list['insert'] as $line => $addLine) {
                    $sheet->insertNewRowBefore($line, $addLine);   //在行3前添加n行
                }
                unset($list['insert']);
            }
        }
//        exit();

        foreach ($list as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        if (isset($mellList)) {
            foreach ($mellList as $mell) {
                $sheet->mergeCells($mell);
            }
        }

    }
    //移除多余的工作表
    $objPHPExcel->removeSheetByIndex(0);
    //设置保存文件名字

    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

    if (!file_exists($saveDir)) {
        mkdir("$saveDir");
    }
    $objWriter->save($saveDir . "/" . $fileName);
    return $fileName;
}

function getFrequencyAllDataByStimeAndEtime($startTime, $endTime)
{
    return M("ordergoods")
        ->query("SELECT o.`war_code`,spu.`spu_type`,o.order_ctime 
FROM `wms_ordergoods` og 
left join `wms_order` o on o.`order_code`=og.`order_code`
join `wms_spu` spu on spu.spu_code=og.spu_code
WHERE o.order_ctime>'{$startTime}' 
AND  o.order_ctime<'{$endTime}'");
}

/**
 * @param $fileDataArrList文件数组 [$typeDir->$saveFile->$fileName]
 * @param $saveNamezip包名称
 * 从多种type文件夹下载不同的表格放到同一个zip包
 */
function output_zip_file_arr($fileDataArrList, $saveName)
{
    $fileDataArr = array();
    foreach ($fileDataArrList as $typeDir => $fileData) {
        foreach ($fileData as $saveFile => $fileName) {
            $fileDataArr[$typeDir][$saveFile] = $fileName;
        }
    }
    unset($fileDataArrList);

    $zip = new \ZipArchive();
    $zipName = md5($saveName) . ".zip";
    $fileZip = C("FILE_SAVE_PATH") . "000/" . $zipName;
    if (file_exists($fileZip)) {
        unlink($fileZip);
    }
    if (!file_exists($fileZip)) {
        touch($fileZip);
        chmod($fileZip, 0777);
        if ($zip->open($fileZip, \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($fileDataArr as $typeDir => $fileData) {
                foreach ($fileData as $saveFile => $fileName) {
                    if (!empty($fileName)) {
                        $file = C("FILE_SAVE_PATH") . $typeDir . "/" . $fileName;
//                        echo $file . PHP_EOL;
                        if (file_exists($file)) {
                            $zip->addFile($file, $saveFile . ".xlsx");
                        }
                    } else {
                        continue;
                    }

                }
            }
        }
        $zip->close(); //关闭处理的zip文件
        return $fileZip;
    } else {
        return "文件创建失败，请检查对应的目录的写权限";
    }

}

function insertExportallFile($zipFile)
{
    $exportallModel = \Wms\Dao\ExportallfileDao::getInstance();
    $data = array();
    return $exportallModel->insert();
}