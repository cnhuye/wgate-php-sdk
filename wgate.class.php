<?php
/**
 *	微信之门PHP-SDK
 *  @author  huye <cnhuye@gmail.com>
 *  @link https://github.com/cnhuye/wgate-php-sdk.git
 *  @version 1.0
 *  usage:
 *   $options = array(
 *			'key'=>'xxxxxxx', //微信之门中创建的key
 *			'secret'=>'xxxxxxxxxxxxxxxxxxx' //key 相应的 secret
 *			'appid'=>'wxxxxxxxxxxx', //公众号的 app id
 *			'weixin_account_id'=>'xxx' //公众号在微信之门平台上相应的 id
 *		);
 *	 $wgate = new WGate($options);
 * 
 */
class WGate
{
  const OAUTH_GATE_URL = 'http://www.weixingate.com/api/v1/oauth';
  const WGATE_OAUTH_GATE_URL = 'http://www.weixingate.com/api/v1/wgate_oauth';

	// for local env
	// const WGATE_HOST = 'http://huye.ngrok.natapp.cn/members';
 //  const API_URL_PREFIX = 'http://huye.ngrok.natapp.cn/members/api/v1';
 //  const WGATE_PAYMENT_URL = "http://huye.ngrok.natapp.cn/members/wx/pays/";

	// for production
	const WGATE_HOST = 'http://www.weixingate.com';
  const API_URL_PREFIX = 'http://api.weixingate.com/v1';
  const WGATE_PAYMENT_URL = "http://www.weixingate.com/members/wx/pays/";

  const API_OAUTH_USERINFO_URL = '/oauth/userinfo';

  const API_WGATE_OAUTH_VERIFY_URL = '/wgate_oauth/verify';
  const API_WGATE_OAUTH_USERINFO_URL = '/wgate_oauth/userinfo';

  const API_WGATE_TOKEN_URL = '/weixin_account/wgate_token';
  const API_TOKEN_URL = '/weixin_account/token';
  const API_JSAPI_TICKET_URL = '/weixin_account/jsapi_ticket';
  const API_CARD_API_TICKET_URL = '/weixin_account/card_api_ticket';
  const API_PAYMENTS_URL = '/weixin_account/payments';
  const API_PAYMENT_SHOW_URL = '/weixin_account/payments/show';


	private $appid;
	private $weixin_account_id;
	private $key;
	private $secret;
	private $wgate_token;
	private $access_token;
	private $access_token_expire_at;
	private $postxml;
	private $jsapi_ticket;

	public $debug =  false;
	public $errCode = 0;
	public $errMsg = "";

	public function __construct($options)
	{
		$this->appid = isset($options['appid'])?$options['appid']:'';
		$this->weixin_account_id = isset($options['weixin_account_id'])?$options['weixin_account_id']:'';
		$this->key= isset($options['key'])?$options['key']:'';
		$this->secret = isset($options['secret'])?$options['secret']:'';
		$this->debug = isset($options['debug'])?$options['debug']:false;
	}

	/**
	 * 获取wgate_token
	 * @param string $key 如在类初始化时已提供，则可为空
	 * @param string $secret 如在类初始化时已提供，则可为空
	 * @param string $token 手动指定wgate_token，非必要情况不建议用
	 */
	public function getWgateToken($key='',$secret="",$token=''){
		if (!$key || !$secret) {
			$key = $this->key;
			$secret = $this->secret;
		}
		if ($token) { //手动指定token，优先使用
		    $this->wgate_token=$token;
		    return $this->wgate_token;
		}

		$authname = 'wgate_token'.$key;
		if ($rs = $this->getCache($authname))  {
			$this->wgate_token = $rs;
			return $rs;
		}

    $timestamp = time();
    $verify = md5($timestamp.$key.$secret);
    $params = http_build_query(["weixin_account_id"=>$this->weixin_account_id,"key"=>$key,"timestamp"=>$timestamp,"verify"=>$verify]);
		$result = $this->http_get(self::API_URL_PREFIX.self::API_WGATE_TOKEN_URL.'?'.$params);
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || isset($json['error'])) {
				$this->errCode = $json['error'];
				$this->errMsg = $json['error'];
				return false;
			}
			$this->wgate_token = $json['token'];
			$expire = $json['expire_at'] ? intval($json['expire_at'])-100 : 3600;
			$this->setCache($authname,$this->wgate_token,$expire);
			return $this->wgate_token;
		}
		return false;
	}



	/**
	 * 获取授权网关跳转URL
	 * @param array $options 授权网关所需参数, http://doc.weixingate.com/reference/auth/
	 */
	public function getOauthUrl($options){
    $qs = http_build_query(array_merge($options,["weixin_account_id"=>$this->weixin_account_id]));
    $url = self::OAUTH_GATE_URL."?".$qs;
    return $url;
	}




	/**
	 * 根据 code 获取授权后用户信息
	 * @param string $wgateid 
	 */
	public function getOauthUserInfo($code){
		if (!$this->wgate_token && !$this->getWgateToken()) return false;
    $qs = http_build_query(["code"=>$code,"token"=>$this->wgate_token]);
    $result = $this->http_get(self::API_URL_PREFIX.self::API_OAUTH_USERINFO_URL."?".$qs);
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || isset($json['error'])) {
				$this->errCode = $json['error'];
				$this->errMsg = $json['error'];
				return false;
			}
			return $json;
		}
		return false;

	}



	/**
	 * 获取授权网关跳转URL
	 * @param array $options 授权网关所需参数, http://doc.weixingate.com/reference/auth/
	 */
	public function getWgateOauthUrl($options){
    $qs = http_build_query(array_merge($options,[]));
    $url = self::WGATE_OAUTH_GATE_URL."?".$qs;
    return $url;
	}




	/**
	 * 根据 wgateid 获取用户信息
	 * @param string $wgateid 
	 */
	public function getWgateOauthUserInfo($code){
    $qs = http_build_query(["code"=>$code]);
    $result = $this->http_get(self::API_URL_PREFIX.self::API_WGATE_OAUTH_USERINFO_URL."?".$qs);
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || isset($json['error'])) {
				$this->errCode = $json['error'];
				$this->errMsg = $json['error'];
				return false;
			}
			return $json;
		}
		return false;

	}












	/**
	 * 获取微信access_token
	 * @param string $token 手动指定access_token，非必要情况不建议用
	 */
	public function getAccessToken($token=''){
		if ($token) { //手动指定token，优先使用
		    $this->access_token=$token;
		    return $this->access_token;
		}

		if (!$this->wgate_token && !$this->getWgateToken()) return false;


		$authname = 'wgate_access_token'.$this->appid;
		if ($rs = $this->getCache($authname))  {
			$this->access_token = $rs;
			return $rs;
		}

    $qs = http_build_query(["token"=>$this->wgate_token,"weixin_account_id"=>$this->weixin_account_id]);
		$result = $this->http_get(self::API_URL_PREFIX.self::API_TOKEN_URL.'?'.$qs);


		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || isset($json['error'])) {
				$this->errCode = $json['error'];
				$this->errMsg = $json['error'];
				return false;
			}
			$this->access_token = $json['token'];
			$expire = $json['expire_at'];
			$this->access_token_expire_at = $expire;
			$this->setCache($authname,$this->access_token,time()-intval($expire));
			return $this->access_token;
		}
		return false;
	}


	/**
	 * 获取微信 access_tokem 过期时间戳
	 */
	public function getAccessTokenExpireTime(){
		if ($this->access_token_expire_at && $this->access_token_expire_at>time()) {
		    return $this->access_token_expire_at;
		}
		null;
	}


	/**
	 * 创建Payment, 获取payment_id
	 * @param array $options 支付所需参数, 同微信支付参数
	 */
	public function createPayment($options){
		if (!$this->wgate_token && !$this->getWgateToken()) return false;
    
    $options = array_merge($options,["token"=>$this->wgate_token,"weixin_account_id"=>$this->weixin_account_id]);
    $sign = $this->genSignature($options,"md5","&secret=".$this->secret);
    $options["sign"] = $sign;
    $qs = http_build_query($options);
    $result = $this->http_post(self::API_URL_PREFIX.self::API_PAYMENTS_URL,$qs);
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || isset($json['error'])) {
				$this->errCode = $json['error'];
				$this->errMsg = $json['error'];
				return false;
			}
      return $json["payment_id"];
		}
		return false;
	}




	/**
	 * 根据payment_id, 返回payment跳转URL
	 * @param string $payment_id 支付所需参数, 同微信支付参数
	 */
	public function getPaymentUrl($payment_id){
		return self::WGATE_PAYMENT_URL.$payment_id;
	}


	/**
	 * 根据payment_id, 返回payment跳转URL
	 * @param string $payment_id 支付所需参数, 同微信支付参数
	 */
	public function verifyPaymentNotify($params){


		$signature = $params["sign"];
		unset($params["sign"]);

		ksort($params);
		$paramstring = "";
		foreach($params as $key => $value)
		{
			if(strlen($paramstring) == 0)
				$paramstring .= $key . "=" . $value;
			else
				$paramstring .= "&" . $key . "=" . $value;
		}
		$paramstring .= "&secret=" . $this->secret;
// print_r($paramstring."\n");
		$tmpStr = md5($paramstring);
// echo $tmpStr."|".$signature;
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}

	}




	/**
	 * 返回 payment notify 的返回内容
	 * @param string $payment_id 支付所需参数, 同微信支付参数
	 */
	public function getPaymentNotifyReturn($successed=true,$msg=""){
		return "<xml><return_code>".($successed ? "SUCCESS" : "FAIL")."</return_code><return_msg>".$msg."</return_msg></xml>";
	}






	/**
	 * 获取JSAPI授权TICKET
	 * @param string $jsapi_ticket 手动指定jsapi_ticket，非必要情况不建议用
	 */
	public function getJsApiTicket($jsapi_ticket=''){
		if (!$this->wgate_token && !$this->getWgateToken()) return false;
		if ($jsapi_ticket) { //手动指定token，优先使用
		    $this->jsapi_ticket = $jsapi_ticket;
		    return $this->jsapi_ticket;
		}
		$authname = 'wgate_jsapi_ticket'.$this->appid;
		if ($rs = $this->getCache($authname))  {
			$this->jsapi_ticket = $rs;
			return $rs;
		}
    $qs = http_build_query(["token"=>$this->wgate_token,"weixin_account_id"=>$this->weixin_account_id]);
		$result = $this->http_get(self::API_URL_PREFIX.self::API_JSAPI_TICKET_URL.'?'.$qs);
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || isset($json['error'])) {
				$this->errCode = $json['error'];
				$this->errMsg = $json['error'];
				return false;
			}
			$this->jsapi_ticket = $json['ticket'];
			$expire = $json['expire_at'];
			$this->jsapi_ticket_expire_at = $expire;
			$this->setCache($authname,$this->jsapi_ticket,time()-intval($expire));
			return $this->jsapi_ticket;
		}
		return false;
	}

	/**
	 * 获取微信 jsapi_ticket 过期时间戳
	 */
	public function getJsApiTicketExpireTime(){
		if ($this->jsapi_ticket_expire_at && $this->jsapi_ticket_expire_at>time()) {
		    return $this->jsapi_ticket_expire_at;
		}
		null;
	}


	/**
	 * 获取JsApi使用签名
	 * @param string $url 网页的URL，自动处理#及其后面部分
	 * @param string $timestamp 当前时间戳 (为空则自动生成)
	 * @param string $noncestr 随机串 (为空则自动生成)
	 * @return array|bool 返回签名字串
	 */
	public function getJsSign($url, $timestamp=0, $noncestr=''){
	    if (!$this->jsapi_ticket && !$this->getJsApiTicket($this->appid) || !$url) return false;
	    if (!$timestamp)
	        $timestamp = time();
	    if (!$noncestr)
	        $noncestr = $this->generateNonceStr();
	    $ret = strpos($url,'#');
	    if ($ret)
	        $url = substr($url,0,$ret);
	    $url = trim($url);
	    if (empty($url))
	        return false;
	    $arrdata = array("timestamp" => $timestamp, "noncestr" => $noncestr, "url" => $url, "jsapi_ticket" => $this->jsapi_ticket);
	    $sign = $this->genSignature($arrdata);
	    if (!$sign)
	        return false;
	    $signPackage = array(
	            "appid"     => $this->appid,
	            "noncestr"  => $noncestr,
	            "timestamp" => $timestamp,
	            "url"       => $url,
	            "signature" => $sign
	    );
	    return $signPackage;
	}





	/**
	 * 获取卡券API授权TICKET
	 * @param string $jsapi_ticket 手动指定jsapi_ticket，非必要情况不建议用
	 */
	public function getCardApiTicket($card_api_ticket=''){
		if (!$this->wgate_token && !$this->getWgateToken()) return false;
		if ($card_api_ticket) { //手动指定token，优先使用
		    $this->card_api_ticket = $card_api_ticket;
		    return $this->card_api_ticket;
		}
		$authname = 'wgate_card_api_ticket'.$this->appid;
		if ($rs = $this->getCache($authname))  {
			$this->card_api_ticket = $rs;
			return $rs;
		}
    $qs = http_build_query(["token"=>$this->wgate_token,"weixin_account_id"=>$this->weixin_account_id]);
		$result = $this->http_get(self::API_URL_PREFIX.self::API_CARD_API_TICKET_URL.'?'.$qs);
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || isset($json['error'])) {
				$this->errCode = $json['error'];
				$this->errMsg = $json['error'];
				return false;
			}
			$this->card_api_ticket = $json['ticket'];
			$expire = $json['expire_at'];
			$this->card_api_ticket_expire_at = $expire;
			$this->setCache($authname,$this->card_api_ticket,time()-intval($expire));
			return $this->card_api_ticket;
		}
		return false;
	}

	/**
	 * 获取微信 jsapi_ticket 过期时间戳
	 */
	public function getCardApiTicketExpireTime(){
		if ($this->card_api_ticket_expire_at && $this->card_api_ticket_expire_at>time()) {
		    return $this->card_api_ticket_expire_at;
		}
		null;
	}




	/**
	 * 获取CardApi使用签名
	 * @param string $url 网页的URL，自动处理#及其后面部分
	 * @param string $timestamp 当前时间戳 (为空则自动生成)
	 * @param string $noncestr 随机串 (为空则自动生成)
	 * @return array|bool 返回签名字串
	 */
	public function getCardSign($shopid=null, $card_type=null, $cardid=null, $timestamp=0, $noncestr=''){

	    if (!$this->card_api_ticket && !$this->getCardApiTicket($this->appid)) return false;
	    if (!$timestamp)
	        $timestamp = time();
	    if (!$noncestr)
	        $noncestr = $this->generateNonceStr();


	    $arrdata = array($this->appid, $shopid, $card_type,$cardid, $timestamp, $noncestr, $this->jsapi_ticket);
	    $sign = $this->genCardSignature($arrdata);
	    if (!$sign)
	        return false;
	    $signPackage = array(
	            "appid"     => $this->appid,
	            "noncestr"  => $noncestr,
	            "timestamp" => $timestamp,
	            "signature" => $sign
	    );
	    return $signPackage;
	}



	/**
	 * 获取CardApiTicket使用签名 cardExt信息
	 * @param string $url 网页的URL，自动处理#及其后面部分
	 * @param string $timestamp 当前时间戳 (为空则自动生成)
	 * @param string $noncestr 随机串 (为空则自动生成)
	 * @param string $appid 用于多个appid时使用,可空
	 * @return array|bool 返回签名字串
	 */
	public function getCardExtSign($cardid=null, $openid=null, $code=null, $timestamp=0, $noncestr=''){
	    if (!$this->card_api_ticket && !$this->getCardApiTicket($this->appid)) return false;
	    if (!$timestamp)
	        $timestamp = time();
	    if (!$noncestr)
	        $noncestr = $this->generateNonceStr();
	    
	    $arrdata = array($this->appid,$openid, $code, $cardid, $timestamp, $noncestr, $this->card_api_ticket);
	    $sign = $this->genCardSignature($arrdata);
	    if (!$sign)
	        return false;
	    $signPackage = array(
	            "appid"     => $this->appid,
	            "noncestr"  => $noncestr,
	            "timestamp" => $timestamp,
	            "signature" => $sign
	    );
	    return $signPackage;
	}







	/**
	 * 微信api不支持中文转义的json结构
	 * @param array $arr
	 */
	static function json_encode($arr) {
		$parts = array ();
		$is_list = false;
		//Find out if the given array is a numerical array
		$keys = array_keys ( $arr );
		$max_length = count ( $arr ) - 1;
		if (($keys [0] === 0) && ($keys [$max_length] === $max_length )) { //See if the first key is 0 and last key is length - 1
			$is_list = true;
			for($i = 0; $i < count ( $keys ); $i ++) { //See if each key correspondes to its position
				if ($i != $keys [$i]) { //A key fails at position check.
					$is_list = false; //It is an associative array.
					break;
				}
			}
		}
		foreach ( $arr as $key => $value ) {
			if (is_array ( $value )) { //Custom handling for arrays
				if ($is_list)
					$parts [] = self::json_encode ( $value ); /* :RECURSION: */
				else
					$parts [] = '"' . $key . '":' . self::json_encode ( $value ); /* :RECURSION: */
			} else {
				$str = '';
				if (! $is_list)
					$str = '"' . $key . '":';
				//Custom handling for multiple data types
				if (!is_string ( $value ) && is_numeric ( $value ) && $value<2000000000)
					$str .= $value; //Numbers
				elseif ($value === false)
				$str .= 'false'; //The booleans
				elseif ($value === true)
				$str .= 'true';
				else
					$str .= '"' . addslashes ( $value ) . '"'; //All other things
				// :TODO: Is there any more datatype we should be in the lookout for? (Object?)
				$parts [] = $str;
			}
		}
		$json = implode ( ',', $parts );
		if ($is_list)
			return '[' . $json . ']'; //Return numerical JSON
		return '{' . $json . '}'; //Return associative JSON
	}

	/**
	 * 获取签名
	 * @param array $arrdata 签名数组
	 * @param string $method 签名方法
	 * @return boolean|string 签名值
	 */
	public function genSignature($arrdata,$method="sha1",$suffix=null) {
		if (!function_exists($method)) return false;
		ksort($arrdata);
		$paramstring = "";
		foreach($arrdata as $key => $value)
		{
			if(strlen($paramstring) == 0)
				$paramstring .= $key . "=" . $value;
			else
				$paramstring .= "&" . $key . "=" . $value;
		}
		$paramstring = $paramstring.$suffix;
// print_r($paramstring);
// echo "\n";
		$Sign = $method($paramstring);
		return $Sign;
	}



	/**
	 * 计算签名
	 * @param array $arrdata 签名数组
	 * @param string $method 签名方法
	 * @return boolean|string 签名值
	 */
	public function genCardSignature($arrdata,$method="sha1") {
		if (!function_exists($method)) return false;
		sort($arrdata);
		$paramstring = join("",$arrdata);
		$sign = $method($paramstring);
		return $sign;
	}



	/**
	 * 生成随机字串
	 * @param number $length 长度，默认为16，最长为32字节
	 * @return string
	 */
	public function generateNonceStr($length=16){
		// 密码字符集，可任意添加你需要的字符
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		for($i = 0; $i < $length; $i++)
		{
			$str .= $chars[mt_rand(0, strlen($chars) - 1)];
		}
		return $str;
	}

	/**
	 * 获取微信服务器IP地址列表
	 * @return array('127.0.0.1','127.0.0.1')
	 */
	public function getServerIp(){
		if (!$this->wgate_token && !$this->getWgateToken()) return false;
		$result = $this->http_get(self::API_URL_PREFIX.self::CALLBACKSERVER_GET_URL.'wgate_token='.$this->wgate_token);
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || isset($json['errcode'])) {
				$this->errCode = $json['errcode'];
				$this->errMsg = $json['errmsg'];
				return false;
			}
			return $json['ip_list'];
		}
		return false;
	}

	/**
	 * For weixin server validation
	 */
	private function checkSignature($str='')
	{
        $signature = isset($_GET["signature"])?$_GET["signature"]:'';
	    $signature = isset($_GET["msg_signature"])?$_GET["msg_signature"]:$signature; //如果存在加密验证则用加密验证段
        $timestamp = isset($_GET["timestamp"])?$_GET["timestamp"]:'';
        $nonce = isset($_GET["nonce"])?$_GET["nonce"]:'';

		$token = $this->token;
		$tmpArr = array($token, $timestamp, $nonce,$str);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );

		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}



    /**
     * 日志记录，可被重载。
     * @param mixed $log 输入日志
     * @return mixed
     */
    protected function log($log){
    		if ($this->debug && function_exists($this->logcallback)) {
    			if (is_array($log)) $log = print_r($log,true);
    			return call_user_func($this->logcallback,$log);
    		}
    }



	/**
	 * GET 请求
	 * @param string $url
	 */
	private function http_get($url){
		$oCurl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
			return $sContent;
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}else{
			return false;
		}
	}

	/**
	 * POST 请求
	 * @param string $url
	 * @param array $param
	 * @param boolean $post_file 是否文件上传
	 * @return string content
	 */
	private function http_post($url,$param,$post_file=false){
		$oCurl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		if (is_string($param) || $post_file) {
			$strPOST = $param;
		} else {
			$aPOST = array();
			foreach($param as $key=>$val){
				$aPOST[] = $key."=".urlencode($val);
			}
			$strPOST =  join("&", $aPOST);
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($oCurl, CURLOPT_POST,true);
		curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
			return $sContent;
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}else{
			return false;
		}
	}

	/**
	 * 设置缓存，按需重载
	 * @param string $cachename
	 * @param mixed $value
	 * @param int $expired
	 * @return boolean
	 */
	protected function setCache($cachename,$value,$expired){
		//TODO: set cache implementation
		return false;
	}

	/**
	 * 获取缓存，按需重载
	 * @param string $cachename
	 * @return mixed
	 */
	protected function getCache($cachename){
		//TODO: get cache implementation
		return false;
	}

	/**
	 * 清除缓存，按需重载
	 * @param string $cachename
	 * @return boolean
	 */
	protected function removeCache($cachename){
		//TODO: remove cache implementation
		return false;
	}

}

/**
 * error code
 * 仅用作类内部使用，不用于官方API接口的errCode码
 */
class WgateErrorCode
{
    public static $OK = 0;
    public static $ValidateSignatureError = 40001;
    public static $ParseXmlError = 40002;
    public static $ComputeSignatureError = 40003;
    public static $IllegalAesKey = 40004;
    public static $ValidateAppidError = 40005;
    public static $EncryptAESError = 40006;
    public static $DecryptAESError = 40007;
    public static $IllegalBuffer = 40008;
    public static $EncodeBase64Error = 40009;
    public static $DecodeBase64Error = 40010;
    public static $GenReturnXmlError = 40011;
    public static $errCode=array(
            '0' => '处理成功',
            '40001' => '校验签名失败',
            '40002' => '解析xml失败',
            '40003' => '计算签名失败',
            '40004' => '不合法的AESKey',
            '40005' => '校验AppID失败',
            '40006' => 'AES加密失败',
            '40007' => 'AES解密失败',
            '40008' => '公众平台发送的xml不合法',
            '40009' => 'Base64编码失败',
            '40010' => 'Base64解码失败',
            '40011' => '公众帐号生成回包xml失败'
    );
    public static function getErrText($err) {
        if (isset(self::$errCode[$err])) {
            return self::$errCode[$err];
        }else {
            return false;
        };
    }
}
