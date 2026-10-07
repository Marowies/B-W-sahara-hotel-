<?php

return [
    'name' => 'Đồng bộ iCal',
    'sync_calendars' => 'Đồng bộ lịch',
    'sync_all_calendars' => 'Đồng bộ tất cả lịch',
    'external_calendars' => 'Lịch bên ngoài',
    'last_sync' => 'Đồng bộ lần cuối',
    'never' => 'Chưa bao giờ',
    'sync' => 'Đồng bộ',
    'sync_all' => 'Đồng bộ tất cả',
    'download' => 'Tải xuống',
    'edit' => 'Chỉnh sửa iCal cho :name',

    // Statistics
    'total_rooms' => 'Tổng số phòng',
    'synced_calendars' => 'Lịch đã đồng bộ',
    'rooms_with_calendars' => 'Phòng có lịch',
    'last_sync_time' => 'Đồng bộ lần cuối',

    // Table columns
    'calendars' => 'Lịch',
    'status' => 'Trạng thái',
    'not_applicable' => 'Không áp dụng',

    // Status badges
    'synced' => 'Đã đồng bộ',
    'needs_sync' => 'Cần đồng bộ',
    'not_synced' => 'Chưa đồng bộ',
    'no_calendars' => 'Không có lịch',

    // Empty states
    'no_rooms_found' => 'Không tìm thấy phòng',
    'no_rooms_desc' => 'Tạo phòng trước để bắt đầu quản lý đồng bộ lịch.',

    // Actions
    'manage_calendars' => 'Quản lý lịch',

    // Export calendar
    'export_calendar_title' => 'Xuất nguồn cấp lịch',
    'export_calendar_to_platforms' => 'Xuất sang nền tảng bên ngoài',
    'copy_url_instruction' => 'Sao chép URL này và dán vào nền tảng đặt phòng bên ngoài của bạn (Airbnb, Booking.com, v.v.)',
    'how_to_export' => 'Cách xuất?',

    // Empty states for calendars
    'no_external_calendars' => 'Chưa cấu hình lịch bên ngoài',
    'add_calendar_desc' => 'Thêm URL lịch bên ngoài để bắt đầu nhập đặt phòng từ các nền tảng khác.',

    'calendar_name' => 'Tên lịch',
    'calendar_name_placeholder' => 'Nhập tên lịch',
    'calendar_name_help' => 'Đặt tên dễ nhận biết cho lịch này (ví dụ: "Booking.com", "Airbnb")',
    'calendar_url' => 'URL lịch',
    'calendar_url_placeholder' => 'Nhập URL lịch (ví dụ: từ Booking.com, Airbnb, v.v.)',
    'calendar_url_help' => 'Dán URL nguồn cấp iCal từ nền tảng đặt phòng bên ngoài của bạn',

    // Form instructions
    'add_external_calendar_title' => 'Thêm lịch bên ngoài',
    'add_external_calendar_desc' => 'Nhập đặt phòng từ các nền tảng bên ngoài để tránh đặt phòng trùng lặp và giữ lịch trống của bạn được đồng bộ hóa.',
    'supported_platforms' => 'Nền tảng được hỗ trợ',
    'and_more' => 'và nhiều hơn nữa',
    'benefits_title' => 'Lợi ích',
    'benefit_1' => 'Tự động ngăn chặn đặt phòng trùng lặp',
    'benefit_2' => 'Giữ lịch trống được đồng bộ trên các nền tảng',
    'benefit_3' => 'Tiết kiệm thời gian với cập nhật tự động',
    'quick_tip' => 'Mẹo nhanh',
    'tip_desc' => 'Bạn có thể thêm nhiều nguồn cấp lịch từ các nền tảng khác nhau cho cùng một phòng.',
    'view_guide' => 'Xem hướng dẫn từng bước',

    'existing_calendars' => 'Lịch hiện có',
    'export_calendar' => 'Xuất lịch',
    'export_url' => 'URL xuất',
    'export_description' => 'Sử dụng URL này để xuất đặt phòng của bạn sang các nền tảng bên ngoài như Booking.com, Airbnb, v.v.',
    'download_ical_file' => 'Tải xuống tệp iCal',
    'sync_success' => 'Đã đồng bộ thành công :success lịch. Thất bại: :failed',
    'sync_all_success' => 'Đã đồng bộ thành công :success lịch. Thất bại: :failed',

    // Logs
    'logs' => 'Nhật ký đồng bộ iCal',
    'log_details' => 'Nhật ký đồng bộ #:id',
    'message' => 'Tin nhắn',
    'data' => 'Dữ liệu',
    'calendar' => 'Lịch',
    'all_calendars' => 'Tất cả lịch',
    'clear_logs' => 'Xóa tất cả nhật ký',
    'clear_logs_confirm' => 'Bạn có chắc chắn muốn xóa tất cả nhật ký không?',
    'logs_cleared' => 'Tất cả nhật ký đã được xóa thành công',
    'error_occurred' => 'Đã xảy ra lỗi. Vui lòng thử lại.',

    // Status
    'status_success' => 'Thành công',
    'status_warning' => 'Cảnh báo',
    'status_error' => 'Lỗi',

    // Email
    'email' => [
        'subject' => 'Cảnh báo lỗi đồng bộ iCal',
        'greeting' => 'Xin chào,',
        'message' => 'Chúng tôi phát hiện sự cố với đồng bộ hóa iCal cho hệ thống đặt phòng khách sạn của bạn.',
        'details' => 'Chi tiết lỗi',
        'time' => 'Thời gian',
        'error' => 'Thông báo lỗi',
        'action' => 'Vui lòng kiểm tra cài đặt đồng bộ iCal của bạn và đảm bảo rằng tất cả các URL lịch bên ngoài đều chính xác và có thể truy cập.',
        'view_logs' => 'Xem nhật ký đồng bộ',
        'closing' => 'Cảm ơn bạn đã quan tâm đến vấn đề này.',
        'footer' => 'Đây là tin nhắn tự động từ :site_name.',
    ],

    // Documentation
    'documentation' => 'Tài liệu đồng bộ iCal',
    'documentation_title' => 'Cách sử dụng đồng bộ iCal',
    'what_is_ical' => 'Đồng bộ iCal là gì?',
    'what_is_ical_desc' => 'iCal (hoặc iCalendar) là định dạng tiêu chuẩn để trao đổi dữ liệu lịch. Tính năng đồng bộ iCal cho phép bạn đồng bộ hóa đặt phòng khách sạn của mình với các nền tảng bên ngoài như Booking.com, Airbnb và các hệ thống đặt phòng khác hỗ trợ iCal.',

    'export_bookings' => 'Xuất đặt phòng của bạn',
    'export_bookings_desc' => 'Bạn có thể xuất đặt phòng khách sạn của mình sang các nền tảng bên ngoài để tránh đặt phòng trùng lặp.',
    'export_step1_title' => 'Đi đến trang đồng bộ iCal',
    'export_step1_desc' => 'Điều hướng đến Đặt phòng > Đồng bộ iCal trong bảng quản trị.',
    'export_step2_title' => 'Tìm phòng bạn muốn xuất',
    'export_step2_desc' => 'Nhấp vào "Chỉnh sửa" cho phòng bạn muốn xuất đặt phòng.',
    'export_step3_title' => 'Sao chép URL xuất hoặc tải xuống tệp iCal',
    'export_step3_desc' => 'Sao chép URL xuất được cung cấp hoặc tải xuống tệp iCal. Thêm URL này vào nền tảng bên ngoài của bạn (Booking.com, Airbnb, v.v.) theo hướng dẫn nhập nguồn cấp iCal của họ.',

    'import_bookings' => 'Nhập đặt phòng bên ngoài',
    'import_bookings_desc' => 'Bạn có thể nhập đặt phòng từ các nền tảng bên ngoài để tránh đặt phòng trùng lặp trong hệ thống của bạn.',
    'import_step1_title' => 'Lấy URL iCal từ nền tảng bên ngoài của bạn',
    'import_step1_desc' => 'Đăng nhập vào nền tảng bên ngoài của bạn (Booking.com, Airbnb, v.v.) và tìm tùy chọn để xuất lịch dưới dạng iCal. Sao chép URL được cung cấp.',
    'import_step2_title' => 'Đi đến trang đồng bộ iCal',
    'import_step2_desc' => 'Điều hướng đến Đặt phòng > Đồng bộ iCal trong bảng quản trị.',
    'import_step3_title' => 'Thêm lịch bên ngoài',
    'import_step3_desc' => 'Nhấp vào "Chỉnh sửa" cho phòng bạn muốn nhập đặt phòng. Nhập tên cho lịch (ví dụ: "Booking.com") và dán URL iCal bạn đã sao chép trước đó.',
    'import_step4_title' => 'Đồng bộ lịch',
    'import_step4_desc' => 'Nhấp vào "Đồng bộ" để đồng bộ lịch theo cách thủ công hoặc đợi đồng bộ tự động chạy.',

    'sync_logs' => 'Nhật ký đồng bộ iCal',
    'sync_logs_desc' => 'Hệ thống lưu giữ nhật ký chi tiết của tất cả các hoạt động đồng bộ hóa. Bạn có thể xem các nhật ký này bằng cách nhấp vào "Nhật ký đồng bộ" trong trang đồng bộ iCal. Điều này giúp bạn khắc phục mọi sự cố với đồng bộ hóa.',

    'troubleshooting' => 'Khắc phục sự cố',
    'troubleshooting_desc' => 'Dưới đây là một số vấn đề phổ biến và giải pháp của chúng:',
    'faq1_title' => 'Lịch bên ngoài không đồng bộ',
    'faq1_desc' => 'Đảm bảo URL chính xác và có thể truy cập. Một số nền tảng yêu cầu bạn đăng nhập để truy cập nguồn cấp iCal. Hãy thử tải xuống tệp iCal trực tiếp từ URL để xác minh rằng nó hoạt động.',
    'faq2_title' => 'Có xung đột giữa các đặt phòng',
    'faq2_desc' => 'Hệ thống sẽ phát hiện xung đột giữa các đặt phòng và ghi nhật ký chúng. Bạn có thể xem các xung đột này trong Nhật ký đồng bộ. Bạn sẽ cần giải quyết các xung đột này theo cách thủ công bằng cách điều chỉnh đặt phòng trong một trong các hệ thống.',
    'faq3_title' => 'Đồng bộ tự động không hoạt động',
    'faq3_desc' => 'Đảm bảo các công việc cron của máy chủ của bạn được thiết lập chính xác. Hệ thống sử dụng bộ lập lịch của Laravel để chạy lệnh đồng bộ mỗi giờ. Bạn cũng có thể chạy đồng bộ theo cách thủ công bằng cách nhấp vào "Đồng bộ" hoặc "Đồng bộ tất cả lịch" trong trang đồng bộ iCal.',
];
