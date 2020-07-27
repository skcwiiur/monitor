<?php

namespace Common\Model;


class StockMaterialModel extends BaseModel {
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
            " LEFT JOIN wms_material AS t1 ON t1.id=t.material_id",
            " LEFT JOIN wms_warehouse AS t2 ON t2.id=t.wh_id",
        ];
        if (isset($search['id']) && $search['id'] !== '') {
            $where['t.id'] = trim($search['id']);
        }
        if (isset($search['material_id']) && $search['material_id'] !== '') {
            $where['t.material_id'] = trim($search['material_id']);
        }
        if (isset($search['wh_id']) && $search['wh_id'] !== '') {
            $where['t.wh_id'] = trim($search['wh_id']);
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
            $list = $this->field("t.*, t1.sku AS material_id_l, t2.name AS wh_id_l")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            //无分页查询
            $list = $this->field("t.*, t1.sku AS material_id_l, t2.name AS wh_id_l")
                ->alias('t')
                ->join($join)
                ->where($where)
                ->order("t.id desc")
                ->select();
        }

        return formatResult(OP_SUCCESS, ['list' => $list, 'page_info' => $page_info]);
    }
    
    /**
     * 库存变更
     * @param int $material_id 货品id
     * @param int $wh_id 仓库id
     * @param int $num 库存变更数量
     * @param int $type 变更类型
     * @param int $biz_id 关联记录id
     *
     * @return array
     */
    public function change($material_id, $wh_id, $num, $type, $biz_id) {
        $material_id = (int)$material_id;
        $wh_id = (int)$wh_id;
        $num = (int)$num;
        $type = (int)$type;
        $biz_id = (int)$biz_id;
        if (empty($material_id) || empty($wh_id) || empty($num) || empty($type) || empty($biz_id)) {
            return formatResult(OP_FAIL, [], "参数不全");
        }
        $this->startTrans();
        $material = M('Material')->where(['id'=>$material_id])->find();
        if (empty($material)) {
            $this->rollback();
            return formatResult(OP_FAIL, [], "货品不存在！");
        }
        if ($material['is_delete'] == 1) {
            $this->rollback();
            return formatResult(OP_FAIL, [], "{$material['sku']}货品已被删除！");
        }
        $warehouse = M('Warehouse')->where(['id'=>$wh_id])->find();
        if (empty($warehouse)) {
            $this->rollback();
            return formatResult(OP_FAIL, [], "仓库不存在！");
        }
        if (empty(STOCK_M_BIZ_TABLES[$type])) {
            $this->rollback();
            return formatResult(OP_FAIL, [], "类型错误！");
        }
        $biz_table = STOCK_M_BIZ_TABLES[$type];
        $biz = M()->table($biz_table)->find($biz_id);
        if (empty($biz)) {
            $this->rollback();
            return formatResult(OP_FAIL, [], "业务数据关联失败！");
        }
        $id = $this->where(['material_id' => $material_id, 'wh_id' => $wh_id])->getField('id');
        if (empty($id)) {
            // 新增记录
            if ($num < 0) {
                $this->rollback();
                return formatResult(OP_FAIL, [], "{$material['sku']}库存变更失败，库存小于0！");
            }
            $this->addData([
                'material_id' => $material_id,
                'wh_id' => $wh_id,
                'num' => $num,
            ]);
            // 记录日志
            D('StockMaterialLog')->addData([
                'material_id' => $material_id,
                'wh_id' => $wh_id,
                'num' => $num,
                'origin_num' => 0,
                'type' => $type,
                'biz_id' => $biz_id,
                'bh' => $biz['bh'] ?: '',
                'create_uid' => session('user.id'),
                'create_time' => time(),
            ]);
            $this->commit();
            return formatResult(OP_SUCCESS);
        }
        else{
            // 主键加锁
            $old_data = $this->lock(TRUE)->where(['id' => $id])->find();
            // 库存变更
            if ($old_data['num'] + $num < 0) {
                $this->rollback();
                return formatResult(OP_FAIL, [], "{$material['sku']}库存变更失败，库存小于0！");
            }
            $this->where(['id' => $id])->setField('num', ['exp', "num + ({$num})"]);
            // 记录日志
            D('StockMaterialLog')->addData([
                'material_id' => $material_id,
                'wh_id' => $wh_id,
                'num' => $num,
                'origin_num' => $old_data['num'],
                'type' => $type,
                'biz_id' => $biz_id,
                'bh' => $biz['bh'] ?: '',
                'create_uid' => session('user.id'),
                'create_time' => time(),
            ]);
            $this->commit();
            return formatResult(OP_SUCCESS);
        }
    }
}
