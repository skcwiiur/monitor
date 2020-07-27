<?php

namespace Common\Model;


class MaterialMoveStoreModel extends BaseModel {
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
            " LEFT JOIN wms_warehouse AS t1 ON t1.id=t.from_wh_id",
            " LEFT JOIN wms_warehouse AS t2 ON t2.id=t.to_wh_id",
            " LEFT JOIN wms_admin_users AS t3 ON t3.id=t.finish_uid",
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
        if (isset($search['from_wh_id']) && $search['from_wh_id'] !== '') {
            $where['t.from_wh_id'] = trim($search['from_wh_id']);
        }
        if (isset($search['to_wh_id']) && $search['to_wh_id'] !== '') {
            $where['t.to_wh_id'] = trim($search['to_wh_id']);
        }
        if (isset($search['note']) && $search['note'] !== '') {
            $search['note'] = trim($search['note']);
            $where['t.note'] = ['LIKE', "%{$search['note']}%"];
        }
        if (isset($search['finish_uid']) && $search['finish_uid'] !== '') {
            $where['t.finish_uid'] = trim($search['finish_uid']);
        }
        if (isset($search['finish_time']) && $search['finish_time'] !== '') {
            $where['t.finish_time'] = trim($search['finish_time']);
        }
        if (isset($search['state']) && $search['state'] !== '') {
            $where['t.state'] = trim($search['state']);
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
            $list = $this->field("t.*, t1.name as from_wh_id_l, t2.name as to_wh_id_l, t3.username as finish_uid_l, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            //无分页查询
            $list = $this->field("t.*, t1.name as from_wh_id_l, t2.name as to_wh_id_l, t3.username as finish_uid_l, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->select();
        }
        foreach ($list as &$row) {
            $row['finish_time_f'] = format_time($row['finish_time']);
        }
        unset($row);

        return formatResult(OP_SUCCESS, ['list' => $list, 'page_info' => $page_info]);
    }
    
    /**
     * 获取单条记录信息
     * @param int $id
     * @return array
     */
    public function getRecord(int $id) {
        $join = [
            " LEFT JOIN wms_warehouse AS t1 ON t1.id=t.from_wh_id",
            " LEFT JOIN wms_warehouse AS t2 ON t2.id=t.to_wh_id",
            " LEFT JOIN wms_admin_users AS t3 ON t3.id=t.finish_uid",
            " LEFT JOIN __ADMIN_USERS__ AS auc ON auc.id=t.create_uid",
            " LEFT JOIN __ADMIN_USERS__ AS auu ON auu.id=t.update_uid",
        ];
        $record = $this->field("t.*, t1.name AS from_wh_id_l, t2.name AS to_wh_id_l, t3.username AS finish_uid_l, auc.username AS create_uid_f, auu.username AS update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
            ->alias('t')
            ->join($join)
            ->where(['t.id' => $id])
            ->find();
        if (empty($record)) {
            return array();
        }
        $record['finish_time_f'] = format_time($record['finish_time']);
        
        return $record;
    }
}
