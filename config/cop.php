<?php

return [

    /**
     * 当前运行环境
     * 开发环境：development
     * 生产环境：production
     */

    'current_environment'=>'development',

    /**
     * 环境配置
     * 注意：正常环境下base_uri不用修改，除非cosco调整
     */
    'environments' => [
        /**
         * 开发环境下的配置
         */
        'development'=>[
            'secret_key' => '',
            'api_key' => '',
            'base_uri'=>'https://api-pp.lines.coscoshipping.com'
        ],
        /**
         * 生产环境下的鉴权信息
         */
        'production'=>[
            'secret_key' => '',
            'api_key' => '',
            'base_uri'=>'https://api.lines.coscoshipping.com'
        ],
    ]
];
