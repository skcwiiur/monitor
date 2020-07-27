<?php

namespace Monitor\Controller;

use Monitor\Model;
use Drupal\Core\Database\Query\Condition;

//define('API_HOST', 'https://testapi.jyfmsc.com');
define('API_HOST', 'https://api.jyfmsc.com');

class APIController extends APIBaseController
{
    /**
     * 登录接口.
     */
    public function push()
    {
        var_dump($this->param);

        $mac = $this->param['mac']; //mac地址
        $hostname = $this->param['hostname'];

        $cpu = $this->param['cpu']; //cpu 使用率
        $disk = $this->param['disk']; //磁盘使用率
        $memsum = $this->param['memsum']; //内存总数
        $memused = $this->param['memused']; //内存使用的
        $mem = $this->param['mem']; //内存使用率
       
        $ltime = $this->param['ltime']; //服务器时间
        
        $ip = get_client_ip();

        $database = getDB();

        $insertFields = [
            'cpu' => $cpu,
            'disk' => $disk,
            'memsum' => $memsum,
            'memused' => $memused,
            'mem' => $mem,
            'mac' => $mac,
            'ltime' => $ltime,
            'ip' => $ip,
            'hostname' => $hostname,
            'create_time' => time(),
        ];
        $query = $database->insert('monitor_linux');
        $query->fields(array_keys($insertFields));
        $query->values(array_values($insertFields));
        $query->execute();
        $this->makeJsonResult(['code' => 1, 'message' => 'success.']);
    }

    /**
     * 检查是否登录.
     */
    public function check_login()
    {
        $this->makeJsonResult(['code' => 1, 'message' => 'success.']);
    }

    /**
     * 员工接口.
     */
    public function user()
    {
        // 骑手只能看骑手
        // 售票员只能看售票员
        $search = $this->param['search'];
        if (empty($search)) {
            $search = $this->param['keyword'];
        }
        $role_type = isset($this->param['role_type']) ? (int) $this->param['role_type'] : -1;
        $database = getDB();
        $query = $database->select('user_users', 't');
        $query->fields('t', ['uid', 'nick_name', 'role_type']);
        if (!empty($search)) {
            $or = new Condition('OR');
            $or->condition('t.nick_name', '%'.$database->escapeLike($search).'%', 'LIKE')
                ->condition('t.uid', $database->escapeLike($search).'%', 'LIKE');
            $query->condition($or);
        }
        if ($role_type > 0) {
            $query->condition('t.role_type', $role_type);
        }
        $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        $this->makeJsonResult(['code' => 1, 'message' => 'success.', 'data' => $result]);
    }

    /**
     * 获取用户权限.
     *
     * @param $uid
     * @param string $type
     *
     * @return string
     */
    private function getUserAccessRight($uid, $type = 'array')
    {
        // 判断角色。如果是固定角色，返回固定权限

        //拿到基本的权限
        $database = getDB();
        $query = $database->select('access_right', 't');
        $query->fields('t', ['pcode', 'pname']);
        $query->condition('t.is_active', 1);
        $base_rights = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        //拿到拥有的权限
        $query = $database->select('access_user_right', 't');
        $query->fields('t', ['uid', 'pcode']);
        $query->condition('t.is_active', 1);
        $query->condition('t.uid', $uid);
        $rights = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($base_rights as $k => $base_item) {
            if ($uid == 'admin') {
                $base_rights[$k]['isAuth'] = 1;
                continue;
            }
            $base_rights[$k]['isAuth'] = 0;
            foreach ($rights as $item) {
                if ($item['pcode'] == $base_item['pcode']) {
                    $base_rights[$k]['isAuth'] = 1;
                }
            }
        }
        if ($type == 'array') {
            return $base_rights;
        } else {
            $mstr = '';
            foreach ($base_rights as $item) {
                if ($item['isAuth'] == 1) {
                    if (empty($mstr)) {
                        $mstr .= $item['pcode'];
                    } else {
                        $mstr .= ',';
                        $mstr .= $item['pcode'];
                    }
                }
            }
        }

        return $mstr;
    }

    /**
     * 新增员工.
     */
    public function user_add()
    {
        if (!$this->access_check('P0006')) {
            $this->makeJsonResult(['code' => -1, 'message' => 'No Permission.']);
        }
        $uid = $this->param['uid'];
        $pwd = $this->param['pwd'];
        $role_type = $this->param['role_type'];
        $nick_name = $this->param['nick_name'];
        if (empty($nick_name)) {
            $nick_name = $uid;
        }
        $database = getDB();
        $query = $database->select('user_users', 't')
            ->fields('t');
        $query->condition('t.uid', $uid);
        $record = $query->execute()->fetchAssoc();
        if ($record) {
            $this->makeJsonResult(['code' => -1, 'message' => '用户已存在.']);
        }
        $salt = $this->rand_number(1, 9999);
        $insertFields = [
            'uid' => $uid,
            'salt' => $salt,
            'pwd' => md5($pwd.$salt),
        ];
        if ($role_type) {
            $insertFields['role_type'] = $role_type;
        }
        if ($nick_name) {
            $insertFields['nick_name'] = $nick_name;
        }
        $query = $database->insert('user_users');
        $query->fields(array_keys($insertFields));
        $query->values(array_values($insertFields));
        $query->execute();
        $this->makeJsonResult(['code' => 1, 'message' => 'success.']);
    }

    /**
     * 更新员工.
     */
    public function user_edit()
    {
        if (!$this->access_check('P0007')) {
            $this->makeJsonResult(['code' => -1, 'message' => 'No Permission.']);
        }
        $uid = $this->param['uid'];
        $state = $this->param['state'];
        $nick_name = $this->param['nick_name'];
        $role_type = isset($this->param['role_type']) ? (int) $this->param['role_type'] : -1;
        $database = getDB();
        $query = $database->select('user_users', 't')
            ->fields('t');
        $query->condition('t.uid', $uid);
        $record = $query->execute()->fetchAssoc();
        if (!$record) {
            $this->makeJsonResult(['code' => -1, 'message' => '用户不存在.']);
        }
        $pwd = $this->param['pwd'];
        if (!empty($pwd)) {
            if (strlen($pwd) < 6) {
                $this->makeJsonResult(['code' => -1, 'message' => '密码不能低于5位.']);
            }
            $salt = $this->rand_number(1, 9999);
            $query = $database->update('user_users');
            $query->fields([
                'salt' => $salt,
                'pwd' => md5($pwd.$salt),
            ]);
            $query->condition('uid', $uid);
            $query->execute();
        }

        $update_fields = [];
        if ($nick_name) {
            $update_fields['nick_name'] = $nick_name;
        }
        if ($state) {
            $update_fields['state'] = $state;
        }
        if ($role_type > 0) {
            $update_fields['role_type'] = $role_type;
        }
        if (!empty($update_fields)) {
            $query = $database->update('user_users');
            $query->fields($update_fields);
            $query->condition('uid', $uid);
            $query->execute();
        }
        $this->makeJsonResult(['code' => 1, 'message' => 'success.']);
    }

    /**
     * 删除员工.
     */
    public function user_del()
    {
        if (!$this->access_check('P0009')) {
            $this->makeJsonResult(['code' => -1, 'message' => 'No Permission.']);
        }
        $uid = $this->param['uid'];
        if ($uid == 'admin') {
            $this->makeJsonResult(['code' => -1, 'message' => 'No authority.']);
        }
        $database = getDB();
        $query = $database->select('user_users', 't')
            ->fields('t');
        $query->condition('t.uid', $uid);
        $record = $query->execute()->fetchAssoc();
        if (!$record) {
            $this->makeJsonResult(['code' => -1, 'message' => '用户不存在.']);
        }
        $query = $database->delete('user_users');
        $query->condition('uid', $uid);
        $query->execute();
        $this->makeJsonResult(['code' => 1, 'message' => 'success.']);
    }

    /**
     * 用户权限.
     */
    public function user_access_right()
    {
        $uid = $this->param['uid'];
        $database = getDB();
        $query = $database->select('user_users', 't')
            ->fields('t');
        $query->condition('t.uid', $uid);
        $record = $query->execute()->fetchAssoc();
        if (!$record) {
            $this->makeJsonResult(['code' => -1, 'message' => '用户不存在.']);
        }
        $access_right = $this->getUserAccessRight($uid, 'array');
        $this->makeJsonResult(['code' => 1, 'message' => 'success.', 'data' => $access_right]);
    }

    /**
     * 更新.
     */
    public function access_right_edit()
    {
        $uid = $this->param['uid'];
        $access_right = isset($this->param['access_right']) ? $this->param['access_right'] : '-1';
        if ($access_right == '-1' || empty($uid)) {
            $this->makeJsonResult(['code' => -1, 'message' => '参数不正确.']);
        }
        $database = getDB();
        $query = $database->select('user_users', 't')
            ->fields('t');
        $query->condition('t.uid', $uid);
        $record = $query->execute()->fetchAssoc();
        if (!$record) {
            $this->makeJsonResult(['code' => -1, 'message' => '用户不存在.']);
        }
        if (is_string($access_right)) {
            $access_right = explode(',', $access_right);
        }
        if (!is_array($access_right)) {
            $this->makeJsonResult(['code' => -1, 'message' => '参数不正确.']);
        }
        // 确保$access_right里面每个都是权限里存在的
        if (count($access_right) > 0) {
            $query = $database->select('access_right', 't');
            $query->fields('t', ['pcode']);
            $query->condition('t.pcode', $access_right, 'IN');
            $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
            $arr = [];
            foreach ($result as $item) {
                $arr[] = $item['pcode'];
            }
            $access_right = $arr;
        }
        $query = $database->update('access_user_right');
        $query->fields(['is_active' => 0]);
        $query->condition('uid', $uid);
        $query->execute();
        foreach ($access_right as $pcode) {
            $query = $database->select('access_user_right', 't')
                ->fields('t');
            $query->condition('t.uid', $uid);
            $query->condition('t.pcode', $pcode);
            $record = $query->execute()->fetchAssoc();
            if (!$record) {
                $insert_fields = [
                    'uid' => $uid,
                    'pcode' => $pcode,
                    'is_active' => 1,
                ];
                $query = $database->insert('access_user_right');
                $query->fields(array_keys($insert_fields));
                $query->values(array_values($insert_fields));
                $query->execute();
            } else {
                $update_fields = [
                    'uid' => $uid,
                    'pcode' => $pcode,
                    'is_active' => 1,
                ];
                $query = $database->update('access_user_right');
                $query->fields($update_fields);
                $query->condition('id', (int) $record['id']);
                $query->execute();
            }
        }
        $access_right = $this->getUserAccessRight($uid, 'array');
        $this->makeJsonResult(['code' => 1, 'message' => 'success.', 'data' => $access_right]);
    }

    /**
     * @param $pcode
     *
     * @return bool
     */
    public function access_check($pcode)
    {
        $uid = $this->current_uid();
        if (empty($uid)) {
            return false;
        }
        $database = getDB();
        $query = $database->select('access_user_right', 't')
            ->fields('t');
        $query->condition('t.uid', $uid);
        $query->condition('t.pcode', $pcode);
        $record = $query->execute()->fetchAssoc();
        if ($record && $record['is_active'] == 1) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function current_uid()
    {
        return $this->token_uid;
    }

    /**
     * 取票的等待状态
     */
    public function get_wait_status()
    {
        $checkout_type_code = $this->param['checkout_type_code']; //CHECK_TICKET_HORSE、CHECK_TICKET_BOAT
        // 查每个票种类等待人数
        $database = getDB();
        $today_start = strtotime(date('Y-m-d'));
        $query = $database->select('checkout_ticket', 't');
        $query->addExpression('COUNT(*)', 'cnt');
        $query->addExpression('call_type', 'call_type');
        $query->condition('t.checkout_type_code', $checkout_type_code);
        $query->condition('t.create_time', $today_start, '>');
        $query->condition('t.call_type', '', '<>');
        $query->condition('t.state', [2, 3], 'in');
        $query->groupBy('t.call_type');
        $result_data = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        $this->makeJsonResult(['code' => 1, 'message' => 'success.', 'data' => $result_data]);
    }

    /**
     * 后台查看票列表.
     */
    public function get_ticket_list()
    {
        $search = $this->param['search'];
        if (empty($search)) {
            $search = $this->param['keyword'];
        }
        $search_date = $this->param['search_date'];
        if (empty($search_date)) {
            $search_date = date('Y-m-d');
        }
        $search_state = (int) $this->param['search_state'];
        if (empty($search_state)) {
            $search_state = 0;
        }
        $page = (int) $this->param['page'];
        $page_size = (int) $this->param['page_size'];
        if (empty($page_size)) {
            $page_size = 10;
        }

        $start_time = strtotime($search_date);
        $end_time = $start_time + 24 * 60 * 60;

        $database = getDB();
        $query = $database->select('checkout_ticket', 't')
            ->fields('t');
        $query->condition('t.ticket_tag', -1, '<>');
        $query->condition('t.create_time', $start_time, '>');
        $query->condition('t.create_time', $end_time, '<');
        if (!empty($search)) {
            $or = new Condition('OR');
            $or->condition('t.origin_ticket', '%'.$database->escapeLike($search).'%', 'LIKE')
                ->condition('t.create_uid', $database->escapeLike($search).'%', 'LIKE');
            $query->condition($or);
        }
        if ($search_state > 0) {
            $query->condition('t.state', $search_state);
        }
        // 数据总条数
        $cloneQuery = clone $query;
        $cloneResult = $cloneQuery->execute();
        $cloneResult->allowRowCount = true;
        $total = $cloneResult->rowCount();
        if ($page > 0) {
            $query->range();
            $totalPage = ($total % $page_size == 0) ? $total / $page_size : floor($total / $page_size) + 1;
            if ($page > $totalPage) {
                $page = $totalPage;
            }
            $offset = ($page - 1) * $page_size;
            $query->range($offset, $page_size);
        }
        $result_data = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        $this->makeJsonResult([
            'code' => 1,
            'message' => 'success.',
            'data' => $result_data,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'total' => $total,
        ]);
    }

    /**
     * 获取自定义票.
     */
    public function get_diy_ticket()
    {
        $search = $this->param['search'];
        if (empty($search)) {
            $search = $this->param['keyword'];
        }
        $search_date = $this->param['search_date'];
        if (empty($search_date)) {
            $search_date = date('Y-m-d');
        }
        $search_state = (int) $this->param['search_state'];
        if (empty($search_state)) {
            $search_state = 0;
        }
        $page = (int) $this->param['page'];
        $page_size = (int) $this->param['page_size'];
        if (empty($page_size)) {
            $page_size = 10;
        }
        $start_time = strtotime($search_date);
        $end_time = $start_time + 24 * 60 * 60;
        $database = getDB();
        $query = $database->select('checkout_ticket', 't')
            ->fields('t');
        $query->condition('t.ticket_tag', -1);
        $query->condition('t.create_time', $start_time, '>');
        $query->condition('t.create_time', $end_time, '<');
        if (!empty($search)) {
            $or = new Condition('OR');
            $or->condition('t.origin_ticket', '%'.$database->escapeLike($search).'%', 'LIKE')
                ->condition('t.create_uid', $database->escapeLike($search).'%', 'LIKE');
            $query->condition($or);
        }
        if ($search_state > 0) {
            $query->condition('t.state', $search_state);
        }
        // 数据总条数
        $cloneQuery = clone $query;
        $cloneResult = $cloneQuery->execute();
        $cloneResult->allowRowCount = true;
        $total = $cloneResult->rowCount();
        if ($page > 0) {
            $query->range();
            $totalPage = ($total % $page_size == 0) ? $total / $page_size : floor($total / $page_size) + 1;
            if ($page > $totalPage) {
                $page = $totalPage;
            }
            $offset = ($page - 1) * $page_size;
            $query->range($offset, $page_size);
        }
        $result_data = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        $this->makeJsonResult([
            'code' => 1,
            'message' => 'success.',
            'data' => $result_data,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'total' => $total,
        ]);
    }

    public function create_diy_ticket()
    {
        if (!$this->access_check('P0001')) {
            $this->makeJsonResult(['code' => -1, 'message' => 'No Permission.']);
        }
        $job_number = $this->current_uid();
        $today_start = strtotime(date('Y-m-d'));
        $origin_ticket = $this->param['origin_ticket'];
        $origin_ticket = strtoupper($origin_ticket);
        $checkout_type_code = $this->param['checkout_type_code']; //CHECK_TICKET_HORSE、CHECK_TICKET_BOAT
        // 先查询是否已经生成对应的数据
        $database = getDB();
        $query = $database->select('checkout_ticket', 't')
            ->fields('t');
        $query->condition('t.origin_ticket', $origin_ticket);
        $query->condition('t.create_time', $today_start, '>');
        $result_data = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        if ($result_data) {
            foreach ($result_data as $k => $v) {
                $wait_num = $this->get_wait_num($v['checkout_type_code'], $v['call_code']);
                $result_data[$k]['wait_num'] = $wait_num;
            }
            $this->makeJsonResult(['code' => 1, 'message' => 'success.', 'data' => $result_data]);
        }
        $insert_fields = [
            'origin_ticket' => $origin_ticket,
            'checkout_type_code' => $checkout_type_code,
            'ticket_tag' => -1,
            'call_code' => '',
            'call_type' => '',
            'state' => 1,
            'create_uid' => $job_number,
            'create_time' => time(),
            'update_uid' => $job_number,
            'update_time' => time(),
        ];
        $query = $database->insert('checkout_ticket');
        $query->fields(array_keys($insert_fields));
        $query->values(array_values($insert_fields));
        $query->execute();

        $query = $database->select('checkout_ticket', 't')
            ->fields('t');
        $query->condition('t.origin_ticket', $origin_ticket);
        $query->condition('t.create_time', $today_start, '>');
        $result_data = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        if ($result_data) {
            foreach ($result_data as $k => $v) {
                $wait_num = $this->get_wait_num($v['checkout_type_code'], $v['call_code']);
                $result_data[$k]['wait_num'] = $wait_num;
            }
            $this->makeJsonResult(['code' => 1, 'message' => 'success.', 'data' => $result_data]);
        }
        $this->makeJsonResult(['code' => -1, 'message' => '检票失败,请再试一次.']);
    }

    /**
     * 查询票信息（扫码之后的）.
     */
    public function scan_ticket_info()
    {
        if (!$this->access_check('P0001')) {
            $this->makeJsonResult(['code' => -1, 'message' => 'No Permission.']);
        }
        $udid = $this->param['udid'];
        $udid = empty($udid) ? '' : $udid;
        $today_start = strtotime(date('Y-m-d'));
        $job_number = $this->current_uid();
        $origin_ticket = $this->param['origin_ticket'];
        $origin_ticket = strtoupper($origin_ticket);
        $checkout_type_code = $this->param['checkout_type_code']; //CHECK_TICKET_HORSE、CHECK_TICKET_BOAT
        if (empty($origin_ticket)) {
            $this->makeJsonResult(['code' => -1, 'message' => '参数不正确.']);
        }
        // 先查询是否已经生成对应的数据
        $database = getDB();
        $query = $database->select('checkout_ticket', 't')
            ->fields('t');
        $query->condition('t.origin_ticket', $origin_ticket);
//        $query->condition('t.create_time', 0, '>');
        $query->condition('t.state', 7, '<>');
        $result_data = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

        // 查code
        if (!$result_data) {
            $query = $database->select('checkout_ticket', 't')
                ->fields('t');
            $query->condition('t.call_code', $origin_ticket);
            $query->condition('t.create_time', $today_start, '>');
            $query->condition('t.state', 7, '<>');
            $result_data = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        }
        if ($result_data) {
            foreach ($result_data as $k => $v) {
                $wait_num = $this->get_wait_num($v['checkout_type_code'], $v['call_code']);
                $result_data[$k]['wait_num'] = $wait_num;
            }
            $this->makeJsonResult(['code' => 1, 'message' => 'success.', 'data' => $result_data]);
        }
        $query = $database->select('common_dic', 't')
            ->fields('t');
        $query->condition('t.d_code', 'CHECK_TICKET_IDS');
        $query->condition('t.d_item_code', $checkout_type_code);
        $record = $query->execute()->fetchAssoc();
        if (!$record) {
            $this->makeJsonResult(['code' => -1, 'message' => '检票失败,票种不对.'.$checkout_type_code]);
        }
        $retTxt = $this->fetch_ticket_cache($origin_ticket);
        if (empty($retTxt)) {
            $ticket_ids = $record['d_item_value'];
//            $ticket_ids = explode(',', $ticket_ids);
            $url = API_HOST.'/admin/ticket/call_number_tickets';
            // -- mock start
//            $job_number = 'root';
            // -- mock end
            //ticket_ids=[268,289]&job_number=root&code=3pw2wavq&sign=D99C169FCE06CD3C93FF0F29419B6A54
            $params = [];
            $params['ticket_ids'] = '[268,289,340]';
            $params['job_number'] = $job_number;
            $params['code'] = $origin_ticket;
            $signUtil = new Model\SignModel();
            $sign = $signUtil->makeSignAdmin($params);
            $params['sign'] = $sign;
            $ret = $this->curl_get($url, $params);
            if ($ret['status'] != 1) {
                $this->makeJsonResult(['code' => -1, 'message' => '检票失败.']);
            }
            if ($ret['status'] == 1) {
                $retTxt = $ret['data'];
                $data = json_decode($retTxt, true);
                if ($data['code'] != 200) {
                    if ($data['message'] == '检票时间已过') {
                        $this->makeJsonResult(['code' => -1, 'message' => '门票已过期']);

                        return;
                    }
                    $this->makeJsonResult(['code' => -1, 'message' => '检票失败:'.$data['message']]);
                } else {
                    $this->add_ticket_cache($origin_ticket, $retTxt, time() + 7 * 24 * 60 * 60);
                }
            }
        }
        $data = json_decode($retTxt, true);
        if ($data['code'] != 200) {
            $this->makeJsonResult(['code' => -1, 'message' => '检票失败:'.$data['message']]);
        }
        $tData = $data['data'];
        $ticket_list = $tData['ticket_list'];
        if (!empty($ticket_list) && is_array($ticket_list)) {
            foreach ($ticket_list as $k => $item) {
                $ticket_tag = $item['biz_order_ticket_id'];
                $ticket_name = $item['ticket_name'];
                $ticket_starttime = $item['ticket_starttime'];
                $ticket_endtime = $item['ticket_endtime'];
                // 先判断tag是否存在，如果存在，则跳过。
                $query = $database->select('checkout_ticket', 't')
                    ->fields('t');
                $query->condition('t.ticket_tag', $ticket_tag);
                $result_data = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
                if ($result_data) {
                    continue;
                }
                // 查询当天是否
                // 检票通过，在本地留下数据
                // 等待后续生成 票的类型
                // 然后状态 取号状态有未取号，已取号等待中，呼叫中，过号，骑乘中，骑乘结束，作废
                // 过号需要重新取票。
                $insert_fields = [
                    'origin_ticket' => $origin_ticket,
                    'checkout_type_code' => $checkout_type_code,
                    'ticket_tag' => $ticket_tag,
                    'ticket_name' => $ticket_name,
                    'ticket_starttime' => $ticket_starttime,
                    'ticket_endtime' => $ticket_endtime,
                    'call_code' => '',
                    'call_type' => '',
                    'state' => 1,
                    'create_uid' => $job_number,
                    'create_time' => time(),
                    'update_uid' => $job_number,
                    'update_time' => time(),
                    'udid' => $udid,
                ];
                $query = $database->insert('checkout_ticket');
                $query->fields(array_keys($insert_fields));
                $query->values(array_values($insert_fields));
                $query->execute();
            }
        }
        $query = $database->select('checkout_ticket', 't')
            ->fields('t');
        $query->condition('t.origin_ticket', $origin_ticket);
        $result_data = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        if ($result_data) {
            foreach ($result_data as $k => $v) {
                $wait_num = $this->get_wait_num($v['checkout_type_code'], $v['call_code']);
                $result_data[$k]['wait_num'] = $wait_num;
            }
            $this->makeJsonResult(['code' => 1, 'message' => 'success.', 'data' => $result_data]);
        }
        $this->makeJsonResult(['code' => -1, 'message' => '检票失败,请再试一次.']);
    }

    /**
     * 重新取号.
     */
    public function rescan_ticket_info()
    {
        if (!$this->access_check('P0001')) {
            $this->makeJsonResult(['code' => -1, 'message' => 'No Permission.']);
        }
        $job_number = $this->current_uid();
        $origin_ticket = $this->param['origin_ticket'];
        $origin_ticket = strtoupper($origin_ticket);
        $udid = $this->param['udid'];
        $id = $this->param['id'];
        $database = getDB();
        $query = $database->select('checkout_ticket', 't')
            ->fields('t');
        $query->condition('t.origin_ticket', $origin_ticket);
        $query->condition('t.id', $id);
        $record = $query->execute()->fetchAssoc();
        if ($record) {
            $state = (int) $record['state'];
            //哪些情况可以取号
            if ($state == 5) {
                $this->makeJsonResult(['code' => -1, 'message' => '项目进行中,无法重新取号.']);
            }
            if ($state == 6) {
                $this->makeJsonResult(['code' => -1, 'message' => '项目已结束,无法重新取号.']);
            }
            $next_call_code = $this->get_next_callcode($record['checkout_type_code'], $record['call_type']);
            $update_fields = [
                'call_code' => '',
                'call_type' => '',
                'state' => 1,
                'udid' => $udid,
                'update_uid' => $job_number,
                'update_time' => time(),
                'create_time' => time(),
            ];
            $query = $database->update('checkout_ticket');
            $query->fields($update_fields);
            $query->condition('id', (int) $record['id']);
            $query->execute();
            $insert_fields = [
                'origin_ticket' => '',
                'checkout_type_code' => $record['checkout_type_code'],
                'ticket_tag' => '',
                'call_code' => $record['call_code'],
                'call_type' => $record['call_type'],
                'state' => 7,
                'udid' => $udid,
                'create_uid' => $job_number,
                'create_time' => time(),
                'update_uid' => $job_number,
                'update_time' => time(),
            ];
            $query = $database->insert('checkout_ticket');
            $query->fields(array_keys($insert_fields));
            $query->values(array_values($insert_fields));
            $query->execute();

            $this->makeJsonResult(['code' => 1, 'message' => '检出成功.']);
            //插入一条call_code相同的空数据，状态是作废。
        }
        $this->makeJsonResult(['code' => -1, 'message' => '骑乘结束,重新取号失败.']);
    }

    /**
     * 从缓存读取结果.
     *
     * @param $origin_ticket
     *
     * @return string
     */
    public function fetch_ticket_cache($origin_ticket)
    {
        $origin_ticket = strtoupper($origin_ticket);
        $database = getDB();
        $query = $database->select('checkout_history', 't')
            ->fields('t');
        $query->condition('t.origin_ticket', $origin_ticket);
        $record = $query->execute()->fetchAssoc();
        if ($record) {
            $ex_time = (int) $record['ex_time'];
            if ($ex_time > time()) {
                return $record['result_data'];
            }
        }

        return '';
    }

    /**
     * 缓存票检出信息.
     *
     * @param $origin_ticket
     * @param $content
     * @param $ex_time
     */
    public function add_ticket_cache($origin_ticket, $content, $ex_time)
    {
        $origin_ticket = strtoupper($origin_ticket);
        $database = getDB();
        $query = $database->select('checkout_history', 't')
            ->fields('t');
        $query->condition('t.origin_ticket', $origin_ticket);
        $record = $query->execute()->fetchAssoc();
        if ($record) {
            $update_fields = [
                'result_data' => $content,
                'ex_time' => $ex_time,
                'create_time' => time(),
                'update_time' => time(),
            ];
            $query = $database->update('checkout_history');
            $query->fields($update_fields);
            $query->condition('origin_ticket', $origin_ticket);
            $query->execute();
        } else {
            $insertFields = [
                'origin_ticket' => $origin_ticket,
                'result_data' => $content,
                'ex_time' => $ex_time,
                'create_time' => time(),
                'update_time' => time(),
            ];
            $query = $database->insert('checkout_history');
            $query->fields(array_keys($insertFields));
            $query->values(array_values($insertFields));
            $query->execute();
        }
    }

    /**
     * 根据原票，生成叫号code.
     */
    public function checkout_code()
    {
        if (!$this->access_check('P0001')) {
            $this->makeJsonResult(['code' => -1, 'message' => 'No Permission.']);
        }
        $udid = $this->param['udid'];
        $id = $this->param['id'];
        $origin_ticket = $this->param['origin_ticket'];
        $origin_ticket = strtoupper($origin_ticket);
        $checkout_type_code = $this->param['checkout_type_code'];
        $call_type = (int) $this->param['call_type']; //是什么类型 1Pony马 2单人马 3双人马
        $database = getDB();
        //校验票种
        $query = $database->select('common_dic', 't')
            ->fields('t');
        $query->condition('t.d_code', 'CHECK_TICKET_IDS');
        $query->condition('t.d_item_code', $checkout_type_code);
        $record = $query->execute()->fetchAssoc();
        if (!$record) {
            $this->makeJsonResult(['code' => -1, 'message' => '票种不正确.'.$checkout_type_code]);
        }
        //校验票类型
        $query = $database->select('common_dic', 't')
            ->fields('t');
        $query->condition('t.d_code', $checkout_type_code.'_TYPE');
        $query->condition('t.d_item_code', $call_type);
        $record = $query->execute()->fetchAssoc();
        if (!$record) {
            $this->makeJsonResult(['code' => -1, 'message' => '票的类型不正确.'.$call_type]);
        }
        //校验数据状态
        $query = $database->select('checkout_ticket', 't')
            ->fields('t');
        $query->condition('t.id', $id);
        $query->condition('t.origin_ticket', $origin_ticket);
        $query->condition('t.checkout_type_code', $checkout_type_code);
        $record = $query->execute()->fetchAssoc();
        if (!$record) {
            $this->makeJsonResult(['code' => -1, 'message' => '检出失败.']);
        }
        if ((int) $record['state'] != 1) {
            $this->makeJsonResult(['code' => -1, 'message' => '检出失败，票据状态不正确']);
        }
        $next_call_code = $this->get_next_callcode($checkout_type_code, $call_type); //根据当天类型，生成最大的一条数据。
        $update_fields = [
            'call_code' => $next_call_code,
            'call_type' => $call_type,
            'state' => 2,
            'udid' => $udid,
            'update_time' => time(),
            'create_time' => time(), //把创建时间变成当天的，不影响code生序
        ];
        $query = $database->update('checkout_ticket');
        $query->fields($update_fields);
        $query->condition('id', (int) $record['id']);
        $query->execute();

        $query = $database->select('checkout_ticket', 't')
            ->fields('t');
        $query->condition('t.id', (int) $record['id']);
        $result_data = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        if ($result_data) {
            foreach ($result_data as $k => $v) {
                $wait_num = $this->get_wait_num($v['checkout_type_code'], $v['call_code']);
                $result_data[$k]['wait_num'] = $wait_num;
            }
            $this->makeJsonResult(['code' => 1, 'message' => '检出成功.', 'data' => $result_data[0]]);
        }
        $this->makeJsonResult(['code' => -1, 'message' => 'fail.']);
    }

    /**
     * 获取下一个叫号的编号.
     *
     * @param $checkout_type_code
     * @param $call_type
     *
     * @return string
     */
    public function get_next_callcode($checkout_type_code, $call_type)
    {
        $today_start = strtotime(date('Y-m-d'));
        $database = getDB();
        $query = $database->select('checkout_ticket', 't');
        $query->addExpression('MAX(t.call_code)', 'max_call_code');
        $query->condition('t.checkout_type_code', $checkout_type_code);
        $query->condition('t.call_type', $call_type);
        $query->condition('t.create_time', $today_start, '>=');
        $record = $query->execute()->fetchAssoc();
        $max_call_code = $record['max_call_code'];
        if (empty($max_call_code)) {
            if ($checkout_type_code == 'CHECK_TICKET_HORSE' && $call_type == 1) {
                return '0001'; //pony马
            } elseif ($checkout_type_code == 'CHECK_TICKET_HORSE' && $call_type == 2) {
                return '1001'; //单人马
            } elseif ($checkout_type_code == 'CHECK_TICKET_HORSE' && $call_type == 3) {
                return '2001'; //双人马
            } elseif ($checkout_type_code == 'CHECK_TICKET_HORSE' && $call_type == 4) {
                return '8001'; //vip
            } else {
                return '9999';
            }
        } else {
            $max_call_code = $record['max_call_code'];
            $next = (int) $max_call_code + 1;

            return sprintf('%04s', $next);
        }
    }

    /**
     * 正在叫号的列表.
     */
    public function call_waiting()
    {
        $today_start = strtotime(date('Y-m-d'));
        $checkout_type_code = $this->param['checkout_type_code']; //CHECK_TICKET_HORSE
        $database = getDB();
        $query = $database->select('checkout_ticket', 't');
        $query->fields('t', ['origin_ticket', 'checkout_type_code', 'ticket_tag', 'call_code', 'call_type', 'state', 'start_call', 'start_call', 'start_time']);
        $query->condition('t.state', [2, 3], 'IN');
        $query->condition('t.checkout_type_code', $checkout_type_code);
        $query->condition('t.create_time', $today_start, '>');
        $query->orderBy('t.create_time', 'ASC');
        $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        // 为result每个号计算前面有几个人
        $sum = [];
        foreach ($result as $k => $v) {
            $key = $v['checkout_type_code'].'-'.$v['call_type'];
            if (!isset($sum[$key])) {
                $sum[$key] = [];
            }
            $result[$k]['wait_num'] = count($sum[$key]) + 1;
            $sum[$key][] = $v;
        }
        $this->makeJsonResult(['code' => 1, 'message' => 'success.', 'data' => $result]);
    }

    /**
     * 根据叫的号，获取前面等待的人数.
     *
     * @param $checkout_type_code
     * @param $code
     *
     * @return int|string
     */
    private function get_wait_num($checkout_type_code, $code)
    {
        $today_start = strtotime(date('Y-m-d'));
        $database = getDB();
        $query = $database->select('checkout_ticket', 't');
        $query->fields('t', ['id', 'origin_ticket', 'checkout_type_code', 'ticket_tag', 'call_code', 'call_type', 'state', 'start_call', 'start_time', 'end_time']);
        $query->condition('t.state', [2, 3], 'IN');
        $query->condition('t.checkout_type_code', $checkout_type_code);
        $query->condition('t.call_code', $code);
        $record = $query->execute()->fetchAssoc();
        if ($record) {
            $query = $database->select('checkout_ticket', 't');
            $query->fields('t', ['origin_ticket', 'checkout_type_code', 'ticket_tag', 'call_code', 'call_type', 'state', 'start_call', 'start_call', 'start_time']);
            $query->condition('t.state', [2, 3], 'IN');
            $query->condition('t.checkout_type_code', $checkout_type_code);
            $query->condition('t.call_type', $record['call_type']);
            $query->condition('t.create_time', $today_start, '>');
            $query->orderBy('t.create_time', 'ASC');
            $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($result as $k => $v) {
                if ($v['call_code'] == $code) {
                    return $k + 1;
                }
            }

            return 0;
        }

        return 999;
    }

    /**
     * 正在执行活动.
     */
    public function call_doing()
    {
        $today_start = strtotime(date('Y-m-d'));
        $checkout_type_code = $this->param['checkout_type_code']; //CHECK_TICKET_HORSE
        $database = getDB();
        $query = $database->select('checkout_ticket', 't');
        $query->fields('t', ['id', 'origin_ticket', 'checkout_type_code', 'ticket_tag', 'call_code', 'call_type', 'state', 'start_call', 'start_time', 'end_time']);
        $query->condition('t.state', [5], 'IN');
        $query->condition('t.checkout_type_code', $checkout_type_code);
        $query->condition('t.create_time', $today_start, '>');
        $query->orderBy('t.start_time', 'ASC');
        $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        // 为result每个号计算前面有几个人
        $sum = [];
        foreach ($result as $k => $v) {
            //start_time 如果状态为5，超过10分钟的，标记为已结束
            //CHECK_TICKET_HORSE
            $start_time = (int) $v['start_time'];
            $state = (int) $v['state'];
            $checkout_type_code = $v['checkout_type_code'];
            // 骑马，如果正在骑马的，10分钟之后，标记为结束
            if ($checkout_type_code == 'CHECK_TICKET_HORSE' && $state == 5 && $start_time < time() - 10 * 60) {
                $update_fields = [
                    'state' => 6,
                ];
                $query = $database->update('checkout_ticket');
                $query->fields($update_fields);
                $query->condition('id', (int) $v['id']);
                $query->execute();
                continue;
            }
            $key = $v['checkout_type_code'].'-'.$v['call_type'];
            if (!isset($sum[$key])) {
                $sum[$key] = [];
            }
            $result[$k]['wait_num'] = count($sum[$key]) + 1;
            $sum[$key][] = $v;
        }
        $this->makeJsonResult(['code' => 1, 'message' => 'success.', 'data' => $result]);
    }

    /**
     * 查询状态
     */
    public function call_search()
    {
        $udid = $this->param['udid'];
        $checkout_type_code = $this->param['checkout_type_code']; //CHECK_TICKET_HORSE 骑马 ， CHECK_TICKET_BOAT 游船
        $code = $this->param['code'];
        $today_start = strtotime(date('Y-m-d'));
        $database = getDB();
        $query = $database->select('checkout_ticket', 't');
        $query->fields('t', ['id', 'origin_ticket', 'checkout_type_code', 'ticket_tag', 'call_code', 'call_type', 'state', 'start_call', 'start_time', 'end_time']);
        $query->condition('t.call_code', $code);
        $query->condition('t.checkout_type_code', $checkout_type_code);
        $query->condition('t.create_time', $today_start, '>');
        $query->orderBy('t.create_time', 'ASC');
        $query->range(0, 1);
        $record = $query->execute()->fetchAssoc();
        if ($record) {
            $this->makeJsonResult(['code' => 1, 'message' => 'success1.', 'data' => $record]);
        } else {
            $this->makeJsonResult(['code' => -1, 'message' => '暂无数据.']);
        }
    }

    /**
     * 叫号，下一个.
     */
    public function call_next()
    {
        $udid = $this->param['udid'];
        $checkout_type_code = $this->param['checkout_type_code']; //CHECK_TICKET_HORSE 骑马 ， CHECK_TICKET_BOAT 游船
        $call_type = $this->param['call_type']; //1 pony马 2单人马 3双人马 4vip马
        $today_start = strtotime(date('Y-m-d'));
        $database = getDB();
        //取出当前状态为3正在呼叫的数据。如果存在，设置为过号
        $update_fields = [
            'state' => 4,
            'update_time' => time(),
        ];
        if (!empty($udid)) {
            $update_fields['udid'] = $udid;
        }
        $query = $database->update('checkout_ticket');
        $query->fields($update_fields);
        $query->condition('checkout_type_code', $checkout_type_code);
        $query->condition('call_type', $call_type);
        $query->condition('state', 3);
        $query->condition('create_time', $today_start, '>');
        $query->execute();
        $query = $database->select('checkout_ticket', 't');
        $query->fields('t', ['id', 'origin_ticket', 'checkout_type_code', 'ticket_tag', 'call_code', 'call_type', 'state', 'start_call', 'start_time', 'end_time']);
        $query->condition('t.state', [2], 'IN');
        $query->condition('t.checkout_type_code', $checkout_type_code);
        $query->condition('t.call_type', $call_type);
        $query->condition('t.create_time', $today_start, '>');
        $query->orderBy('t.create_time', 'ASC');
        $query->range(0, 1);
        $record = $query->execute()->fetchAssoc();
        if ($record) {
            $update_fields = [
                'start_call' => time(),
                'state' => 3,
                'update_time' => time(),
            ];
            if (!empty($udid)) {
                $update_fields['udid'] = $udid;
            }
            $query = $database->update('checkout_ticket');
            $query->fields($update_fields);
            $query->condition('id', (int) $record['id']);
            $query->execute();
            $this->makeJsonResult(['code' => 1, 'message' => 'success.', 'data' => $record]);
        } else {
            $this->makeJsonResult(['code' => -1, 'message' => '暂无数据.']);
        }
    }

    public function tts()
    {
        $content = $this->param['content'];
        if (empty($content)) {
            $this->makeJsonResult(['code' => -1, 'message' => '内容不能为空.']);
        }
        $type = $this->param['type'];
        if (empty($type)) {
            $this->makeJsonResult(['code' => -1, 'message' => '类型不能为空.']);
        }
        $insertFields = [
            'type' => $type,
            'content' => $content,
            'state' => 0,
            'create_time' => time(),
        ];
        $database = getDB();
        $query = $database->insert('tts_content');
        $query->fields(array_keys($insertFields));
        $query->values(array_values($insertFields));
        $query->execute();
        $this->makeJsonResult(['code' => 1, 'message' => 'success.']);
    }

    /**
     * 获取需要叫号的内容。多条数据合并返回.
     */
    public function tts_list()
    {
        $today_start = strtotime(date('Y-m-d'));
        $start_time = time(); //秒
        $max_wait = 30; //秒
        set_time_limit(60);
        $type = $this->param['type'];
        if (empty($type)) {
            $this->makeJsonResult(['code' => -1, 'message' => '类型不能为空.']);
        }
        $database = getDB();
        $query = $database->select('tts_content', 't');
        $query->fields('t');
        $query->condition('t.state', 0);
        $query->condition('t.type', $type);
        $query->condition('t.create_time', $today_start, '>');
        $query->orderBy('t.id', 'ASC');
        $query->range(0, 1);
        $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        if (!$result) {
            while (true) {
                $query = $database->select('tts_content', 't');
                $query->fields('t');
                $query->condition('t.state', 0);
                $query->condition('t.type', $type);
                $query->condition('t.create_time', $today_start, '>');
                $query->orderBy('t.id', 'ASC');
                $query->range(0, 1);
                $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
                if ($result) {
                    break;
                }
                sleep(1);
                if (time() - $start_time > $max_wait) { //超过30秒，自动退出
                    break;
                }
            }
        }
        if ($result) {
            $content = '';
            foreach ($result as $k => $v) {
                $content = $content.$v['content'];
                $update_fields = [
                    'state' => 2,
                ];
                $query = $database->update('tts_content');
                $query->fields($update_fields);
                $query->condition('id', (int) $v['id']);
                $query->execute();
            }
            $this->makeJsonResult(['code' => 1, 'message' => 'success.', 'data' => ['content' => $content]]);
        }
        $this->makeJsonResult(['code' => -1, 'message' => '暂无数据.']);
    }

//    function exec_wincmd($cmd)
//    {
//        $WshShell = new \COM("WScript.Shell");
//        $cwd = getcwd();
//        if (strpos($cwd, ' ')) {
//            if ($pos = strpos($cmd, ' ')) {
//                $cmd = substr($cmd, 0, $pos) . '" ' . substr($cmd, $pos);
//            } else {
//                $cmd .= '"';
//            }
//            $cwd = '"' . $cwd;
//        }
//        $oExec = $WshShell->Run("cmd /C \" $cmd\"", 0, true);
//        return $oExec == 0 ? true : false;
//    }

    /**
     * 骑马或游船开始.
     */
    public function call_start()
    {
        $id = $this->param['id'];
        $udid = $this->param['udid'];
        $is_force = $this->param['is_force'];
        if (!$is_force) {
            $is_force = 0;
        }
        $checkout_type_code = $this->param['checkout_type_code'];
        if (empty($id)) {
            $this->makeJsonResult(['code' => -1, 'message' => '参数不正确.']);
        }
        $database = getDB();
        $query = $database->select('checkout_ticket', 't')
            ->fields('t');
        $query->condition('t.id', $id);
        $query->condition('t.checkout_type_code', $checkout_type_code);
        $record = $query->execute()->fetchAssoc();
        if (!$record) {
            $this->makeJsonResult(['code' => -1, 'message' => '数据不存在.']);
        }
        $state = (int) $record['state'];
        // 校验state
        if ($state == 2 && $is_force == 1) {
            $state = 3;
        }
        if ($state != 3) {
            if ($state == 5) {
                $time = time() - (int) $record['start_time'];
                $this->makeJsonResult(['code' => -1, 'message' => '项目已开始'.$time.'秒.']);
            }
            if ($state == 2) {
                $this->makeJsonResult(['code' => -1, 'message' => '请先呼叫，呼叫之后才能开始']);
            }
            $this->makeJsonResult(['code' => -1, 'message' => '数据状态不正确.'.$state]);
        }
        $update_fields = [
            'start_time' => time(),
            'state' => 5,
            'update_time' => time(),
        ];
        if (!empty($udid)) {
            $update_fields['udid'] = $udid;
        }
        $query = $database->update('checkout_ticket');
        $query->fields($update_fields);
        $query->condition('id', (int) $record['id']);
        $query->execute();
        //回写api接口
        $job_number = $this->current_uid();
        $biz_order_ticket_id = $record['ticket_tag'];
        $bo = $this->api_call_back($job_number, $biz_order_ticket_id);
        if ($bo) {
            $update_fields = [
                'call_back_state' => 1,
            ];
            $query = $database->update('checkout_ticket');
            $query->fields($update_fields);
            $query->condition('id', (int) $record['id']);
            $query->execute();
        }
        $this->makeJsonResult(['code' => 1, 'message' => 'success.']);
    }

    // 回写
    private function api_call_back($job_number, $biz_order_ticket_id)
    {
        $url = API_HOST.'/admin/ticket/check_call_number';
        $params = [];
        $params['job_number'] = $job_number;
        $params['biz_order_ticket_id'] = $biz_order_ticket_id;
        $signUtil = new Model\SignModel();
        $sign = $signUtil->makeSignAdmin($params);
        $params['sign'] = $sign;
        $ret = $this->curl_get($url, $params);
        if ($ret['status'] != 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 叫号结束
     */
    public function call_end()
    {
        $id = $this->param['id'];
        $checkout_type_code = $this->param['checkout_type_code'];
        $udid = $this->param['udid'];
        if (empty($id)) {
            $this->makeJsonResult(['code' => -1, 'message' => '参数不正确.']);
        }
        $database = getDB();
        $query = $database->select('checkout_ticket', 't')
            ->fields('t');
        $query->condition('t.id', $id);
        $query->condition('t.checkout_type_code', $checkout_type_code);
        $record = $query->execute()->fetchAssoc();
        if (!$record) {
            $this->makeJsonResult(['code' => -1, 'message' => '数据不存在.']);
        }
        $state = (int) $record['state'];
        if ($state != 5) {
            if ($state == 3) {
                $this->makeJsonResult(['code' => -1, 'message' => '请先开始项目.']);
            }
            if ($state == 6) {
                $this->makeJsonResult(['code' => -1, 'message' => '项目已结束.']);
            }
            $this->makeJsonResult(['code' => -1, 'message' => '数据状态不正确.'.$state]);
        }
        $update_fields = [
            'end_time' => time(),
            'state' => 6,
            'update_time' => time(),
        ];
        if (!empty($udid)) {
            $update_fields['udid'] = $udid;
        }
        $query = $database->update('checkout_ticket');
        $query->fields($update_fields);
        $query->condition('id', (int) $record['id']);
        $query->execute();
        $this->makeJsonResult(['code' => 1, 'message' => 'success.']);
    }

    /**
     * 获取设备的叫号信息.
     */
    public function device_call_info()
    {
        $today_start = strtotime(date('Y-m-d'));
        //获取设备当天3种情况的最后一条数据
        $udid = $this->param['udid'];
        $checkout_type_code = $this->param['checkout_type_code']; //CHECK_TICKET_HORSE
        $database = getDB();
        if ($checkout_type_code == 'CHECK_TICKET_HORSE') {
            //拿到type
            $types = [1, 2, 3, 4]; // pony马 单人马 双人马 vip马
            $retData = [];
            foreach ($types as $v) {
                $query = $database->select('checkout_ticket', 't')
                    ->fields('t');
                $query->condition('t.checkout_type_code', $checkout_type_code);
//                $query->condition('t.udid', $udid);
                $query->condition('t.call_type', $v);
                $query->condition('t.state', [1, 2, 3], 'IN');
                $query->condition('t.create_time', $today_start, '>');
                $query->orderBy('t.id', 'ASC');
                $query->range(0, 1);
                $record = $query->execute()->fetchAssoc();
                if ($record) {
                    $retData[] = $record;
                } else {
                    $retData[] = $record;
                }
            }
            $this->makeJsonResult(['code' => 1, 'message' => 'success.', 'data' => $retData]);
        }
        $this->makeJsonResult(['code' => -1, 'message' => '暂无数据.']);
    }

    /**
     * 网络访问curl.
     *
     * @param $url
     * @param $params
     *
     * @return mixed
     */
    private function curl_get($url, $params)
    {
        $request = '';
        foreach ($params as $k => $v) {
            if (empty($request)) {
                $request .= $k.'='.$v;
            } else {
                $request .= '&';
                $request .= $k.'='.$v;
            }
        }
        if (strpos($url, '?') === false) {
            $request = '?'.$request;
        } else {
            $request = '&'.$request;
        }
        $url = $url.$request;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);
        $ret = [
            'status' => 1,
            'data' => $output,
        ];

        return $ret;
    }

    /**
     * 更新数据状态
     */
    public function update_call_state()
    {
        $udid = $this->param['udid'];
        $origin_ticket = $this->param['origin_ticket'];
        $id = $this->param['id'];
        $state = $this->param['state'];
        $database = getDB();
        $query = $database->select('checkout_ticket', 't')
            ->fields('t');
        $query->condition('t.id', $id);
        $query->condition('t.origin_ticket', $origin_ticket);
        $record = $query->execute()->fetchAssoc();
        if (empty($record)) {
            $this->makeJsonResult(['code' => -1, 'message' => '数据不存在.']);
        }
        $query = $database->update('checkout_ticket');
        $query->fields([
            'state' => $state,
            'update_time' => time(),
            'udid' => $udid,
        ]);
        $query->condition('id', (int) $record['id']);
        $query->execute();
        $this->makeJsonResult(['code' => 1, 'message' => 'success.']);
    }
}
