<?php
/**
 * Created by PhpStorm.
 * User: zhaibaoming
 * Date: 2017/2/6
 * Time: 10:03
 */

/**
 * 网页授权
 *
 * @param $url string  要跳转的页面
 * @author: zhaibaoming
 * @Date: 2017/2/6
 */
function web_authorization($url)
{
    $hurl = urlencode($url);

    $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . C('appid') . '&redirect_uri=' . $hurl . '&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect ';

    header('location:' . $url);
}

/**
 * 返回 微信用户信息
 *
 * @author: zhaibaoming
 * @Date: 2017/2/6
 * @return mixed
 */
function get_userinfo()
{
    $code = $_GET['code'];
    if (!$code) {
        die('参数错误!');
    }

    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . C('appid') . "&secret=" . C('appsecret') . "&code=" . $code . "&grant_type=authorization_code";
    $fileData = file_get_contents($url);
    $file = json_decode($fileData);
    $yonghuurl = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $file->access_token . "&openid=" . $file->openid . "";
    $yong = file_get_contents($yonghuurl);

    return json_decode($yong);
}

/**
 * curl  get 方法
 *
 * @param $url
 * @author: zhaibaoming
 * @Date: 2017/2/6
 * @return mixed
 */
function getCurl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//不输出内容
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}