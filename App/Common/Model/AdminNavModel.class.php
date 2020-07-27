<?php

namespace Common\Model;


class AdminNavModel extends BaseModel {
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
        ];
        if (isset($search['id']) && $search['id'] !== '') {
            $where['t.id'] = trim($search['id']);
        }
        if (isset($search['pid']) && $search['pid'] !== '') {
            $where['t.pid'] = trim($search['pid']);
        }
        if (isset($search['name']) && $search['name'] !== '') {
            $search['name'] = trim($search['name']);
            $where['t.name'] = ['LIKE', "%{$search['name']}%"];
        }
        if (isset($search['mca']) && $search['mca'] !== '') {
            $search['mca'] = trim($search['mca']);
            $where['t.mca'] = ['LIKE', "%{$search['mca']}%"];
        }
        if (isset($search['ico']) && $search['ico'] !== '') {
            $search['ico'] = trim($search['ico']);
            $where['t.ico'] = ['LIKE', "%{$search['ico']}%"];
        }
        if (isset($search['order_number']) && $search['order_number'] !== '') {
            $where['t.order_number'] = trim($search['order_number']);
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
            $list = $this->field("t.*")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            //无分页查询
            $list = $this->field("t.*")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->select();
        }

        return formatResult(OP_SUCCESS, ['list' => $list, 'page_info' => $page_info]);
    }
}
