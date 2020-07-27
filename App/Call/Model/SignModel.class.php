<?php
namespace Call\Model;
/**
 *  数据签名和数据验证
 */
class SignModel
{

    protected static $instance;

    /**
     * 初始化
     * @access public
     * @param array $options 参数
     * @return Sign
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 生成签名签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     * @return string
     */
    public function makeSign(&$values)
    {
        // 加入一个随机字符串
        $values['nonce_str'] = $this->getNonceStr();
        //签名步骤一：按字典序排序参数
        ksort($values);
        $string = $this->toUrlParams($values);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".config('app_key');
        //	echo $string;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 生成签名签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     * @return string
     */
    public function makeSignAdmin($values)
    {
        // 加入一个随机字符串
//        $values['nonce_str'] = $this->getNonceStr();
        //签名步骤一：按字典序排序参数
        // $sign = '';
        unset($values['sign']);
        //对参数中的数组  做特殊处理
        foreach ($values as $key=> $value){
            if($key == 'ticket_ids'){
                $value = json_decode($value, true);
            }
            if(is_array($value)){
                $array =   $this->getArrayKeyValue($value, $key);
                if(count($array)){
                    foreach ($array as $k=>$v){
                        $values[$k] = $v;
                    }
                }
                unset($values[$key]);
            }
        }
        ksort($values);
        $string = $this->toUrlParams($values);
        $string = strtoupper(md5($string . "&key=".'ay8zl2XveLrPTBju'));
        unset($values['nonce_str']);
        return $string;
    }

    /**
     * 校验传入参数正确性
     * @author wangbo 2018-02-01
     * @param  array &$values 传入数据
     * @return bool
     */
    public function checkSign(&$values)
    {
        if (!isset($values['sign'])) {
            return false;
        }
        $sign = $values['sign'];
        unset($values['sign']);
        //对参数中的数组  做特殊处理
        foreach ($values as $key=> $value){
            if(is_array($value)){
                $array =   $this->getArrayKeyValue($value, $key);
                if(count($array)){
                    foreach ($array as $k=>$v){
                        $values[$key.$k] = $v;
                    }
                }
                unset($values[$key]);
            }
        }
        ksort($values);
        $string = $this->toUrlParams($values);
        $string = strtoupper(md5($string . "&key=".config('app_key')));
        unset($values['nonce_str']);
        return $sign === $string ? true : false;
    }



    /**
     * 校验传入参数正确性
     * @author wangbo 2018-02-01
     * @param  array &$values 传入数据
     * @return bool
     */
    public function checkSignAdmin(&$values)
    {
        if (!isset($values['sign'])) {
            return false;
        }
        // $sign = '';
        $sign = $values['sign'];
        unset($values['sign']);
        //对参数中的数组  做特殊处理
        foreach ($values as $key=> $value){
            if($key == 'ticket_ids'){
                $value = json_decode($value, true);
            }
            if(is_array($value)){
                $array =   $this->getArrayKeyValue($value, $key);
                if(count($array)){
                    foreach ($array as $k=>$v){
                        $values[$k] = $v;
                    }
                }
                unset($values[$key]);
            }
        }
        ksort($values);
        $string = $this->toUrlParams($values);
        $string = strtoupper(md5($string . "&key=".'ay8zl2XveLrPTBju'));
        unset($values['nonce_str']);
        return $sign === $string ? true : false;
    }

    /**
     * 格式化参数格式化成url参数
     */
    private function toUrlParams($values)
    {
        $buff = "";
        foreach ($values as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }


    /**
     * @param $array array 转换的数组
     * @param $base
     * @return string
     */
    private function getArrayUrl($array, $base){
        global  $xx ;

        foreach ($array as $key=>$value){
            if(is_array($value)){
                $base  .=  '['.$key.']';
                $this->getArrayUrl($value, $base);
            }else{
                $xx.= $base.'['.$key.']'.'='.$value.'&';
            }
        }
        return $xx;
    }

    /**
     * 将数组多维数组 转成一维数组
     * @param $array array
     * @param $base
     * @return array
     */
    private function getArrayKeyValue($array, $base){
        global  $xx ;

        foreach ($array as $key=>$value){
            if(is_array($value)){
                $base  =  '['.$key.']';
                $this->getArrayKeyValue($value, $base);
            }else{
                $xx[$base.'['.$key.']']= $value;
            }
        }
        return $xx;
    }

    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return string
     */
    private static function getNonceStr($length = 32)
    {
        return 123456;
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
}