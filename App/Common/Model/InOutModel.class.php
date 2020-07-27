<?php

namespace Common\Model;


class InOutModel {

  /**
   * 从各类单据生成出库/入库单
   * @param $table
   * @param $bh
   */
  public function syncInOutFrom($table, $bh){
    $session_uid = session('user.id');
    $now_time = time();
    $tables = [];
    $tables[] = ['IN', 'wms_material_arrival', 1];//
    $tables[] = ['IN', 'wms_production_arrival', 1];//入库类型
    $tables[] = ['OUT', 'wms_material_use', 4];
    $tables[] = ['OUT', 'wms_production_use', 4];
    $type = '';  // IN/OUT
    $table_name = '';// 主表
    $table_detail = '';// 详情表
    $inout_type = 0;// 出库入库类型
    $store_table = '';
    foreach ($tables as $item) {
      if ($item[1] == $table) {
        $type = $item[0];
        $table_name =  $item[1];
        $table_detail = $item[1] . '_detail';
        $inout_type =  $item[2];
        break;
      }
    }
    if (empty($table_name)){
      return [
        'code' => -1,
        'message' => '表不存在' . $table
      ];
    }
    if (substr($table_name, 0, strlen('wms_production')) === 'wms_production'){
      $store_table = ($type == 'IN') ? 'wms_production_instore' : 'wms_production_outstore';
      $store_table_detail = $store_table . '_detail';
    }elseif (substr($table_name, 0, strlen('wms_material')) === 'wms_material'){
      $store_table = ($type == 'IN') ? 'wms_material_instore' : 'wms_material_outstore';
      $store_table_detail = $store_table . '_detail';
    }
    $record = M()
      ->table($table_name)
      ->where(['bh' => $bh])
      ->find();
    if (empty($record)) {
      return [
        'code' => -1,
        'message' => '数据不存在。编号:' . $bh
      ];
    }
    $exist = M()
      ->table($store_table)
      ->where(['p_ids' => $bh])
      ->find();
    if ($exist) {
      return [
        'code' => -1,
        'message' =>  ($type == 'IN') ? '操作失败，该单据编号生成的入库单已存在！' : '操作失败，该单据编号生成的出库单已存在！',
      ];
    }
    $next_bh = get_next_serial_no($store_table, 'bh');
    $insert_fields = [
      'bh' => $next_bh,
      'wh_id' => $record['wh_id'],
      'p_ids' => $bh,
      'state' => 0, //未审核
      'create_uid' => $session_uid,
      'create_time' => $now_time,
      'update_uid' => $session_uid,
      'update_time' => $now_time
    ];
    if ($type == 'IN') {
      $insert_fields['in_type'] = $inout_type;// 入库类型:0其他,1采购入库,2生产入库,3退货入库,4领用回库,5移仓
      if (substr($table_name, 0, strlen('wms_production')) === 'wms_production'){
        $pid_store = D('ProductionInstore')->addData($insert_fields);
      }elseif (substr($table_name, 0, strlen('wms_material')) === 'wms_material'){
        $pid_store = D('MaterialInstore')->addData($insert_fields);
      }
    }else{
      $insert_fields['out_type'] = $inout_type;
      if (substr($table_name, 0, strlen('wms_production')) === 'wms_production'){
        $pid_store = D('ProductionOutstore')->addData($insert_fields);
      }elseif (substr($table_name, 0, strlen('wms_material')) === 'wms_material'){
        $pid_store = D('MaterialOutstore')->addData($insert_fields);
      }
    }
    // 清空详情表
    $exist = M()
      ->table($store_table_detail)
      ->where(['bh' => $next_bh])
      ->select();
    if ($exist) {
      M()->table($store_table_detail)
        ->delete(['bh' => $next_bh]);
    }
    //查询detail表，循环插入instore详情表。
    $result = M()
      ->table($table_detail)
      ->where(['bh' => $bh])
      ->select();
    foreach ($result as $item) {
      $detail_fields = [
        'pid' => $pid_store,
        'bh' => $next_bh,
        'num' => $item['num'],
        'create_uid' => $session_uid,
        'create_time' => $now_time,
        'update_uid' => $session_uid,
        'update_time' => $now_time
      ];
      if ($store_table == 'wms_material_instore'){
        $detail_fields['material_id'] = $item['material_id'];
        D('MaterialInstoreDetail')->addData($detail_fields);
      }
      if ($store_table == 'wms_production_instore'){
        $detail_fields['product_id'] = $item['product_id'];
        D('ProductionInstoreDetail')->addData($detail_fields);
      }
      if ($store_table == 'wms_material_outstore'){
        $detail_fields['material_id'] = $item['material_id'];
        D('MaterialOutstoreDetail')->addData($detail_fields);
      }
      if ($store_table == 'wms_production_outstore'){
        $detail_fields['product_id'] = $item['product_id'];
        D('ProductionOutstoreDetail')->addData($detail_fields);
      }
    }
    return [
      'code' => 1,
      'message' => 'success！',
    ];
  }

  /**
   * 根据当前单据查询是否已经生成出库入库单
   * @param $table
   * @param $bh
   */
  public function searchInOutInfo($table, $bh){
    $tables = [];
    $tables[] = ['IN', 'wms_material_arrival', 1];//
    $tables[] = ['IN', 'wms_production_arrival', 1];//入库类型
    $tables[] = ['OUT', 'wms_material_use', 4];
    $tables[] = ['OUT', 'wms_production_use', 4];
    $type = '';
    $store_table = '';
    foreach ($tables as $item) {
      if ($item[1] == $table) {
        $type = $item[0];
        $table_name =  $item[1];
        break;
      }
    }
    if (empty($table_name)){
      return [
        'code' => -1,
        'message' => '表不存在' . $table
      ];
    }
    if (substr($table_name, 0, strlen('wms_production')) === 'wms_production'){
      $store_table = ($type == 'IN') ? 'wms_production_instore' : 'wms_production_outstore';
    }elseif (substr($table_name, 0, strlen('wms_material')) === 'wms_material'){
      $store_table = ($type == 'IN') ? 'wms_material_instore' : 'wms_material_outstore';
    }
    $record = M()
        ->table($store_table)
        ->where(['p_ids' => $bh])
        ->find();
    //TODO :state > 0
    if ($record) {
      return [
        'code' => 1,
        'data' => $record,
        'table' => $store_table
      ];
    }
    return [
      'code' => -1,
      'message' => '表不存在' . $table
    ];
  }
}
