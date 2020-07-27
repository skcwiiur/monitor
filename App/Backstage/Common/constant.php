<?php
/**
 * Created by PhpStorm.
 * User: FHT
 * Date: 2018/7/21
 * Time: 16:53
 */

// 成品及材料类型
define('TYPE_PRODUCT', 1);  // 成品
define('TYPE_MATERIAL', 2); // 材料

// 成品库存变动类型
define('STOCK_P_INSTORE', 1);  // 入库
define('STOCK_P_OUTSTORE', 2);  // 出库
define('STOCK_P_SALE', 3);  // 销货
define('STOCK_P_RETURN', 4);  // 退货
define('STOCK_P_MOVE', 5);  // 移仓
define('STOCK_P_DISCARDED', 6);  // 报废
define('STOCK_P_YK', 7);  // 盈亏

define('STOCK_P_BIZ_TABLES', [
    STOCK_P_INSTORE => 'wms_production_instore',
    STOCK_P_OUTSTORE => 'wms_production_outstore',
    STOCK_P_SALE => '',
    STOCK_P_RETURN => '',
    STOCK_P_MOVE => 'wms_production_move_store',
    STOCK_P_DISCARDED => 'wms_production_discarded',
    STOCK_P_YK => 'wms_production_make_yk_detail',
]);

// 材料库存变动类型
define('STOCK_M_INSTORE', 1);  // 入库
define('STOCK_M_OUTSTORE', 2);  // 出库
define('STOCK_M_MOVE', 5);  // 移仓
define('STOCK_M_DISCARDED', 6);  // 报废
define('STOCK_M_YK', 7);  // 盈亏

define('STOCK_M_BIZ_TABLES', [
    STOCK_M_INSTORE => 'wms_material_instore',
    STOCK_M_OUTSTORE => 'wms_material_outstore',
    STOCK_M_MOVE => 'wms_material_move_store',
    STOCK_M_DISCARDED => 'wms_material_discarded',
    STOCK_M_YK => 'wms_material_make_yk_detail',
]);