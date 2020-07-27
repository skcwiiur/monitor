<?php

namespace Call\Controller;

use Org\Wechat\WechatApi;
use Think\Controller;

/**
 * 默认首页Controller
 * 显示框架基本信息，具体项目请新建模块
 */
class IndexController extends Controller {
	public function index() {
		S('et', 1);
		header('content-type:text/html;charset=uft-8');
		header('location:/Public/dist/');

	}

	public function testDB() {
		$database = getDB();
		$query = $database->select('user_users', 't');
		$query->fields('t');
		$result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
		var_dump($result);
	}

	public function phpinfo() {
		phpinfo();
	}

	public function test() {
		$result = curl_request("http://ba.fangyue.net/icp.php?token=8492ce0f210351d7911cfea00177bc84&domain=baidu.com");
		if (OP_SUCCESS !== $result['status']) {
			print_r($result);
			exit;
		}

		$temp = preg_replace('/[\x00-\x1F]|\xef|\xbb|\xbf/i', '', $result['data']);
		$temp = json_decode($temp, true);
		print_r($temp);
		exit;
	}

	public function dfDoOrder() {
		$order = [
			'express_type' => 1,
			'orderid' => "789uwarw9015",
			'd_province' => "江苏省",
			'd_city' => "无锡市",
			'd_contact' => "宇仨",
			'd_tel' => "15265128838",
			'd_address' => "江苏省江阴市澄江街道1132号",
			'parcel_quantity' => 1,
			'pay_method' => 1,
			'account' => "JYSMFJYDQ",
		];
		print_r(\Org\Shipping\sf::doOrder($order));

	}

	public function wechatJs() {
		$jsPackage = WechatApi::getSignPackage();
		$this->assign('signPackage', $jsPackage['data']);
		$this->display();
	}

	public function imgResize() {
		if (!IS_POST) {
			$this->display();
			exit;
		}
		$upload = new \Think\Upload();
		$upload->exts = ['jpg', 'png'];
		$upload->autoSub = FALSE;
		$info = $upload->upload();
		$excel_path = $upload->rootPath . $info['test']['savename'];
		Img($excel_path, 870, 100, 1);
	}

	public function getWxUser() {
		$result = WechatApi::userGetList();
		foreach ($result['data']['data']['openid'] as $value) {
			print_r(WechatApi::userInfo($value));
			print_r('</br>');
		}
	}

	/**
	 * 购买模版消息通知
	 * @static
	 * @access public
	 * @param $touser 目标用户的openid
	 * @param $product 购买的商品
	 * @param $price 购买的金额
	 * @param $time 购买的时间
	 * @param $url 跳转url
	 */
	public function purchaseSuccessNotify($touser, $url = 'http://www.baidu.com/') {
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

	public function receive() {
		print_r(I('post.'));
	}
}
