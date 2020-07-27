<?php

namespace Common\Model;


class MaterialUseModel extends BaseModel {
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
            " LEFT JOIN wms_admin_users on wms_admin_users.id=t.use_uid",
            " LEFT JOIN __ADMIN_USERS__ as auc on auc.id=t.create_uid",
            " LEFT JOIN __ADMIN_USERS__ as auu on auu.id=t.update_uid",
            " LEFT JOIN __ADMIN_USERS__ as aur on aur.id=t.review_uid",
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
        if (isset($search['use_uid']) && $search['use_uid'] !== '') {
            $where['t.use_uid'] = trim($search['use_uid']);
        }
        if (isset($search['use_type']) && $search['use_type'] !== '') {
            $where['t.use_type'] = trim($search['use_type']);
        }
        if (isset($search['note']) && $search['note'] !== '') {
            $search['note'] = trim($search['note']);
            $where['t.note'] = ['LIKE', "%{$search['note']}%"];
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
            $page = $page > $page_sum ? $page_sum : $page;
            $page = $page < 1 ? 1 : $page;
            $page_info = [
                'page_sum' => $page_sum,
                'record_count' => $record_count,
                'current_page' => $page,
                'pre_page' => $page - 1,
                'next_page' => $page + 1,
            ];

            //数据查询
            $list = $this->field("t.*, wms_warehouse.name as wh_id_l, wms_admin_users.username as use_uid_l, auc.username as create_uid_f, auu.username as update_uid_f, aur.username as review_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            //无分页查询
            $list = $this->field("t.*, wms_warehouse.name as wh_id_l, wms_admin_users.username as use_uid_l, auc.username as create_uid_f, auu.username as update_uid_f, aur.username as review_uid_f FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->select();
        }

        return formatResult(OP_SUCCESS, ['list' => $list, 'page_info' => $page_info]);
    }

    /**
   * 获取数据详情
   * @param int $id
   * @return array
   */
  public function getRecord(int $id) {
    $join = [
      " LEFT JOIN wms_warehouse as t1 on t1.id=t.wh_id",
      " LEFT JOIN wms_admin_users as t2 on t2.id=t.use_uid",
      " LEFT JOIN __ADMIN_USERS__ as auc on auc.id=t.create_uid",
      " LEFT JOIN __ADMIN_USERS__ as auu on auu.id=t.update_uid",
      " LEFT JOIN __ADMIN_USERS__ as aur on aur.id=t.review_uid",
    ];
    $record = $this->field("t.*, t1.name as wh_id_l, t2.username as use_uid_l, auc.username as create_uid_f, auu.username as update_uid_f,aur.username as review_uid_l, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
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
