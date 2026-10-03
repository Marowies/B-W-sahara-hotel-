<?php

return [
    'settings' => [
        'title' => '优化',
        'description' => '压缩 HTML 输出、内联 CSS、删除注释...',
        'enable' => '是否启用页面速度优化？',
    ],
    'collapse_white_space' => '折叠空白',
    'collapse_white_space_description' => '此过滤器通过删除不必要的空白来减少 HTML 文件中传输的字节数。',
    'elide_attributes' => '省略属性',
    'elide_attributes_description' => '当指定值等于该属性的默认值时，此过滤器会从标签中删除属性，从而减少 HTML 文件的传输大小。这可以节省少量字节，并可能通过规范化受影响的标签使文档更易于压缩。',
    'inline_css' => '内联 CSS',
    'inline_css_description' => '此过滤器通过将 CSS 移动到头部，将标签的内联 style 属性转换为类。',
    'insert_dns_prefetch' => '插入 DNS 预取',
    'insert_dns_prefetch_description' => '此过滤器在 HEAD 中注入标签，使浏览器能够进行 DNS 预取。',
    'remove_comments' => '删除注释',
    'remove_comments_description' => '此过滤器可消除 HTML、JS 和 CSS 注释。该过滤器通过删除注释来减少 HTML 文件的传输大小。根据 HTML 文件的不同，此过滤器可以显著减少网络上传输的字节数。',
    'remove_quotes' => '删除引号',
    'remove_quotes_description' => '此过滤器会从 HTML 属性中删除不必要的引号。尽管各种 HTML 规范要求使用引号，但当属性的值由某些字符子集（字母数字和一些标点字符）组成时，浏览器允许省略引号。',
    'defer_javascript' => '延迟执行 JavaScript',
    'defer_javascript_description' => '推迟 HTML 中 JavaScript 的执行。如有必要，可在某些脚本中取消推迟，使用 data-pagespeed-no-defer 作为脚本属性来取消推迟。',
];
