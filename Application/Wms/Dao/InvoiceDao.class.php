<?php

namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;

/**
 * 出仓单
 * Class InvoiceDao
 * @package Wms\Dao
 */
class InvoiceDao extends BaseDao implements BaseDaoInterface
{

    //添加数据[status,ecode,receiver,address,postal,worcode]
    /**
     * @param $item
     * @return bool
     */
    public function insert($item)
    {
        $code = venus_unique_code("IN");
        $ctime = $item["ctime"];
        $data = array(
            "inv_code" => $code,
            "inv_ctime" => empty($ctime) ? venus_current_datetime() : $ctime,
            "inv_status" => $item["status"],
            "inv_ecode" => $item["ecode"],
            "inv_receiver" => $item["receiver"],
            "inv_phone" => $item["phone"],
            "inv_address" => $item["address"],
            "inv_postal" => $item["postal"],
            "inv_type" => $item["type"],
            "return_mark" => $item["returnmark"],
            "inv_mark" => $item["mark"],
            "trace_code" => $item["tracecode"],
            "wor_code" => $item["worcode"],
            "war_code" => $this->warehousecode,
            "timestamp" => venus_current_datetime(),
        );
        return M("Invoice")->add($data) ? $code : false;
    }

    //查询

    /**
     * @param $code
     * @return mixed
     */
    public function queryByCode($code)
    {
        $condition = array("inv.war_code" => $this->warehousecode, "inv_code" => $code);
        return M("Invoice")->alias("inv")->join("LEFT JOIN wms_worker wor ON wor.wor_code = inv.wor_code")->where($condition)->find();
    }

    /**
     * @param $ecode
     * @return mixed
     */
    public function queryByEcode($ecode)
    {
        $condition = array("war_code" => $this->warehousecode, "inv_ecode" => $ecode);
        return M("Invoice")->where($condition)->select();
    }
    //查询

    /**
     * @param $condition
     * @param int $page
     * @param int $count
     * @return mixed
     */
    public function queryListByCondition($condition, $page = 0, $count = 100)
    {
        $condition = $this->conditionFilter($condition);
        return M("Invoice")->alias('inv')->field('inv.*,wor_rname')
            ->join("LEFT JOIN wms_worker wor ON wor.wor_code = inv.wor_code")
            ->where($condition)->order('inv.id desc')->limit("{$page},{$count}")->fetchSql(false)->select();
        //return M("Invoice")->alias('inv')->where($condition)->order("id desc")->limit("{$page},{$count}")->fetchSql(false)->select();
    }
    //总数

    /**
     * @param $condition
     * @return mixed
     */
    public function queryCountByCondition($condition)
    {
        $condition = $this->conditionFilter($condition);
        return M("Invoice")->alias('inv')->where($condition)->order("id desc")->fetchSql(false)->count();
    }
    //更新状态

    /**
     * @param $code
     * @param $status
     * @return mixed
     */
    public function updateStatusByCode($code, $status)
    {
        $condition = array("war_code" => $this->warehousecode, "inv_code" => $code);
        return M("Invoice")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(), "inv_status" => $status));
    }

    //更新状态和创建时间
    public function updateStatusAndCtimeByCode($code, $status)
    {
        $condition = array("war_code" => $this->warehousecode, "inv_code" => $code);
        return M("Invoice")->where($condition)->fetchSql(false)
            ->save(array("inv_ctime" => venus_current_datetime(), "timestamp" => venus_current_datetime(), "inv_status" => $status));
    }

    //删除订单
    public function deleteByCode($code)
    {
        $condition = array("war_code" => $this->warehousecode, "inv_code" => $code);
        return M("Invoice")->where($condition)->fetchSql(false)->delete();
    }
    //查询条件过滤[worcode,ctime,sctime,ectime,status]

    /**
     * @param $cond
     * @return array
     */
    private function conditionFilter($cond)
    {
        $condition = array("inv.war_code" => $this->warehousecode);
        if (isset($cond["worcode"])) {
            $condition["wor_code"] = $cond["worcode"];
        }
        if (isset($cond["ctime"])) {
            $condition["inv_ctime"] = $cond["ctime"];
        }
        if (isset($cond["sctime"]) && isset($cond["ectime"])) {
            $condition["inv_ctime"] = array(array('EGT', $cond["sctime"]), array('ELT', $cond["ectime"]), 'AND');
        } else if (isset($cond["sctime"])) {
            $condition["inv_ctime"] = array("EGT", $cond["sctime"]);
        } else if (isset($cond["ectime"])) {
            $condition["inv_ctime"] = array("ELT", $cond["ectime"]);
        }
        if (isset($cond["type"])) {
            $condition["inv_type"] = $cond["type"];
        }
        if (isset($cond["status"])) {
            $condition["inv_status"] = $cond["status"];
        }
        if (isset($cond["code"])) {
            $condition["inv_code"] = $cond["code"];
        }
        if (isset($cond["ecode"])) {
            $condition["inv_ecode"] = $cond["ecode"];
        }
        if (isset($cond["mark"])) {
            $condition["inv_mark"] = $cond["mark"];
        }

        if (isset($cond["receiver"])) {
            $receiver = $cond["receiver"];
            $condition["inv_receiver"] = array("like", "%{$receiver}%");
        }

        return $condition;
    }

    //专门用于退货追踪数据
    public function queryByInvEcodeAndSkuCode($ecode, $code)
    {
        $condition = array("gs.war_code" => $this->warehousecode, "inv_ecode" => $ecode);
        return M("Invoice")->alias('inv')->field("inv.inv_code,igs.igs_code,igs.igs_count,
        igs.sku_count igs_sku_count,igs.gs_code,igo.sku_count igo_sku_count,igo.igo_count,
        gs.gs_init,gs.gs_count,gs.gb_code,gb.rec_code,gs.sku_init gs_sku_init,gb.gb_count,
        gb.sku_count gb_sku_count,igs.spu_code,igo.igo_code")
            ->join("JOIN wms_igoodsent igs ON igs.inv_code = inv.inv_code AND inv.inv_ecode = '{$ecode}'  AND igs.sku_code = '{$code}'")
            ->join("JOIN wms_igoods igo ON igo.igo_code = igs.igo_code AND igo.sku_code = '{$code}'")
            ->join("JOIN wms_goodstored gs ON igs.gs_code = gs.gs_code AND gs.sku_code = '{$code}'")
            ->join("JOIN wms_goodsbatch gb ON gb.gb_code = gs.gb_code AND gb.sku_code = '{$code}'")
            ->join("JOIN wms_receipt rec ON rec.rec_code = gb.rec_code")
            ->join("JOIN wms_sku sku ON sku.sku_code = gb.sku_code AND gs.sku_code = '{$code}'")
            ->join("JOIN wms_spu spu ON spu.spu_code = gs.spu_code ")
            ->where($condition)->order('gs.gs_code desc')->fetchSql(false)->find();
    }

    //专门用于退货追踪数据
    public function queryByInvEcodeAndSkuCodeAndSkuInitAndSupCode($ecode, $code, $skuinit, $supCode)
    {
        $condition = array("gs.war_code" => $this->warehousecode, "inv_ecode" => $ecode, "igo.sku_count" => $skuinit);
        return M("Invoice")->alias('inv')->field("inv.inv_code,igs.igs_code,igs.igs_count,
        igs.sku_count igs_sku_count,igs.gs_code,igo.sku_count igo_sku_count,igo.igo_count,
        gs.gs_init,gs.gs_count,gs.gb_code,gb.rec_code,gs.sku_init gs_sku_init,gb.gb_count,
        gb.sku_count gb_sku_count,igs.spu_code,igo.igo_code,gs.sku_count gs_sku_count,
        sku.spu_count,igo.spu_sprice,igo.spu_pprice,igo.spu_percent")
            ->join("JOIN wms_igoodsent igs ON igs.inv_code = inv.inv_code AND inv.inv_ecode = '{$ecode}'  AND igs.sku_code = '{$code}'")
            ->join("JOIN wms_igoods igo ON igo.igo_code = igs.igo_code AND igo.sku_code = '{$code}'")
            ->join("JOIN wms_goodstored gs ON igs.gs_code = gs.gs_code AND gs.sku_code = '{$code}'")
            ->join("JOIN wms_goodsbatch gb ON gb.gb_code = gs.gb_code AND gb.sku_code = '{$code}' AND gb.sup_code='{$supCode}'")
            ->join("JOIN wms_receipt rec ON rec.rec_code = gb.rec_code")
            ->join("JOIN wms_sku sku ON sku.sku_code = gb.sku_code AND gs.sku_code = '{$code}'")
            ->join("JOIN wms_spu spu ON spu.spu_code = gs.spu_code ")
            ->where($condition)->order('igs.igs_code desc')->fetchSql(false)->select();
    }

    //20190124针对新改版分单出现两个ecode的情况
    public function queryByEcodeAndMark($ecode, $mark)
    {
        $condition = array("war_code" => $this->warehousecode, "inv_ecode" => $ecode, "inv_mark" => $mark);
        return M("Invoice")->where($condition)->find();
    }

    /**
     * @param $condition
     * @param int $page
     * @param int $count
     * @return mixed
     */
    public function queryReceiverListByCondition($cond, $page = 0, $count = 100)
    {
        $condition = $this->conditionFilter($cond);

        $condition["trace_code"] = array("like", "TK%");
        return M("Invoice")->alias("inv")->field('*')
            ->where($condition)
            ->limit("{$page},{$count}")->fetchSql(false)->select();
    }
}