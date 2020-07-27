<?php

namespace Common\Model;


class StockProductLogModel extends BaseModel {
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
            " LEFT JOIN wms_product AS t1 ON t1.id=t.product_id",
            " LEFT JOIN wms_warehouse AS t2 ON t2.id=t.wh_id",
            " LEFT JOIN __ADMIN_USERS__ AS auc ON auc.id=t.create_uid",
        ];
        if (isset($search['id']) && $search['id'] !== '') {
            $where['t.id'] = trim($search['id']);
        }
        if (isset($search['product_id']) && $search['product_id'] !== '') {
            $where['t.product_id'] = trim($search['product_id']);
        }
        if (isset($search['wh_id']) && $search['wh_id'] !== '') {
            $where['t.wh_id'] = trim($search['wh_id']);
        }
        if (isset($search['type']) && $search['type'] !== '') {
            $where['t.type'] = trim($search['type']);
        }
        if (isset($search['biz_id']) && $search['biz_id'] !== '') {
            $where['t.biz_id'] = trim($search['biz_id']);
        }
        if (isset($search['bh']) && $search['bh'] !== '') {
            $search['bh'] = trim($search['bh']);
            $where['t.bh'] = ['LIKE', "%{$search['bh']}%"];
        }
        if (isset($search['num']) && $search['num'] !== '') {
            $where['t.num'] = trim($search['num']);
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
            $list = $this->field("t.*, t1.sku AS product_id_l, t2.name AS wh_id_l, auc.username AS create_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            //无分页查询
            $list = $this->field("t.*, t1.sku AS product_id_l, t2.name AS wh_id_l, auc.username AS create_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->select();
        }

        return formatResult(OP_SUCCESS, ['list' => $list, 'page_info' => $page_info]);
    }
}
