<?php

return [
    'login' => [
        'fail' => '用户名或密码错误。',
        'username' => '电子邮件/用户名',
        'password' => '密码',
        'title' => '用户登录',
        'remember' => '记住我？',
        'login' => '登录',
        'placeholder' => [
            'username' => '请输入您的用户名或电子邮件地址',
            'email' => '请输入您的电子邮件地址',
            'password' => '请输入您的密码',
        ],
        'success' => '登录成功！',
        'not_active' => '您的账户尚未激活！',
        'banned' => '此账户已被禁用。',
        'logout_success' => '注销成功！',
        'dont_have_account' => '您在此系统上没有账户，请联系管理员以获取更多信息！',
        'email' => '电子邮件',
    ],
    'forgot_password' => [
        'title' => '忘记密码',
        'message' => '<p>您是否忘记了密码？</p><p>请输入您的电子邮箱。系统将发送一封包含有效链接的邮件，用于重置您的密码。</p>',
        'submit' => '提交',
    ],
    'reset' => [
        'new_password' => '新密码',
        'password_confirmation' => '确认新密码',
        'email' => '电子邮件',
        'title' => '重置您的密码',
        'update' => '更新',
        'wrong_token' => '此链接无效或已过期。请重新使用重置表单再试一次。',
        'user_not_found' => '该用户名不存在。',
        'success' => '密码重置成功！',
        'fail' => '令牌无效，重置密码链接已过期！',
        'reset' => [
            'title' => '通过电子邮件重置密码',
        ],
        'send' => [
            'success' => '一封电子邮件已发送至您的邮箱。请查收并完成此操作。',
            'fail' => '此时无法发送电子邮件。请稍后重试。',
        ],
        'new-password' => '新密码',
        'placeholder' => [
            'new_password' => '请输入您的新密码',
            'new_password_confirmation' => '请确认您的新密码',
        ],
    ],
    'email' => [
        'reminder' => [
            'title' => '通过电子邮件重置密码',
        ],
    ],
    'password_confirmation' => '确认密码',
    'failed' => '失败',
    'throttle' => '频率限制',
    'not_member' => '还不是会员？',
    'register_now' => '立即注册',
    'lost_your_password' => '忘记密码了？',
    'login_title' => '管理员',
    'login_via_social' => '通过社交网络登录',
    'back_to_login' => '返回登录页面',
    'sign_in_below' => '请在下方登录',
    'languages' => '语言',
    'reset_password' => '重置密码',
    'deactivated_message' => '您的账户已被停用。请联系管理员。',
    'password_changed_message' => '您的密码已更改。请使用新密码重新登录。',
    'settings' => [
        'email' => [
            'title' => '访问控制列表（ACL）',
            'description' => 'ACL 邮件配置',
            'templates' => [
                'password_reminder' => [
                    'title' => '重置密码',
                    'description' => '用户请求重置密码时向其发送电子邮件',
                    'subject' => '重置密码',
                    'reset_link' => '重置密码链接',
                    'email_title' => '重置密码说明',
                    'email_message' => '您收到这封邮件是因为我们收到了针对您账户的密码重置请求。',
                    'button_text' => '重置密码',
                    'trouble_text' => '如果您在点击“重置密码”按钮时遇到问题，请将下面的 URL 复制并粘贴到您的浏览器中：<a href=:reset_link>:reset_link</a>，然后粘贴到浏览器中。如果您并未请求重置密码，请忽略此消息；如有任何疑问，请与我们联系。',
                ],
            ],
        ],
    ],
];
