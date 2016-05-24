wgate-php-sdk
==============

微信之门平台API PHP版本SDK.

微信之门官方网站: http://www.weixingate.com

### 下载

```sh
git clone https://github.com/cnhuye/wgate-php-sdk.git
```

微信之门 的 wgate sdk 只控制微信之门相关的API.

连接微信的API请下载微信之门改版的 wechat-php-sdk :

```sh
git clone https://github.com/cnhuye/wechat-php-sdk.git
```
详情请查看: https://github.com/cnhuye/wechat-php-sdk

### 调用方式

```php
  require_once 'wgate-php-sdk/wgate.class.php';
  
  $options = array(
    "key"=>$wgate_key, // 微信之门中生成的KEY
    "secret"=>$wgate_secret, // 相应的secret
    "appid"=>$appid,  // 公众号的APPID
    "weixin_account_id"=>$wgate_weixin_account_id // 微信之门中公众号对应的ID
  );
  $wgate = new WGate($options);
  
  
  // wechat sdk 配合 wgate sdk
  $weObj = new Wechat(["wgate"=>$wgate]);
  
```

### 方法

* getWgateToken($key='',$secret="",$token='')  // 获取 wgate_token.  
* getOauthUrl($options) // 使用自定义服务号, 得到微信之门授权URL. $options为array.
  需要设置微信中oAuth授权域名为微信之门域名. 具体请看微信之门文档
* getOauthUserInfo($code) // 根据授权返回的code获得当前用户信息.
* getWgateOauthUrl($options) // 没有服务号, 使用用微信之门授权网关.
* getWgateOauthUserInfo($code) // 根据 wgate_oauth 接口授权返回的code 获得用户信息
* getAccessToken() // 获取微信access_token
* getAccessTokenExpireTime() // 获取上一步得到的access_token 过期时间
* createPayment($options) // 创建微信支付. 返回创建的支付 payment_id
* getPaymentUrl($payment_id) // 根据上一步创建的支付 payment_id 得到支付页面URL
* verifyPaymentNotify($params) // 校验用户支付完成后, 后端返回的支付通知
* getPaymentNotifyReturn($successed=true,$msg="") // 生成返回给通知的内容
* getJsApiTicket() // 得到jsapi_ticket
* getJsApiTicketExpireTime() // 得到 jsapi_ticket 过期时间
* getJsSign($url, $timestamp=0, $noncestr='')  // 根据当前URL获得 js sign
* getCardApiTicket() // 获得卡券 api ticket
* getCardApiTicketExpireTime() // 获得卡券 api ticket 过期时间
* getCardSign($shopid=null, $card_type=null, $cardid=null, $timestamp=0, $noncestr='') // 获得卡券签名
* getCardExtSign($cardid=null, $openid=null, $code=null, $timestamp=0, $noncestr='') // 获得卡券签名 ext 信息

WGate 为所有需要绑定的微信接口提供了一个中央控制器, 例如 access_token, OAuth, js ticket等.

并且给不同开发者提供了一个公众号下的不同 key/secret 作为接入凭据.

不同开发者共享中央控制器中的内容. 因此需要检查相应的 expire time, 如过期, 需要重新获取.

不过微信之门改版的 wechat-php-sdk 已经加入了自动获取更新 access_token 的功能, 如果过期会自动重试, 并重新调用接口. 详情请见 https://github.com/cnhuye/wechat-php-sdk 主动接口方法部分说明.



### 错误处理

当方法返回 false 时, 说明调用微信之门接口出错. 可使用使用errMsg属性获取微信之门服务器返回的错误消息.
```php
$error = $wgate->errMsg;
echo $error;
```


### 示例

```php
... 上面调用初始化 ...

// 跳转绑定服务号授权
$url = $wgate->getOauthUrl(["info"=>"force", "back"=> 'http://..."]);

// 根据返回的 code 得到用户信息
$userinfo = $wgate->getOauthUserInfo($_GET["code"]);

// 获取微信 access_token
$access_token = $wgate->getAccessToken();

// 获取 wgate_token
echo $wgate->getWgateToken();

// 调用出错时获取错误信息
$result = $wgate->getJsApiTicket();
if($result==false){
  echo $wgate->errMsg;
}
```

License
-------
This is licensed under the GNU LGPL, version 2.1 or later.   
For details, see: http://creativecommons.org/licenses/LGPL/2.1/



