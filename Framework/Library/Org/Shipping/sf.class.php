<?php
/**
 * Created by PhpStorm.
 * User: FHT
 * Date: 2017/8/18 0018
 * Time: 19:43
 */

namespace Org\Shipping;


/**
 * 顺丰快递接口
 * @package Org\Shipping
 */
class sf {

    /**
     * 下订单（含筛选）接口
     * @param array $data
     * @return array
     */
    public static function doOrder($data = []) {
        if (empty($data)) {
            return formatResult(OP_FAIL, '', "数据为空！");
        }

        $express_type = isset($data['express_type']) ? $data['express_type'] : 37;
        $orderid = isset($data['orderid']) ? $data['orderid'] : '';
        $d_province = isset($data['d_province']) ? $data['d_province'] : '';
        $d_city = isset($data['d_city']) ? $data['d_city'] : '';
        $d_company = isset($data['d_company']) ? $data['d_company'] : '';
        $d_contact = isset($data['d_contact']) ? $data['d_contact'] : '';
        $d_contact = str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $d_contact);
        $d_tel = isset($data['d_tel']) ? $data['d_tel'] : '';
        $d_address = isset($data['d_address']) ? $data['d_address'] : '';
        $d_address = str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $d_address);
        $pay_method = isset($data['pay_method']) ? $data['pay_method'] : 1;
        $parcel_quantity = isset($data['parcel_quantity']) ? $data['parcel_quantity'] : 1;
        $conf = C('SF_CFG');

        if (empty($conf['checkword']) || empty($conf['url']) || empty($conf['custid'])) {
            return formatResult(OP_FAIL, '', "配置错误！");
        }
        if (empty($orderid)) {
            return formatResult(OP_FAIL, '', "订单号不能为空！");
        }

        $tpl = "<?xml version='1.0' encoding='UTF-8'?>";
        $tpl .= "<Request service='OrderService' lang='zh-CN'>";
        $tpl .= "<Head>{$conf['custid']}</Head>";
        $tpl .= "<Body>";
        $tpl .= "<Order orderid='{$orderid}' express_type='{$express_type}' j_province='{$conf['j_province']}' j_city='{$conf['j_city']}' j_company='{$conf['j_company']}' "
            . "j_contact='{$conf['j_contact']}' j_tel='{$conf['j_tel']}' j_address='{$conf['j_address']}' d_province='{$d_province}' d_city='{$d_city}' d_company='{$d_company}' "
            . "d_contact='{$d_contact}' d_tel='{$d_tel}' d_address='{$d_address}' pay_method='{$pay_method}' parcel_quantity='{$parcel_quantity}'/>";
        $tpl .= "</Order>";
//        $tpl .= "<OrderOption cargo='服装' custid='{$conf['custid']}'/>";
//        $tpl .= "</OrderOption>";
        $tpl .= "</Body>";
        $tpl .= "</Request>";
        $res = self::_doRequest($tpl, $conf);
        if (OP_SUCCESS !== $res['status']) {
            return $res;
        }
        else{
            return formatResult(OP_SUCCESS, $res['data']['OrderResponse']['@attributes']);
        }
    }

    /**
     * 子单号申请接口
     * @param array $data
     * @return array
     */
    public static function doOrderZD($data = []) {
        if (empty($data)) {
            return formatResult(OP_FAIL, '', "数据为空！");
        }

        $orderid = isset($data['orderid']) ? $data['orderid'] : '';
        $number = isset($data['number']) ? $data['number'] : '';
        $conf = C('SF_CFG');

        if (empty($conf['checkword']) || empty($conf['url']) || empty($conf['custid'])) {
            return formatResult(OP_FAIL, '', "配置错误！");
        }
        if (empty($orderid)) {
            return formatResult(OP_FAIL, '', "订单号不能为空！");
        }

        //3.tpl
        $tpl = "<Request service='OrderZDService' lang='zh-CN'>";
        $tpl .= "<Head> {$conf['custid']}</Head>";
        $tpl .= "<Body>";
        $tpl .= "<OrderZD orderid='{$orderid}' parcel_quantity='{$number}'/>";
        $tpl .= "</Body>";
        $res = self::_doRequest($tpl, $conf);
        if (OP_SUCCESS !== $res['status']) {
            return $res;
        }
        else{
            return formatResult(OP_SUCCESS, $res['data']['OrderZDResponse']['OrderZDResponse']['@attributes']);
        }
    }

    /**
     * 路由查询接口
     * @param array $data
     * @return array
     */
    public static function doRoute($data = []) {
        if (empty($data)) {
            return formatResult(OP_FAIL, '', "数据为空！");
        }

        $tracking_type = isset($data['tracking_type']) ? $data['tracking_type'] : 1;
        $tracking_number = isset($data['tracking_number']) ? $data['tracking_number'] : '';
        $conf = C('SF_CFG');

        if (empty($conf['checkword']) || empty($conf['url']) || empty($conf['custid'])) {
            return formatResult(OP_FAIL, '', "配置错误！");
        }
        if (empty($tracking_number)) {
            return formatResult(OP_FAIL, '', "订单号不能为空！");
        }

        //3.tpl
        $tpl = "<Request service='RouteService' lang='zh-CN'>";
        $tpl .= "<Head> {$conf['custid']}</Head>";
        $tpl .= "<Body>";
        $tpl .= "<RouteRequest tracking_type='{$tracking_type}' tracking_number='{$tracking_number}'/>";
        $tpl .= "</Body>";

        return self::_doRequest($tpl, $conf);
    }

    /**
     * 发送请求
     * @param $tpl
     * @param $conf
     * @return array
     */
    private static function _doRequest($tpl, $conf) {
        $verifyCode = base64_encode(md5($tpl . $conf['checkword'], TRUE));
        $res = curl_request($conf['url'], ("xml={$tpl}&verifyCode={$verifyCode}"));
        if (OP_SUCCESS !== $res['status']) {
            return $res;
        }
        $res = xmlToArray($res['data']);
        if (OP_SUCCESS !== $res['status']) {
            return formatResult(OP_FAIL, '', "xml解析失败！");
        }
        $res = $res['data'];
        if ('OK' != $res['Head']) {
            return formatResult(OP_FAIL, '', $res['ERROR']);
        }
        return formatResult(OP_SUCCESS, $res['Body']);
    }

}