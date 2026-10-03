<?php

return [
    'cache_management' => '缓存管理',
    'cache_management_description' => '清除缓存以使您的网站保持最新。',
    'cache_commands' => '清除缓存命令',
    'commands' => [
        'clear_cms_cache' => [
            'title' => '清除所有 CMS 缓存',
            'description' => '清除 CMS 缓存：数据库缓存、静态块等。当更新数据后看不到更改时，请运行此命令。',
            'success_msg' => '缓存已清理',
        ],
        'refresh_compiled_views' => [
            'title' => '刷新已编译的视图',
            'description' => '清除已编译的视图以使其保持最新。',
            'success_msg' => '视图缓存已刷新',
        ],
        'clear_config_cache' => [
            'title' => '清除配置缓存',
            'description' => '当您在生产环境中进行更改时，可能需要刷新配置缓存。',
            'success_msg' => '配置缓存已清理',
        ],
        'clear_route_cache' => [
            'title' => '清除路由缓存',
            'description' => '清除路由缓存。',
            'success_msg' => '路由缓存已清理',
        ],
        'clear_log' => [
            'title' => '清除日志',
            'description' => '清除系统日志文件',
            'success_msg' => '系统日志已清理',
        ],
    ],
    'optimization' => [
        'title' => '性能优化',
        'optimize' => [
            'title' => '优化网站性能',
            'description' => '缓存配置、路由和视图，以加快加载速度。',
            'button' => '优化',
            'success_msg' => '优化已成功完成',
        ],
        'clear' => [
            'title' => '清除优化缓存',
            'description' => '移除优化缓存以允许更改配置。',
            'button' => '清除',
            'success_msg' => '优化缓存已成功清除',
        ],
        'messages' => [
            'config_cached' => '配置已缓存',
            'routes_cleared' => '路由已清除（缓存需要命令行操作）',
            'views_compiled' => '视图已编译',
            'framework_cache_cleared' => '框架缓存已清除',
            'optimization_completed' => '优化完成：:details',
            'optimization_failed' => '优化失败：:error',
            'clear_failed' => '清除优化失败：:error',
        ],
    ],
    'type' => '类型',
    'description' => '描述',
    'action' => '操作',
    'current_size' => '当前大小',
    'clear_button' => '清除',
    'refresh_button' => '刷新',
    'cache_size_warning' => '您的 CMS 缓存体积较大（>50MB）。清除缓存可能会提升系统性能。',
    'footer_note' => '对网站进行更改后请清除缓存，以确保更改正确显示。',
];
