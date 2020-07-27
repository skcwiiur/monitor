<?php

namespace Backstage\Model;

use Common\Model\BaseModel;
use Org\Nx\Data;
use Think\Auth;

/**
 * 菜单操作model
 */
class AdminNavModel extends BaseModel {
    
    /**
     * 删除数据
     * @param    array $map where语句数组形式
     * @return    boolean            操作是否成功
     */
    public function deleteData($map) {
        $count = $this->where(array('pid' => $map['id']))->count();
        if ($count != 0) {
            return FALSE;
        }
        $this->where(array($map))->delete();
        
        return TRUE;
    }
    
    /**
     * 获取全部菜单
     * @param  string $type tree获取树形结构 level获取层级结构
     * @param  string $order 排序
     * @param  array $rule_list 当前权限路径
     * @return array        结构数据
     */
    public function getNavTree($type = 'tree', $order = '', $rule_list = []) {
        // 判断是否需要排序
        if (empty($order)) {
            $data = $this->select();
        }
        else {
            $data = $this->order($order)->select();
        }
        // 获取树形或者结构数据
        if ($type == 'tree') {
            $data = Data::tree($data, 'name', 'id', 'pid');
        }
        else if ($type = "level") {
            $data = Data::channelLevel($data, 0, '&nbsp;', 'id');
            // 显示有权限的菜单
            $auth = new Auth();
            $uid = get_uid();
            $is_super_admin = C('AUTH_CONFIG.SUPER_ADMIN') == $uid ? 1 : 0;
            foreach ($data as $k => $v) {
                if ($is_super_admin || $auth->check($v['mca'], $uid)) {
                    foreach ($v['_data'] as $m => $n) {
                        if ($is_super_admin || $auth->check($n['mca'], $uid)) {
                            foreach ($n['_data'] as $x => $y) {
                                if ($is_super_admin || $auth->check($y['mca'], $uid)) {
                                    if (!empty($rule_list) && in_array($y['mca'], [$rule_list['name1'], $rule_list['name2']])) {
                                        $data[$k]['_data'][$m]['_data'][$x]['_active'] = 1;
                                        $data[$k]['_data'][$m]['_active'] = 1;
                                        $data[$k]['_active'] = 1;
                                    }
                                }
                                else {
                                    unset($data[$k]['_data'][$m]['_data'][$x]);
                                }
                            }
                        }
                        else {
                            unset($data[$k]['_data'][$m]);
                        }
                    }
                }
                else {
                    // 删除无权限的菜单
                    unset($data[$k]);
                }
            }
        }
        
        // p($data);die;
        return $data;
    }
    
    
}
