<?php

namespace Common\Model;


class StaffModel extends BaseModel {
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
            " LEFT JOIN __ADMIN_USERS__ AS auc ON auc.id=t.create_uid",
            " LEFT JOIN __ADMIN_USERS__ AS auu ON auu.id=t.update_uid",
        ];
        if (isset($search['id']) && $search['id'] !== '') {
            $where['t.id'] = trim($search['id']);
        }
        if (isset($search['job_sn']) && $search['job_sn'] !== '') {
            $search['job_sn'] = trim($search['job_sn']);
            $where['t.job_sn'] = ['LIKE', "%{$search['job_sn']}%"];
        }
        if (isset($search['name']) && $search['name'] !== '') {
            $search['name'] = trim($search['name']);
            $where['t.name'] = ['LIKE', "%{$search['name']}%"];
        }
        if (isset($search['mobile']) && $search['mobile'] !== '') {
            $search['mobile'] = trim($search['mobile']);
            $where['t.mobile'] = ['LIKE', "%{$search['mobile']}%"];
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
            $list = $this->field("t.*, auc.username AS create_uid_f, auu.username AS update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            //无分页查询
            $list = $this->field("t.*, auc.username AS create_uid_f, auu.username AS update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->select();
        }
        return formatResult(OP_SUCCESS, ['list' => $list, 'page_info' => $page_info]);
    }
}
