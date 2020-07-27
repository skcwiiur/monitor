<?php

return [
    /*     * ***********************************附加设置********************************** */
    'SHOW_PAGE_TRACE' => false, // 是否显示调试面板
    'URL_CASE_INSENSITIVE' => false, // url区分大小写
    'TAGLIB_BUILD_IN' => 'Cx,Backstage\Tag\My', // 加载自定义标签
    'LOAD_EXT_CONFIG' => '', // 加载网站设置文件
    'TMPL_PARSE_STRING' => [// 定义常用路径
        '__PUBLIC__' => __ROOT__ . '/Public/Default',
        '__ASSETS__' => __ROOT__ . '/Public/Default/assets',
        '__STATIC__' => __ROOT__ . '/Public/Default/static',
    ],
    'TMPL_ACTION_ERROR'     =>  'Index/dispatch_jump',
    'TMPL_ACTION_SUCCESS'   =>  'Index/dispatch_jump',
    /*     * *********************************auth设置********************************* */
    'AUTH_CONFIG' => [
        'AUTH_USER' => 'admin_users',          //用户信息表
        'SUPER_ADMIN' => 88,                   //超级管理员uid
    ],
    /*     * **********************************缓存设置********************************* */
    'REDIS_HOST' => '172.16.11.146',
    'REDIS_PORT' => 6379,
    'REDIS_DB' => 15, //选择redis中的一个db
    //'DATA_CACHE_TYPE' => 'Redis', //Memcached
    'DATA_CACHE_PREFIX' => 'back_',
    'DATA_CACHE_TIME' => 3600 * 24 * 7,
    /*     * *********************************邮件服务器********************************* */
    'EMAIL_FROM_NAME' => '', // 发件人
    'EMAIL_SMTP' => '', // smtp
    'EMAIL_USERNAME' => '', // 账号
    'EMAIL_PASSWORD' => '', //密码
    /*     * *********************************其他配置********************************* */
    'NEED_UPLOAD_OSS' => [ // 需要上传的目录
        '/Public/Upload/avatar',
        '/Public/Upload/cover',
        '/Public/Upload/image/webuploader',
        '/Public/Upload/video',
    ],
    'VERIFY_CONFLATION' => 'rpd',
    'DEFAULT_PAGE_SIZE' => 12,

];
