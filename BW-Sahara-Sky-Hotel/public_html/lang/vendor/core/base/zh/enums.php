<?php

return [
    'statuses' => [
        'draft' => '草稿',
        'pending' => '待处理',
        'published' => '已发布',
    ],
    'system_updater_steps' => [
        'download' => '下载更新文件',
        'update_files' => '更新系统文件',
        'update_database' => '更新数据库',
        'publish_core_assets' => '发布核心资源',
        'publish_packages_assets' => '发布软件包资源',
        'clean_up' => '清理系统更新文件',
        'done' => '系统更新成功',
        'unknown' => '未知步骤',
        'messages' => [
            'download' => '正在下载更新文件...',
            'update_files' => '正在更新系统文件...',
            'update_database' => '正在更新数据库...',
            'publish_core_assets' => '正在发布核心资源...',
            'publish_packages_assets' => '正在发布软件包资源...',
            'clean_up' => '正在清理系统更新文件...',
            'done' => '完成！您的浏览器将在 30 秒内自动刷新。',
        ],
        'failed_messages' => [
            'download' => '无法下载更新文件',
            'update_files' => '无法更新系统文件',
            'update_database' => '无法更新数据库',
            'publish_core_assets' => '无法发布核心资源',
            'publish_packages_assets' => '无法发布软件包资源',
            'clean_up' => '无法清理系统更新文件',
        ],
        'success_messages' => [
            'download' => '成功下载更新文件。',
            'update_files' => '成功更新系统文件。',
            'update_database' => '成功更新数据库。',
            'publish_core_assets' => '成功发布核心资源。',
            'publish_packages_assets' => '成功发布软件包资源。',
            'clean_up' => '成功清理系统更新文件。',
        ],
    ],
];
