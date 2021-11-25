<?php

return [
    'timeout' => 5.0,

    'middleware' => [],

    'code' => [
        'length' => 6,
        'interval' => 60, // 发送间隔，s
    ],
    // 默认发送配置
    'default' => [
        // 网关调用策略，默认：顺序调用
        // 'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,
        'strategy' => \Overtrue\EasySms\Strategies\RandomStrategy::class,

        // 各场景可用的发送网关
        'gateways' => [
            // 默认
            'default' => [
                'qcloud',
                'aliyun',
            ],
            //登录验证
            'login-verify' => [
                'aliyun',
                'qcloud'
            ],
            // 充值密码
            'reset-password' => [
                'qcloud',
                'aliyun'
            ],
        ],
    ],
    // 可用的网关配置, see: https://github.com/overtrue/easy-sms
    'gateways' => [
        'aliyun' => [
            'access_key_id' => env('ALIYUN_ACCESS_ID'),
            'access_key_secret' => env('ALIYUN_ACCESS_KEY'),
            'sign_name' => 'BigSoft',
        ],
        'qcloud' => [
            'sdk_app_id' => '',
            'secret_id' => env('QCLOUD_ACCESS_ID'),
            'secret_key' => env('QCLOUD_ACCESS_KEY'),
            'sign_name' => '大官人科技'
        ],


        'errorlog' => [
            'file' => storage_path('easy-sms.log'),
        ],
    ],
    'templates' => [
//        'default' => [
//            'aliyun' => [
//                'template_id' => '',
//                'content' => ""
//            ],
//            'qcloud' => [
//                'template_id' => '',
//                'content' => ""
//            ]
//        ],
//        'reset-password' => [
//            'qcloud' => [
//                'template_id' => '',
//                'content' => ""
//            ],
//            'aliyun' => [
//                'template_id' => '',
//                'content' => ""
//            ]
//        ],
    ]
];
