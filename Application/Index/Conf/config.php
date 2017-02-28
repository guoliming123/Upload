<?php
return array(
    'TOKEN' => 'poiuyt',
    'appid' => 'wxa298165a1aba6e6b',
    'appsecret' => '93ccd16985755607010fe48bdd0bb49f',
    'DOMAIN_NAME' => 'didi.sinmore.com.cn',

    'IMAGE_PATH' => '/Uploads/', //保存根路径

    /* 模板相关配置 */
    'TMPL_PARSE_STRING' => array(
        '__STATIC__' => __ROOT__ . '/Public/static',
        '__ADDONS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/Addons',
        '__IMG__' => __ROOT__ . '/Public/' . MODULE_NAME . '/images',
        '__CSS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/css',
        '__JS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/js',
    )
);