<?php

return [
    'name' => '高级语言',
    'description' => '多语言内容的高级语言功能',
    'import' => [
        'rules' => [
            'id' => '需要提供 ID，且必须为有效的 ID。',
            'name' => '需要提供名称，且必须为不超过 255 个字符的字符串。',
            'description' => '如果提供描述，则其长度不得超过 400 个字符。',
            'content' => '如果提供内容，则其长度不得超过 300,000 个字符。',
            'location' => '如果提供位置信息，则其长度不得超过 255 个字符。',
            'floor_plans' => '如果提供楼层平面图，则必须为有效的字符串。',
            'faq_schema_config' => '如果提供常见问题架构配置，则必须为有效的字符串。',
            'faq_ids' => '如果提供常见问题 ID，则必须为有效的数组。',
        ],
    ],
    'export' => [
        'total' => '总计',
    ],
    'import_model_translations' => ':model 翻译',
    'export_model_translations' => ':model 翻译',
    'import_description' => '从 CSV/Excel 文件导入 :name 的翻译。',
    'export_description' => '将 :name 的翻译导出到 CSV/Excel 文件。',
];
