<?php

return [
    'name' => 'Sinkronisasi iCal',
    'sync_calendars' => 'Sinkronkan Kalender',
    'sync_all_calendars' => 'Sinkronkan Semua Kalender',
    'external_calendars' => 'Kalender Eksternal',
    'last_sync' => 'Sinkronisasi Terakhir',
    'never' => 'Tidak Pernah',
    'sync' => 'Sinkronkan',
    'sync_all' => 'Sinkronkan Semua',
    'download' => 'Unduh',
    'edit' => 'Edit iCal untuk :name',

    // Statistik
    'total_rooms' => 'Total Kamar',
    'synced_calendars' => 'Kalender yang Tersinkron',
    'rooms_with_calendars' => 'Kamar dengan Kalender',
    'last_sync_time' => 'Sinkronisasi Terakhir',

    // Kolom tabel
    'calendars' => 'Kalender',
    'status' => 'Status',
    'not_applicable' => 'N/A',

    // Status badge
    'synced' => 'Tersinkron',
    'needs_sync' => 'Perlu Sinkron',
    'not_synced' => 'Belum Sinkron',
    'no_calendars' => 'Tidak ada Kalender',

    // Keadaan kosong
    'no_rooms_found' => 'Tidak Ada Kamar',
    'no_rooms_desc' => 'Buat kamar terlebih dahulu untuk mulai mengelola sinkronisasi kalender.',
    'no_external_calendars' => 'Tidak ada kalender eksternal yang dikonfigurasi',
    'add_calendar_desc' => 'Tambahkan URL kalender eksternal untuk mulai mengimpor pemesanan dari platform lain.',

    // Aksi
    'manage_calendars' => 'Kelola Kalender',

    // Ekspor kalender
    'export_calendar_title' => 'Ekspor Feed Kalender',
    'copy_url_instruction' => 'Salin URL ini dan tempelkan ke platform pemesanan eksternal Anda (Airbnb, Booking.com, dll.)',
    'how_to_export' => 'Cara Mengekspor?',
    'calendar_name' => 'Nama Kalender',
    'calendar_name_placeholder' => 'Masukkan nama kalender',
    'calendar_name_help' => 'Berikan kalender ini nama yang mudah dikenali (mis., "Booking.com", "Airbnb")',
    'calendar_url' => 'URL Kalender',
    'calendar_url_placeholder' => 'Masukkan URL kalender (mis., dari Booking.com, Airbnb, dll.)',
    'calendar_url_help' => 'Tempel URL feed iCal dari platform pemesanan eksternal Anda',
    'existing_calendars' => 'Kalender yang Ada',
    'export_calendar' => 'Ekspor Kalender',
    'export_calendar_to_platforms' => 'Ekspor ke Platform Eksternal',
    'export_url' => 'URL Ekspor',
    'export_description' => 'Gunakan URL ini untuk mengekspor pemesanan Anda ke platform eksternal seperti Booking.com, Airbnb, dll.',
    'download_ical_file' => 'Unduh File iCal',
    'sync_success' => 'Berhasil menyinkronkan :success kalender. Gagal: :failed',
    'sync_all_success' => 'Berhasil menyinkronkan :success kalender. Gagal: :failed',

    // Log
    'logs' => 'Log Sinkronisasi iCal',
    'log_details' => 'Log Sinkronisasi #:id',
    'message' => 'Pesan',
    'data' => 'Data',
    'calendar' => 'Kalender',
    'all_calendars' => 'Semua Kalender',
    'clear_logs' => 'Hapus Semua Log',
    'clear_logs_confirm' => 'Apakah Anda yakin ingin menghapus semua log?',
    'logs_cleared' => 'Semua log telah berhasil dihapus',
    'error_occurred' => 'Terjadi kesalahan. Silakan coba lagi.',

    // Status
    'status_success' => 'Berhasil',
    'status_warning' => 'Peringatan',
    'status_error' => 'Kesalahan',

    // Email
    'email' => [
        'subject' => 'Peringatan Kegagalan Sinkronisasi iCal',
        'greeting' => 'Halo,',
        'message' => 'Kami mendeteksi masalah dengan sinkronisasi iCal untuk sistem pemesanan hotel Anda.',
        'details' => 'Detail Kesalahan',
        'time' => 'Waktu',
        'error' => 'Pesan Kesalahan',
        'action' => 'Silakan periksa pengaturan sinkronisasi iCal Anda dan pastikan bahwa semua URL kalender eksternal sudah benar dan dapat diakses.',
        'view_logs' => 'Lihat Log Sinkronisasi',
        'closing' => 'Terima kasih atas perhatian Anda terhadap hal ini.',
        'footer' => 'Ini adalah pesan otomatis dari :site_name.',
    ],

    // Form instructions
    'add_external_calendar_title' => 'Tambah Kalender Eksternal',
    'add_external_calendar_desc' => 'Impor pemesanan dari platform eksternal untuk mencegah pemesanan ganda dan menjaga ketersediaan tetap sinkron.',
    'supported_platforms' => 'Platform yang Didukung',
    'and_more' => 'dan lainnya',
    'benefits_title' => 'Manfaat',
    'benefit_1' => 'Cegah pemesanan ganda secara otomatis',
    'benefit_2' => 'Jaga ketersediaan tetap sinkron di seluruh platform',
    'benefit_3' => 'Hemat waktu dengan pembaruan otomatis',
    'quick_tip' => 'Tip Cepat',
    'tip_desc' => 'Anda dapat menambahkan beberapa feed kalender dari berbagai platform untuk kamar yang sama.',
    'view_guide' => 'Lihat Panduan Langkah demi Langkah',

    // Dokumentasi
    'documentation' => 'Dokumentasi Sinkronisasi iCal',
    'documentation_title' => 'Cara Menggunakan Sinkronisasi iCal',
    'what_is_ical' => 'Apa itu Sinkronisasi iCal?',
    'what_is_ical_desc' => 'iCal (atau iCalendar) adalah format standar untuk pertukaran data kalender. Fitur Sinkronisasi iCal memungkinkan Anda untuk menyinkronkan pemesanan kamar hotel Anda dengan platform eksternal seperti Booking.com, Airbnb, dan sistem pemesanan lainnya yang mendukung iCal.',

    'export_bookings' => 'Mengekspor Pemesanan Anda',
    'export_bookings_desc' => 'Anda dapat mengekspor pemesanan hotel Anda ke platform eksternal untuk mencegah pemesanan ganda.',
    'export_step1_title' => 'Buka halaman Sinkronisasi iCal',
    'export_step1_desc' => 'Navigasi ke Pemesanan > Sinkronisasi iCal di panel admin.',
    'export_step2_title' => 'Temukan kamar yang ingin Anda ekspor',
    'export_step2_desc' => 'Klik "Edit" untuk kamar yang ingin Anda ekspor pemesanannya.',
    'export_step3_title' => 'Salin URL ekspor atau unduh file iCal',
    'export_step3_desc' => 'Salin URL ekspor yang disediakan atau unduh file iCal. Tambahkan URL ini ke platform eksternal Anda (Booking.com, Airbnb, dll.) dengan mengikuti instruksi mereka untuk mengimpor feed iCal.',

    'import_bookings' => 'Mengimpor Pemesanan Eksternal',
    'import_bookings_desc' => 'Anda dapat mengimpor pemesanan dari platform eksternal untuk mencegah pemesanan ganda di sistem Anda.',
    'import_step1_title' => 'Dapatkan URL iCal dari platform eksternal Anda',
    'import_step1_desc' => 'Masuk ke platform eksternal Anda (Booking.com, Airbnb, dll.) dan temukan opsi untuk mengekspor kalender sebagai iCal. Salin URL yang disediakan.',
    'import_step2_title' => 'Buka halaman Sinkronisasi iCal',
    'import_step2_desc' => 'Navigasi ke Pemesanan > Sinkronisasi iCal di panel admin.',
    'import_step3_title' => 'Tambahkan kalender eksternal',
    'import_step3_desc' => 'Klik "Edit" untuk kamar yang ingin Anda impor pemesanannya. Masukkan nama untuk kalender (mis., "Booking.com") dan tempel URL iCal yang Anda salin sebelumnya.',
    'import_step4_title' => 'Sinkronkan kalender',
    'import_step4_desc' => 'Klik "Sinkronkan" untuk menyinkronkan kalender secara manual atau tunggu sinkronisasi otomatis berjalan.',

    'sync_logs' => 'Log Sinkronisasi iCal',
    'sync_logs_desc' => 'Sistem menyimpan log terperinci dari semua aktivitas sinkronisasi. Anda dapat melihat log ini dengan mengklik "Log Sinkronisasi" di halaman Sinkronisasi iCal. Ini membantu Anda memecahkan masalah dengan sinkronisasi.',

    'troubleshooting' => 'Pemecahan Masalah',
    'troubleshooting_desc' => 'Berikut adalah beberapa masalah umum dan solusinya:',
    'faq1_title' => 'Kalender eksternal tidak tersinkronisasi',
    'faq1_desc' => 'Pastikan URL sudah benar dan dapat diakses. Beberapa platform mengharuskan Anda untuk masuk untuk mengakses feed iCal. Coba unduh file iCal langsung dari URL untuk memverifikasi bahwa itu berfungsi.',
    'faq2_title' => 'Ada konflik antara pemesanan',
    'faq2_desc' => 'Sistem akan mendeteksi konflik antara pemesanan dan mencatatnya. Anda dapat melihat konflik ini di Log Sinkronisasi. Anda perlu menyelesaikan konflik ini secara manual dengan menyesuaikan pemesanan di salah satu sistem.',
    'faq3_title' => 'Sinkronisasi otomatis tidak berfungsi',
    'faq3_desc' => 'Pastikan pekerjaan cron server Anda telah diatur dengan benar. Sistem menggunakan penjadwal Laravel untuk menjalankan perintah sinkronisasi setiap jam. Anda juga dapat menjalankan sinkronisasi secara manual dengan mengklik "Sinkronkan" atau "Sinkronkan Semua Kalender" di halaman Sinkronisasi iCal.',
];
