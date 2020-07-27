<?php

namespace Common\Model;


class ProductionPlanDetailModel extends BaseModel {
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
            " LEFT JOIN wms_production_plan as t1 on t1.id=t.plan_id",
            " LEFT JOIN wms_product as t2 on t2.id=t.product_id",
            " LEFT JOIN __ADMIN_USERS__ as auc on auc.id=t.create_uid",
            " LEFT JOIN __ADMIN_USERS__ as auu on auu.id=t.update_uid",
        ];
        if (isset($search['id']) && $search['id'] !== '') {
            $where['t.id'] = trim($search['id']);
        }
        if (isset($search['plan_id']) && $search['plan_id'] !== '') {
            $where['t.plan_id'] = trim($search['plan_id']);
        }
        if (isset($search['product_id']) && $search['product_id'] !== '') {
            $where['t.product_id'] = trim($search['product_id']);
        }
        if (isset($search['expect_num']) && $search['expect_num'] !== '') {
            $where['t.expect_num'] = trim($search['expect_num']);
        }
        if (isset($search['actual_num']) && $search['actual_num'] !== '') {
            $where['t.actual_num'] = trim($search['actual_num']);
        }
        if (isset($search['note']) && $search['note'] !== '') {
            $search['note'] = trim($search['note']);
            $where['t.note'] = ['LIKE', "%{$search['note']}%"];
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
            $list = $this->field("t.*, t1.bh as plan_id_l, t1.state as plan_state, t2.sku as product_id_l, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            //无分页查询
            $list = $this->field("t.*, t1.bh as plan_id_l, t1.state as plan_state, t2.sku as product_id_l, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->select();
        }

        return formatResult(OP_SUCCESS, ['list' => $list, 'page_info' => $page_info]);
    }
}
