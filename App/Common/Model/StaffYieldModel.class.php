<?php

namespace Common\Model;


class StaffYieldModel extends BaseModel {
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
            " LEFT JOIN wms_staff AS t1 ON t1.id=t.staff_id",
        ];
        if (isset($search['id']) && $search['id'] !== '') {
            $where['t.id'] = trim($search['id']);
        }
        if (isset($search['staff_id']) && $search['staff_id'] !== '') {
            $where['t.staff_id'] = trim($search['staff_id']);
        }
        if (isset($search['step']) && $search['step'] !== '') {
            $where['t.step'] = trim($search['step']);
        }
        if (isset($search['barcode']) && $search['barcode'] !== '') {
            $search['barcode'] = trim($search['barcode']);
            $where['t.barcode'] = ['LIKE', "%{$search['barcode']}%"];
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
            $list = $this->field("t.*, t1.job_sn AS staff_id_l, FROM_UNIXTIME(t.create_time) AS create_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            //无分页查询
            $list = $this->field("t.*, t1.job_sn AS staff_id_l, FROM_UNIXTIME(t.create_time) AS create_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->select();
        }

        return formatResult(OP_SUCCESS, ['list' => $list, 'page_info' => $page_info]);
    }
}
