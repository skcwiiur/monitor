<?php

namespace Monitor\Controller;

use Think\Controller;

/**
 * 默认首页Controller
 * 显示框架基本信息，具体项目请新建模块.
 */
class APIBaseController extends Controller
{
    /**
     * @var string
     */
    protected $defaultType = 'json';
    protected $jsonpCallback = 'callback_';

    /**
     * @var array
     */
    protected $param = [];

    protected $noAuthArray = [
        'login',
        'screen_wait',
        'screen_ing',
        'call_waiting',
        'call_doing',
        'tts',
        'tts_list',
    ];
    protected $token_uid = '';

    /**
     * 一次授权，保持多久.
     *
     * @var int
     */
    protected $token_keep = 7 * 24 * 3600;

    /**
     * ApiBaseController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        header('Access-Control-Allow-Origin:*');

        $raw = json_decode(file_get_contents('php://input'), true);
        $post = I('post.');
        $get = I('get.');
        if ($raw) {
            $this->param = $raw;
        }
        if ($post) {
            $this->param = $this->param + $post;
        }
        if ($get) {
            $this->param = $this->param + $get;
        }
        $rtype = isset($this->param['rtype']) ? $this->param['rtype'] : 'json';
        if ($rtype == 'jsonp') {
            $this->defaultType = 'jsonp';
            $this->callback = isset($this->param['callback']) ? $this->param['callback'] : 'callback_';
        }
        //$this->checkAuth();
    }

    /**
     * 检查授权（token或者sign）.
     */
    private function checkAuth()
    {
        $token = isset($this->param['token']) ? $this->param['token'] : '';
        if (!empty($token)) {
            $database = getDB();
            $query = $database->select('token_access', 't');
            $query->fields('t');
            $query->condition('t.token', $token);
            $record = $query->execute()->fetchAssoc();
            if ($record) {
                $ex_time = $record['ex_time'];
                $this->token_uid = $record['uid'];
                if ($ex_time > time()) {
                    return true;
                } else {
                    $this->makeJsonResult(['code' => -100, 'message' => '登录已失效，请重新登录.']);
                }
            } else {
                $this->makeJsonResult(['code' => -100, 'message' => '登录已失效，请重新登录.']);
            }
        }
        if (in_array(ACTION_NAME, $this->noAuthArray)) {
            return true;
        }
        $this->makeJsonResult(['code' => -1, 'message' => 'No authority.'.CONTROLLER_NAME.'-'.ACTION_NAME]);
    }

    /**
     * api调用成功返回方法.
     *
     * @param $data
     */
    protected function makeJsonResult($data)
    {
        if ($this->defaultType == 'jsonp') {
            header('Content-Type: text/javascript');
            echo $this->callback.'('.json_encode($data, JSON_UNESCAPED_UNICODE).')';
            exit;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @param $uid
     *
     * @return string
     */
    protected function createToken($uid)
    {
        return md5($uid.time().$this->rand_number(1000, 9999));
    }

    /**
     * 获取一定范围内的随机数字
     * 跟rand()函数的区别是 位数不足补零 例如
     * rand(1,9999)可能会得到 465
     * rand_number(1,9999)可能会得到 0465  保证是4位的.
     *
     * @param int $min 最小值
     * @param int $max 最大值
     *
     * @return string
     */
    protected function rand_number($min = 1, $max = 9999)
    {
        return sprintf('%0'.strlen($max).'d', mt_rand($min, $max));
    }

    /**
     * @param $uid
     * @param $token
     */
    protected function keepToken($uid, $token)
    {
        $database = getDB();
        // TODO:删除过期的，或者该帐号其他有效的token，实现单设备登录
        $insertFields = [
            'uid' => $uid,
            'device_id' => '',
            'token' => $token,
            'ex_time' => time() + $this->token_keep,
        ];
        $query = $database->insert('token_access');
        $query->fields(array_keys($insertFields))->values(array_values($insertFields));
        $query->execute();
    }
}
