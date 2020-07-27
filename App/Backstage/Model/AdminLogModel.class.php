<?php

namespace Backstage\Model;

use Common\Model\BaseModel;

class AdminLogModel extends BaseModel {
    
    /**
     * 新增一个
     * @param array $data
     * @return boolean fasle 失败 ， int  成功 返回完整的数据
     */
    public function addData($data) {
        if (count($data) == 0) {
            return 0;
        }
        $id = $this->add($data);
        
        //内容添加完成
        return $id;
    }
    
    /**
     * 更新
     * @param array $map
     * @param array $data
     * @return boolean fasle 失败 ， int  成功 返回完整的数据
     */
    public function updateData($map, $data) {
        if (count($data) == 0) {
            return FALSE;
        }
        $res = $this->where($map)->data($data)->save();
        
        //内容添加完成
        return $res;
    }
    
    /**
     * 删除数据
     * @return boolean fasle 失败 ， int  成功 返回完整的数据
     */
    public function removeData($map) {
        $res = $this->where($map)->delete();
        
        //内容添加完成
        return $res;
    }
    
    /**
     * 查询一条数据
     * @return boolean fasle 失败 ， int  成功 返回完整的数据
     */
    public function getData($map) {
        $res = $this->where($map)->find();
        
        //内容添加完成
        return $res;
    }
    
    /**
     * 查询一条数据
     * @return boolean fasle 失败 ， int  成功 返回完整的数据
     */
    public function SelectLogData($type = 0) {
        $p = I('get.p');
        $ip = trim(I('get.ip'));
        $name = trim(I('get.name'));
        
        $page_size = C('DEFAULT_PAGE_SIZE');
        $page = empty($p) ? 1 : $p;
        if (!empty($ip)) {
            $where['ip'] = $ip;
        }
        if (!empty($name)) {
            $where['account'] = $name;
        }
        if (0 == $type) {
            $res = $this->field("al.*,FROM_UNIXTIME(al.operation_time) AS time")
                ->alias('al')
                ->where($where)
                ->order("al.operation_time desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            $res = $this->where($where)->count();
        }
        
        return $res;
    }
    
    
    /**
     * 列表查询
     * @param array $search
     * @return boolean fasle 失败 ， int  成功 返回完整的数据
     */
    public function searchByPage($search) {
        $page_size = $search['page_size']; //C('DEFAULT_PAGE_SIZE');
        $page = $search['page'];
        
        //查询条件
        $where = [];
        if ('' != $search['ip']) {
            $where['al.ip'] = $search['ip'];
        }
        if ('' != $search['account']) {
            $where['al.account'] = $search['account'];
        }
        
        if ($page_size > 0) {
            //分页信息
            $record_count = $this->alias('al')->where($where)->count();
            $page_sum = ceil($record_count / $page_size);
            $page = $page > $page_sum ? $page_sum : $page;
            $page = $page < 1 ? 1 : $page;
            $page_info = array(
                'page_sum' => $page_sum,
                'record_count' => $record_count,
                'current_page' => $page,
                'pre_page' => $page - 1,
                'next_page' => $page + 1,
            );
            
            //数据查询
            $list = $this->field("al.*,FROM_UNIXTIME(al.operation_time) AS time")
                ->alias('al')
                ->where($where)
                ->order("al.operation_time desc")
                ->page("{$page},{$page_size}")
                ->select();
        }
        else {
            $page_info = [];
            //无分页查询
            $list = $this->field("al.*,FROM_UNIXTIME(al.operation_time) AS time")
                ->alias('al')
                ->where($where)
                ->order("al.operation_time desc")
                ->select();
        }
        
        return formatResult(OP_SUCCESS, ['list' => $list, 'page_info' => $page_info]);
    }
}
