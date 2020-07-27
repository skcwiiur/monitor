<?php

namespace Monitor\Controller;

use Think\Controller;
use Org\Wechat\WechatApi;

//require_once ORG_PATH.'Shipping/SF/SF_service.class.php';

/**
 * 默认首页Controller
 * 显示框架基本信息，具体项目请新建模块.
 */
class IndexController extends Controller
{
    public function index()
    {
        S('et', 1);
        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover{color:blue;}</style>'
            .'<div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用9 <b>ThinkPHP</b>！</p><br/>框架版本'.C('FRAMEWORK_VERSION').'<br/>PHP版本 '.PHP_VERSION.'<br/>ThinkPHP版本 V{$Think.version}</div>'
            .'<script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>', 'utf-8');
    }

    public function phpinfo()
    {
        phpinfo();
    }

    public function test()
    {
        $result = curl_request('http://ba.fangyue.net/icp.php?token=8492ce0f210351d7911cfea00177bc84&domain=baidu.com');
        if (OP_SUCCESS !== $result['status']) {
            print_r($result);
            exit;
        }

        $temp = preg_replace('/[\x00-\x1F]|\xef|\xbb|\xbf/i', '', $result['data']);
        $temp = json_decode($temp, true);
        print_r($temp);
        exit;
    }

    public function dfDoOrder()
    {
        $order = [
            'express_type' => 1,
            'orderid' => '789uwerw9815', //444845331323
            'd_province' => '江苏省',
            'd_city' => '无锡市',
            'd_contact' => '费天宇',
            'd_tel' => '15061228838',
            'd_address' => '江苏省江阴市澄江街道1132号',
            'parcel_quantity' => 1,
            'pay_method' => 1,
            'account' => 'JYSMFJYDQ',
        ];
        print_r(\Org\Shipping\sf::doOrder($order));
//        $order = ['tracking_type' => 1, 'tracking_number' => "444845331323"];
//        print_r(\Org\Shipping\sf::doRoute($order));
//        $order = ['number' => 2, 'orderid' => "789uwerw9810"];
//        print_r(\Org\Shipping\sf::doOrderZD($order));
    }

    public function wechatJs()
    {
        $jsPackage = WechatApi::getSignPackage();
        $this->assign('signPackage', $jsPackage['data']);
        $this->display();
    }

    public function imgResize()
    {
        if (!IS_POST) {
            $this->display();
            exit;
        }
        $upload = new \Think\Upload();
        $upload->exts = ['jpg', 'png'];
        $upload->autoSub = false;
        $info = $upload->upload();
        $excel_path = $upload->rootPath.$info['test']['savename'];
        Img($excel_path, 870, 100, 1);
    }

    public function getWxUser()
    {
        $result = WechatApi::userGetList();
        foreach ($result['data']['data']['openid'] as $value) {
            print_r(WechatApi::userInfo($value));
            print_r('</br>');
        }
    }

    /**
     * 购买模版消息通知.
     *
     * @static
     *
     * @param $touser 目标用户的openid
     * @param $product 购买的商品
     * @param $price 购买的金额
     * @param $time 购买的时间
     * @param $url 跳转url
     */
    public function purchaseSuccessNotify($touser, $url = 'http://www.baidu.com/')
    {
//        $data = [
//            'first' => ['value' => "尊敬的会员，感谢您使用AUTASON微信商城购物", 'color' => '#000000'],
//            'product' => ['value' => $product, 'color' => '#000000'],
//            'price' => ['value' => $price, 'color' => '#000000'],
//            'time' => ['value' => $time, 'color' => '#000000'],
//            'remark' => ['value' => "感谢您对AUTASON的支持与厚爱，如有问题请洽4001008866。温馨提示：请按说明书洗涤和保养。", 'color' => '#000000'],
//        ];
        $data = [
            'content' => ['value' => '你滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答滴答', 'color' => '#000000'],
        ];

        return WechatApi::sendTemplateMessage($touser, 'TbZq-i2HdQFWq_dZMGQ3q0_4LegIqBIeVQNFaU_7L0U', $url, '#00FF00', $data);
    }

    public function amazonS3()
    {
        $s = new \AmazonS3([
            'key' => '95a7eee94547843f3ebf',
            'secret' => '2e1a796cff8e6c04665e9198fc35c67d017f0ead',
            //            'key' => '35e96cab45b8836e38fc',
            //            'secret' => '0750073bd9eaa4a8479175285890361f6cd2627f',
        ]);
        $s->set_hostname('oos-hq-sh.ctyunapi.cn');
        $s->allow_hostname_override(false);
        //$s->enable_path_style();
        $ListResponse = $s->list_buckets();
        $Buckets = $ListResponse->body->Buckets->Bucket;
        foreach ($Buckets as $Bucket) {
            echo $Bucket->Name."\t".$Bucket->CreationDate.'<br>';
        }
        //       $response = $s->create_object('heilan-prd', 'test/test1.txt', ['body' => 'Hello world. Nice to meet you .']);
        //        $response = $s->create_object('heilan-prd', 'test/test2.txt', ['body' => 'Hello world. Nice to meet you too.']);
        //        $response = $s->delete_object('heilan-prd', 'test/test2.txt');
        $response = $s->create_object('heilan-prd', 'test/test.vedio', ['fileUpload' => PUBLIC_PATH.'Upload/video.mp4']);
        //        $response = $s->get_object('heilan-prd', 'test/test2.txt');
        //                var_dump($response->body);
        $response = $s->get_object_url('heilan-prd', 'test/test.vedio', time() + 500);
        var_dump($response);
    }

    public function receive()
    {
        print_r(I('post.'));
    }

    public function sf_order()
    {
        $order = [
            'express_type' => 1,
            'orderid' => '789uwerw9813', //444845330955
            'd_province' => '江苏省',
            'd_city' => '无锡市',
            'd_contact' => '费天宇',
            'd_tel' => '15061748838',
            'd_address' => '江苏省江阴市澄江街道1132号',
            'parcel_quantity' => 1,
            'pay_method' => 1,
            'account' => 'JYSMFJYDQ',
        ];
        //        $res = $sf->doOrderService($order);
        $tpl = $this->tplOrderService($order);
        //        $tpl = $this->tplRouteService(['custid'=>'5101702166','tracking_type'=>1,'tracking_number'=>"612176268922"]);
        //        $verifyCode = base64_encode(md5($tpl . "T1tqoHmBwCrQztPrjWr3XFaii622w3kM",true));
        //        $url = "http://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService";
        //        $tpl = $this->tplRouteService(['custid'=>'BSPdevelop','tracking_type'=>1,'tracking_number'=>"444824170707"]);
        //        $verifyCode = base64_encode(md5($tpl . "j8DzkIFgmlomPt0aLuwU",true));
        //        $url = "http://bspoisp.sit.sf-express.com:11080/bsp-oisp/sfexpressService";
        $verifyCode = base64_encode(md5($tpl.'LpAHhoEEgjf6', true));
        $url = 'http://218.17.248.244:11080/bsp-oisp/sfexpressService';
        $res = curl_request($url, ("xml={$tpl}&verifyCode={$verifyCode}")); //["xml" => $tpl, 'verifyCode' => $verifyCode]
        $res = xmlToArray($res['data']);
        print_r($res);
    }

    private function tplOrderService(array $conf)
    {
        //1.取值
        $custid = isset($conf['account']) ? $conf['account'] : '';
        $express_type = isset($conf['express_type']) ? $conf['express_type'] : 37;
        $orderid = isset($conf['orderid']) ? $conf['orderid'] : '';
        $d_province = isset($conf['d_province']) ? $conf['d_province'] : '';
        $d_city = isset($conf['d_city']) ? $conf['d_city'] : '';
        $d_company = isset($conf['d_company']) ? $conf['d_company'] : '';
        $d_contact = isset($conf['d_contact']) ? $conf['d_contact'] : '';
        $d_contact = str_replace(['&', '<', '>', '\'', '"'], ['&amp;', '&lt;', '&gt;', '&apos;', '&quot;'], $d_contact);
        $d_tel = isset($conf['d_tel']) ? $conf['d_tel'] : '';
        $d_address = isset($conf['d_address']) ? $conf['d_address'] : '';
        $d_address = str_replace(['&', '<', '>', '\'', '"'], ['&amp;', '&lt;', '&gt;', '&apos;', '&quot;'], $d_address);
        $pay_method = isset($conf['pay_method']) ? $conf['pay_method'] : 1;
        $parcel_quantity = isset($conf['parcel_quantity']) ? $conf['parcel_quantity'] : 1;
        //2.校验
        if (empty($orderid)) {
            return ''; //订单号不能为空
        }
        $sf_cfg = [
            'j_province' => '江苏省',
            'j_city' => '江阴市',
            'j_company' => '上海科瑞特服饰',
            'j_contact' => '赵伟岳',
            'j_tel' => '0510-86121388-3122',
            'j_address' => '上海科瑞特服饰',
        ];
        //3.tpl
        $tpl = "<?xml version='1.0' encoding='UTF-8'?>";
        $tpl .= "<Request service='OrderService' lang='zh-CN'>";
        $tpl .= "<Head>{$custid}</Head>";
        $tpl .= '<Body>';
        $tpl .= "<Order orderid='{$orderid}' express_type='{$express_type}' j_province='{$sf_cfg['j_province']}' j_city='{$sf_cfg['j_city']}' j_company='{$sf_cfg['j_company']}' "
            ."j_contact='{$sf_cfg['j_contact']}' j_tel='{$sf_cfg['j_tel']}' j_address='{$sf_cfg['j_address']}' d_province='{$d_province}' d_city='{$d_city}' d_company='{$d_company}' "
            ."d_contact='{$d_contact}' d_tel='{$d_tel}' d_address='{$d_address}' pay_method='{$pay_method}' parcel_quantity='{$parcel_quantity}'/>";
        $tpl .= '</Order>';
        $tpl .= "<OrderOption cargo='服装' custid='{$this->SF_CUSTID}'/>";
        $tpl .= '</OrderOption>';
        $tpl .= '</Body>';
        $tpl .= '</Request>';

        return $tpl;
    }

    private function tplRouteService(array $conf)
    {
        //1.取值
        $custid = isset($conf['custid']) ? $conf['custid'] : 'BSPdevelop';
        $tracking_type = isset($conf['tracking_type']) ? $conf['tracking_type'] : 1;
        $tracking_number = isset($conf['tracking_number']) ? $conf['tracking_number'] : '';
        //2.校验
        if (empty($tracking_number)) {
            return ''; //订单号不能为空
        }
        //3.tpl
        $tpl = "<Request service='RouteService' lang='zh-CN'>";
        $tpl .= "<Head> {$custid}</Head>";
        $tpl .= '<Body>';
        $tpl .= "<RouteRequest tracking_type='{$tracking_type}' tracking_number='{$tracking_number}'/>";
        $tpl .= '</Body>';

        return $tpl;
    }
}
