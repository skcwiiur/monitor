<?php

namespace Common\Model;


class MaterialMakeInventoryModel extends BaseModel {
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
            " LEFT JOIN wms_admin_users as t1 on t1.id=t.uid",
            " LEFT JOIN wms_admin_users as t2 on t2.id=t.review_uid",
            " LEFT JOIN __ADMIN_USERS__ as auc on auc.id=t.create_uid",
            " LEFT JOIN __ADMIN_USERS__ as auu on auu.id=t.update_uid",
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
        if (isset($search['make_time']) && $search['make_time'] !== '') {
            $where['t.make_time'] = trim($search['make_time']);
        }
        if (isset($search['uid']) && $search['uid'] !== '') {
            $where['t.uid'] = trim($search['uid']);
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
            $list = $this->field("t.*, wms_warehouse.name as wh_id_l, t1.username as uid_l, t2.username as review_uid_l,auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            //无分页查询
            $list = $this->field("t.*, wms_warehouse.name as wh_id_l, t1.username as uid_l, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->select();
        }

        foreach ($list as &$row) {
          $row['make_time_f'] = format_time($row['make_time']);
          $row['review_time_f'] = format_time($row['review_time']);
        }
        unset($row);

        return formatResult(OP_SUCCESS, ['list' => $list, 'page_info' => $page_info]);
    }

  /**
   * @param array $options
   * @return mixed
   */
  /**
   * 获取盘库信息
   * @param int $id
   * @return array
   */
  public function getRecord(int $id) {
    $join = [
      " LEFT JOIN wms_warehouse as t1 on t1.id=t.wh_id",
      " LEFT JOIN wms_admin_users as t2 on t2.id=t.review_uid",
      " LEFT JOIN __ADMIN_USERS__ as auc on auc.id=t.create_uid",
      " LEFT JOIN __ADMIN_USERS__ as auu on auu.id=t.update_uid",
    ];
    $record = $this->field("t.*, t1.name as wh_id_l, t2.username as review_uid_l, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
      ->alias('t')
      ->join($join)
      ->where(['t.id' => $id])
      ->find();
    if (empty($record)) {
      return array();
    }
    $record['review_time_f'] = format_time($record['review_time']);
    $record['make_time_f'] = format_time($record['make_time']);

    return $record;
  }
}
