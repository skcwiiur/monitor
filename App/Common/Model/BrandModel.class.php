<?php

namespace Common\Model;


class BrandModel extends BaseModel {
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
            " LEFT JOIN __ADMIN_USERS__ as auc on auc.id=t.create_uid",
            " LEFT JOIN __ADMIN_USERS__ as auu on auu.id=t.update_uid",
        ];
        if (isset($search['id']) && $search['id'] !== '') {
            $where['t.id'] = trim($search['id']);
        }
        if (isset($search['code']) && $search['code'] !== '') {
            $search['code'] = trim($search['code']);
            $where['t.code'] = ['LIKE', "%{$search['code']}%"];
        }
        if (isset($search['name']) && $search['name'] !== '') {
            $search['name'] = trim($search['name']);
            $where['t.name'] = ['LIKE', "%{$search['name']}%"];
        }
        if (isset($search['logo']) && $search['logo'] !== '') {
            $search['logo'] = trim($search['logo']);
            $where['t.logo'] = ['LIKE', "%{$search['logo']}%"];
        }
        if (isset($search['sort_order']) && $search['sort_order'] !== '') {
            $where['t.sort_order'] = trim($search['sort_order']);
        }
        if (isset($search['is_show']) && $search['is_show'] !== '') {
            $where['t.is_show'] = trim($search['is_show']);
        }
        if (isset($search['type']) && $search['type'] !== '') {
            $where['t.type'] = trim($search['type']);
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
            $list = $this->field("t.*, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            //无分页查询
            $list = $this->field("t.*, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->select();
        }

        return formatResult(OP_SUCCESS, ['list' => $list, 'page_info' => $page_info]);
    }
}
