<?php

namespace Common\Model;


class RawModel extends BaseModel {
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
            " LEFT JOIN wms_category on wms_category.id=t.cat_id",
            " LEFT JOIN wms_brand on wms_brand.id=t.brand_id",
            " LEFT JOIN __ADMIN_USERS__ as auc on auc.id=t.create_uid",
            " LEFT JOIN __ADMIN_USERS__ as auu on auu.id=t.update_uid",
        ];
        if (isset($search['id']) && $search['id'] !== '') {
            $where['t.id'] = trim($search['id']);
        }
        if (isset($search['cat_id']) && $search['cat_id'] !== '') {
            $where['t.cat_id'] = trim($search['cat_id']);
        }
        if (isset($search['raw_sn']) && $search['raw_sn'] !== '') {
            $search['raw_sn'] = trim($search['raw_sn']);
            $where['t.raw_sn'] = ['LIKE', "%{$search['raw_sn']}%"];
        }
        if (isset($search['raw_name']) && $search['raw_name'] !== '') {
            $search['raw_name'] = trim($search['raw_name']);
            $where['t.raw_name'] = ['LIKE', "%{$search['raw_name']}%"];
        }
        if (isset($search['brand_id']) && $search['brand_id'] !== '') {
            $where['t.brand_id'] = trim($search['brand_id']);
        }
        if (isset($search['warn_number']) && $search['warn_number'] !== '') {
            $where['t.warn_number'] = trim($search['warn_number']);
        }
        if (isset($search['keywords']) && $search['keywords'] !== '') {
            $search['keywords'] = trim($search['keywords']);
            $where['t.keywords'] = ['LIKE', "%{$search['keywords']}%"];
        }
        if (isset($search['is_real']) && $search['is_real'] !== '') {
            $where['t.is_real'] = trim($search['is_real']);
        }
        if (isset($search['sort_order']) && $search['sort_order'] !== '') {
            $where['t.sort_order'] = trim($search['sort_order']);
        }
        if (isset($search['is_delete']) && $search['is_delete'] !== '') {
            $where['t.is_delete'] = trim($search['is_delete']);
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
            $list = $this->field("t.*, wms_category.name as cat_id_l, wms_brand.name as brand_id_l, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            //无分页查询
            $list = $this->field("t.*, wms_category.name as cat_id_l, wms_brand.name as brand_id_l, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->select();
        }

        return formatResult(OP_SUCCESS, ['list' => $list, 'page_info' => $page_info]);
    }
}
