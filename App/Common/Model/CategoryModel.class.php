<?php

namespace Common\Model;


class CategoryModel extends BaseModel {
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
            " LEFT JOIN wms_category as t1 on t1.id=t.p_id",
            " LEFT JOIN __ADMIN_USERS__ as auc on auc.id=t.create_uid",
            " LEFT JOIN __ADMIN_USERS__ as auu on auu.id=t.update_uid",
        ];
        if (isset($search['id']) && $search['id'] !== '') {
            $where['t.id'] = trim($search['id']);
        }
        if (isset($search['name']) && $search['name'] !== '') {
            $search['name'] = trim($search['name']);
            $where['t.name'] = ['LIKE', "%{$search['name']}%"];
        }
        if (isset($search['code']) && $search['code'] !== '') {
            $search['code'] = trim($search['code']);
            $where['t.code'] = ['LIKE', "%{$search['code']}%"];
        }
        if (isset($search['desc']) && $search['desc'] !== '') {
            $search['desc'] = trim($search['desc']);
            $where['t.desc'] = ['LIKE', "%{$search['desc']}%"];
        }
        if (isset($search['p_id']) && $search['p_id'] !== '') {
            $where['t.p_id'] = trim($search['p_id']);
        }
        if (isset($search['sort_order']) && $search['sort_order'] !== '') {
            $where['t.sort_order'] = trim($search['sort_order']);
        }
        if (isset($search['measure_unit']) && $search['measure_unit'] !== '') {
            $search['measure_unit'] = trim($search['measure_unit']);
            $where['t.measure_unit'] = ['LIKE', "%{$search['measure_unit']}%"];
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
            $list = $this->field("t.*, t1.name as p_id_l, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            //无分页查询
            $list = $this->field("t.*, t1.name as p_id_l, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->select();
        }
        $attrs = M('AttrDic')->field("name,id")->select();
        $attrs = get_key_array($attrs);
        foreach ($list as &$value) {
            $attr_arr = empty($value['attr']) ? array() : explode(',', $value['attr']);
            $attr_name = [];
            foreach ($attr_arr as $v) {
                $attr_name[] = $attrs[$v]['name'];
                $value['attr_arr'][] = $attrs[$v];
            }
            $value['attr_f'] = implode(',', $attr_name);
        }
        unset($value);

        return formatResult(OP_SUCCESS, ['list' => $list, 'page_info' => $page_info]);
    }
    
    /**
     * 获取分类信息
     * @param int $id
     * @return array
     */
    public function getCatInfo(int $id) {
        if (empty($id)) {
            return array();
        }
        $join = [
            " LEFT JOIN wms_category on wms_category.id=t.p_id",
            " LEFT JOIN __ADMIN_USERS__ as auc on auc.id=t.create_uid",
            " LEFT JOIN __ADMIN_USERS__ as auu on auu.id=t.update_uid",
        ];
        $cat = $this->field("t.*, wms_category.name as p_id_l, auc.username as create_uid_f, auu.username as update_uid_f, FROM_UNIXTIME(t.create_time) AS create_time_f, FROM_UNIXTIME(t.update_time) AS update_time_f")
            ->alias('t')
            ->join($join)
            ->where(['t.id' => $id])
            ->find();
        if (empty($cat)) {
            return array();
        }
        $attrs = M('AttrDic')->field("name, id, code")->select();
        $attrs = get_key_array($attrs);
        $attr_arr = empty($cat['attr']) ? array() : explode(',', $cat['attr']);
        $attr_name = $cat['attr_arr'] = [];
        foreach ($attr_arr as $v) {
            $attr_name[] = $attrs[$v]['name'];
            $attrs[$v]['values'] = M('AttrValue')->field("name, id, code, value")->where(['attr_id' => $v])->select();
            $cat['attr_arr'][$v] = $attrs[$v];
        }
        $cat['attr_f'] = implode(',', $attr_name);
        return $cat;
    }
}
