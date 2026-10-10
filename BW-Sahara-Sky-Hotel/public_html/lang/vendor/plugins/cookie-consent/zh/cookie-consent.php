<?php

return [
    'theme_options' => [
        'name' => 'Cookie 同意',
        'description' => 'Cookie 同意设置',
        'enable' => '启用 Cookie 同意',
        'message' => '消息',
        'button_text' => '按钮文字',
        'max_width' => '最大宽度（px）',
        'background_color' => '背景颜色',
        'text_color' => '文字颜色',
        'learn_more_url' => '了解更多 URL',
        'learn_more_text' => '了解更多文字',
        'style' => '样式',
        'full_width' => '全宽',
        'minimal' => '极简',
        'show_reject_button' => '显示拒绝按钮',
        'show_reject_button_helper' => '启用后，用户将看到拒绝所有 Cookie 的按钮。',
        'show_customize_button' => '显示自定义偏好按钮',
        'show_customize_button_helper' => '启用后，用户将看到自定义 Cookie 偏好的按钮，使其符合 GDPR 要求。',
    ],
    'message' => '允许 Cookie 将改善您在此网站上的体验。',
    'button_text' => '接受 Cookie',
    'reject_text' => '拒绝',
    'customize_text' => '自定义偏好',
    'save_text' => '保存偏好',
    'cookie_categories' => [
        'essential' => [
            'name' => '必要',
            'description' => '这些 Cookie 对于网站正常运行至关重要。',
        ],
        'analytics' => [
            'name' => '分析',
            'description' => '这些 Cookie 帮助我们了解访问者如何与网站互动。',
        ],
        'marketing' => [
            'name' => '营销',
            'description' => '这些 Cookie 用于投放个性化广告。',
        ],
    ],
];
