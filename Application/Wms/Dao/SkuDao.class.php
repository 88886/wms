<?php

namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;

/**
 * SKU数据
 * Class SkuDao
 * @package Wms\Dao
 */
class SkuDao extends BaseDao implements BaseDaoInterface
{

    /**
     * SkuDao constructor.
     */
    function __construct()
    {
    }
    //查询

    /**
     * @param $data
     * @return mixed
     */
    public function insert($data)
    {
//        $code = venus_unique_code("SK");
//        $data['sku_code'] = $code;
        $data['war_code'] = $this->warehousecode;
        return M("sku")->fetchSql(true)->add($data);
    }

    //查询

    /**
     * @param $code
     * @return mixed
     */
    public function queryByCode($code)
    {
        //$condition = array("sku.war_code" => $this->warehousecode, "sku.sku_code" => $code);
        $condition = array("sku.sku_code" => $code);
        return M("sku")->alias('sku')->field('*,sku.sku_code,spu.spu_code')
            ->join("JOIN wms_spu spu ON spu.spu_code = sku.spu_code")
            ->where($condition)->order('spu.spu_code desc')->fetchSql(false)->find();
    }


    //查询

    /**
     * @param $cond
     * @param int $page
     * @param int $count
     * @return mixed
     */
    public function queryListByCondition($cond, $page = 0, $count = 100)
    {
        $condition = array();
        $skujoinconds = array();
        if (isset($cond["%name%"])) {
            $skuname = str_replace(array("'", "\""), "", $cond["%name%"]);
            array_push($skujoinconds, "spu.spu_name LIKE '%{$skuname}%'");
        }
        if (isset($cond["name"])) {
            array_push($skujoinconds, "spu.spu_name = " . $cond["name"]);
        }

        if (isset($cond["abname"])) {
            $spuabname = str_replace(array("'", "\""), "", $cond["abname"]);
            array_push($skujoinconds, "spu.spu_abname LIKE '%#{$spuabname}%'");
        }

        if (isset($cond["type"])) {
            array_push($skujoinconds, "spu.spu_type = " . $cond["type"]);
        }
        if (isset($cond["subtype"])) {
            array_push($skujoinconds, "spu.spu_subtype = " . $cond["subtype"]);
        }
        if (isset($cond["status"])) {
            array_push($skujoinconds, "sku.sku_status = " . $cond["status"]);
        }
        $skujoinconds = empty($skujoinconds) ? "" : " AND " . implode(" AND ", $skujoinconds);


//        return M("sku")->alias('sku')->field('*,spu.spu_code,sku.sku_code')
//            ->join("JOIN wms_spu spu ON spu.spu_code = sku.spu_code {$skujoinconds}")
//            ->where($condition)->order('spu_subtype asc')->limit("{$page},{$count}")->fetchSql(true)->select();


        if (isset($cond["exwarcode"])) {
            $exwarcode = str_replace(array("'", "\""), "", $cond["exwarcode"]);
            array_push($projoinconds, "pro.exwar_code = '{$exwarcode}'");
            $projoinconds = empty($projoinconds) ? "" : " AND " . implode(" AND ", $projoinconds);
            return M("sku")->alias('sku')->field('*,spu.spu_code,sku.sku_code')
                ->join("JOIN wms_spu spu ON spu.spu_code = sku.spu_code {$skujoinconds}")
                ->join("JOIN wms_profit pro ON pro.spu_code = spu.spu_code {$projoinconds}")
//                ->where($condition)->order('sku.sku_code desc')->limit("{$page},{$count}")->fetchSql(false)->select();
                ->where($condition)->order('spu_subtype,spu.spu_code asc')->limit("{$page},{$count}")->fetchSql(false)->select();
        } else {
            return M("sku")->alias('sku')->field('*,spu.spu_code,sku.sku_code')
                ->join("JOIN wms_spu spu ON spu.spu_code = sku.spu_code {$skujoinconds}")
//                ->where($condition)->order('sku.sku_code desc')->limit("{$page},{$count}")->fetchSql(false)->select();
                ->where($condition)->order('spu_subtype,spu.spu_code asc')->limit("{$page},{$count}")->fetchSql(false)->select();
        }


    }

    /**
     * @param $cond
     * @return mixed
     */
    public function queryCountByCondition($cond)
    {
        $condition = array();
        $skujoinconds = array();
        if (isset($cond["%name%"])) {
            $skuname = str_replace(array("'", "\""), "", $cond["%name%"]);
            array_push($skujoinconds, "spu.spu_name LIKE '%{$skuname}%'");
        }
        if (isset($cond["name"])) {
            array_push($skujoinconds, "spu.spu_name = " . $cond["name"]);
        }
        if (isset($cond["abname"])) {
            $spuabname = str_replace(array("'", "\""), "", $cond["abname"]);
            array_push($skujoinconds, "spu.spu_abname LIKE '%#{$spuabname}%'");
        }
        if (isset($cond["type"])) {
            array_push($skujoinconds, "spu.spu_type = " . $cond["type"]);
        }
        if (isset($cond["subtype"])) {
            array_push($skujoinconds, "spu.spu_subtype = " . $cond["subtype"]);
        }
        if (isset($cond["status"])) {
            array_push($skujoinconds, "sku.sku_status = " . $cond["status"]);
        }
        $skujoinconds = empty($skujoinconds) ? "" : " AND " . implode(" AND ", $skujoinconds);

        return M("sku")->alias('sku')->field('*,spu.spu_code,sku.sku_code')
            ->join("JOIN wms_spu spu ON spu.spu_code = sku.spu_code {$skujoinconds}")
            ->where($condition)->order('sku.sku_code desc')->fetchSql(false)->count();

        /* if (isset($cond["exwarcode"])) {
             $exwarcode = str_replace(array("'", "\""), "", $cond["exwarcode"]);
             array_push($projoinconds, "pro.exwar_code = '{$exwarcode}'");
             $projoinconds = empty($projoinconds) ? "" : " AND " . implode(" AND ", $projoinconds);
             return M("sku")->alias('sku')->field('*,spu.spu_code,sku.sku_code')
                 ->join("JOIN wms_spu spu ON spu.spu_code = sku.spu_code {$skujoinconds}")
                 ->join("JOIN wms_profit pro ON pro.spu_code = spu.spu_code {$projoinconds}")
                 ->where($condition)->order('sku.sku_code desc')->fetchSql(false)->count();
         } else {
             return M("sku")->alias('sku')->field('*,spu.spu_code,sku.sku_code')
                 ->join("JOIN wms_spu spu ON spu.spu_code = sku.spu_code {$skujoinconds}")
                 ->where($condition)->order('sku.sku_code desc')->fetchSql(false)->count();
         }*/

    }

    //更新货品状态(2018-07-19 新添加)

    /**
     * @param $code
     * @param $skuStatus
     * @return mixed
     */
    public function updateStatusCodeByCode($code, $skuStatus)
    {
        $condition['sku_code'] = array('in', $code);
        $data = M("sku")
            ->where(array("war_code" => $this->warehousecode, $condition))
            ->save(array("timestamp" => venus_current_datetime(), "sku_status" => $skuStatus));
        $sql = M('sku')->_sql();
        \Think\Log::write(json_encode($sql), 'zk0307');
        return $data;
    }

    //采购车获取最新sku方法（内部）
    public function queryBySkuCode($code)
    {
        $condition = array("sku.sku_code" => $code, "sku.sku_status" => 1);
        return M("sku")->alias('sku')->field('*,sku.sku_code,spu.spu_code')
            ->join("JOIN wms_spu spu ON spu.spu_code = sku.spu_code")
            ->where($condition)->order('spu.spu_code desc')->fetchSql(false)->find();
    }

    //采购车获取最新sku方法(外部)
    public function queryByExternalSkuCode($code)
    {
        $condition = array("sku.sku_code" => $code);
        return M("sku")->alias('sku')->field('*,sku.sku_code,spu.spu_code,sku.sku_unit')
            ->join("JOIN wms_spu spu ON spu.spu_code = sku.spu_code")
            ->join("JOIN wms_skuexternal skuexternal ON skuexternal.spu_code = spu.spu_code")
            ->where($condition)->order('spu.spu_code desc')->fetchSql(false)->find();
    }

    //querySkuCodeBySpuCodeToIwms
    //此方法仅用于副仓
    public function querySkuCodeBySpuCodeToIwms($spuCode)
    {
        return M("sku")->alias('sku')->where(array("spu_code" => $spuCode))->fetchSql(false)->getField("sku_code");
    }

    public function queryGoodsListByCondition($cond)
    {
        $condition = array("goods.war_code" => $this->warehousecode);//,"sku_status" => 1

        if (isset($cond["seltype"]) && $cond["seltype"] == 3) {
            $condition["sku_count"] = array("EQ", 0);
        }
        if (isset($cond["seltype"]) && $cond["seltype"] == 2) {
            $condition["sku_count"] = array("GT", 0);
        }
        return M("sku")->alias('sku')->field('*,spu.spu_mark,spu.spu_code,sku.sku_code,spu.sup_code')
            ->join("JOIN wms_spu spu ON spu.spu_code = sku.spu_code")
            ->join("LEFT JOIN wms_goods goods ON goods.sku_code = sku.sku_code")
            ->join("LEFT JOIN wms_supplier sup ON sup.sup_code = spu.sup_code")
            ->where($condition)
            ->order('spu_subtype,spu.spu_code asc')->limit("0,100000")->fetchSql(false)->select();
    }

    public function querySpuListBySkuCode($cond)
    {//导出最新的采购价 2019-2-15

        if (isset($cond['skCode'])) {
            $condition['sku_code'] = $cond['skCode'];
        }

        if (isset($cond['supCode'])) {
            $condition['sup_code'] = $cond['supCode'];
        }
//        array("sku_code" => $code,"sup_code"=>array("eq","SU00000000000001"))
        return M("goodsbatch")->where($condition)->order('id desc')->fetchSql(false)->find();
    }

    /**
     * 仅用于根据入仓的最新采购价来更新spu的采购价
     */
    public function updateSpupriceBySkuCode($spcode, $gbprice)
    {
        return M("spu")
            ->where(array("spu_code" => $spcode))->fetchSql(true)
            ->save(array("timestamp" => venus_current_datetime(), "spu_bprice" => $gbprice));
    }

    //无库存货品数据
    public function queryNotGoodsListByCondition()
    {
        $sql = "select *,spu.spu_code,sku.sku_code,spu.sup_code from wms_sku sku 
LEFT JOIN `wms_spu` spu ON spu.spu_code = sku.spu_code 
LEFT JOIN wms_supplier sup ON sup.sup_code = spu.sup_code 
WHERE sku.sku_code NOT IN(SELECT DISTINCT goods.sku_code from wms_goods goods WHERE 1) 
AND sku.`sku_status`=1 LIMIT 0,10000";
        return M("sku")->query($sql);
    }

    //仅用户更新sku数据脚本
    public function updateSkuByCode($code, $item)
    {
        $condition = array();
        if (isset($item["sku_norm"])) {
            $condition['sku_norm'] = $item["sku_norm"];
        }
        if (isset($item["sku_unit"])) {
            $condition['sku_unit'] = $item["sku_unit"];
        }
        if (isset($item["sku_mark"])) {
            $condition['sku_mark'] = $item["sku_mark"];
        }
        return M("sku")->where(array("sku_code" => $code))->fetchSql(true)->save($condition);
    }
}