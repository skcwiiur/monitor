<?php

namespace Common\Model;


class MaterialInstoreModel extends BaseModel {
  /**
   * 列表查询
   * @param array $search
   * @return array
   */
  public function searchByPage($search) {
    $page_size = $search['page_size'];
    $page = $search['page'];
    $page_info = [];

    //查询条件
    $where = [];
    $join = [
      " LEFT JOIN wms_warehouse on wms_warehouse.id=t.wh_id",
      " LEFT JOIN __ADMIN_USERS__ as auc on auc.id=t.create_uid",
      " LEFT JOIN __ADMIN_USERS__ as auu on auu.id=t.update_uid",
      " LEFT JOIN __ADMIN_USERS__ as aur on aur.id=t.review_uid",
      " LEFT JOIN wms_account_relation as ar on ar.biz_bh=t.bh",
    ];
    if (isset($search['id']) && $search['id'] !== '') {
      $where['t.id'] = trim($search['id']);
    }
    if (isset($search['bh']) && $search['bh'] !== '') {
      $search['bh'] = trim($search['bh']);
      $where['t.bh'] = ['LIKE', "%{$search['bh']}%"];
    }
    if (isset($search['wh_id']) && $search['wh_id'] !== '') {
      $where['t.wh_id'] = trim($search['wh_id']);
    }
    if (isset($search['in_type']) && $search['in_type'] !== '') {
      $where['t.in_type'] = trim($search['in_type']);
    }
    if (isset($search['p_ids']) && $search['p_ids'] !== '') {
      $search['p_ids'] = trim($search['p_ids']);
      $where['t.p_ids'] = ['LIKE', "%{$search['p_ids']}%"];
    }
    if (isset($search['state']) && $search['state'] !== '') {
      $where['t.state'] = trim($search['state']);
    }
    else {
      $where['t.state'] = ['EGT', 0];
    }
    if ($page_size > 0) {
      //分页信息
      $record_count = $this->alias('t')->join($join)->where($where)->count();
      $page_sum = ceil($record_count / $page_size);
      $page = $page > $page_sum?$page_sum:$page;
      $page = $page < 1?1:$page;
      $page_info = [
        'page_sum' => $page_sum,
        'record_count' => $record_count,
        'current_page' => $page,
        'pre_page' => $page - 1,
        'next_page' => $page + 1,
      ];

      //数据查询
      $list = $this->field("ar.bill_sn,ar.id as relation_id,t.*, wms_warehouse.name as wh_id_l, auc.username as create_uid_f, auu.username as update_uid_f, aur.username as review_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
        ->alias('t')
        ->join($join)
        ->where($where)
        ->order("t.id desc")
        ->page("{$page},{$page_size}")
        ->select();
    }
    else {
      //无分页查询
      $list = $this->field("ar.bill_sn,ar.id as relation_id,t.*, wms_warehouse.name as wh_id_l, auc.username as create_uid_f, auu.username as update_uid_f, aur.username as review_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
        ->alias('t')
        ->join($join)
        ->where($where)
        ->order("t.id desc")
        ->select();
    }
    return formatResult(OP_SUCCESS, [
      'list' => $list,
      'page_info' => $page_info
    ]);
  }

  /**
   * 获取数据详情
   * @param int $id
   * @return array
   */
  public function getRecord(int $id) {
    $join = [
      " LEFT JOIN wms_warehouse as t1 on t1.id=t.wh_id",
      " LEFT JOIN wms_admin_users as t2 on t2.id=t.review_uid",
      " LEFT JOIN __ADMIN_USERS__ as auc on auc.id=t.create_uid",
      " LEFT JOIN __ADMIN_USERS__ as auu on auu.id=t.update_uid",
      " LEFT JOIN wms_account_relation as ar on ar.biz_bh=t.bh",
    ];
    $record = $this->field("ar.bill_sn,ar.id as relation_id,t.*, t1.name as wh_id_l, t2.username as review_uid_l, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
      ->alias('t')
      ->join($join)
      ->where(['t.id' => $id])
      ->find();
    if (empty($record)) {
      return array();
    }
    $record['review_time_f'] = format_time($record['review_time']);
    return $record;
  }


}
