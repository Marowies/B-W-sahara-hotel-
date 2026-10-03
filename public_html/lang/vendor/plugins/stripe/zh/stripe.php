<?php

return [
    'webhook_secret' => 'Webhook 密钥',
    'webhook_setup_guide' => [
        'step_1_description' => '访问 :link，然后在 Developers 选项卡的 Webhooks 部分点击 Add Endpoint 按钮。',
        'step_2_description' => '选择 payment_intent.succeeded 事件，并在 Endpoint URL 字段中输入以下 URL：:url',
        'step_3_description' => '点击 Add Endpoint 按钮以保存 Webhook。',
        'step_3_label' => '添加端点',
        'step_4_description' => '从 Webhook Details 部分复制 Signing Secret 值，并将其粘贴到设置页面的 Payment 选项卡中 Stripe 部分的 Stripe Webhook Secret 字段中。',
        'step_4_label' => '复制签名密钥',
        'step_2_label' => '选择事件并配置端点',
        'title' => 'Stripe Webhook 设置指南',
        'description' => '按照以下步骤设置 Stripe Webhook',
        'step_1_label' => '登录 Stripe 控制台',
    ],
    'no_payment_charge' => '没有支付费用。请重试！',
    'payment_failed' => '支付失败！',
    'payment_type' => '支付类型',
];
