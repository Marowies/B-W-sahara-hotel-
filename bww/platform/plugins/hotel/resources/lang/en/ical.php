<?php

return [
    'name' => 'iCal Sync',
    'sync_calendars' => 'Sync Calendars',
    'sync_all_calendars' => 'Sync All Calendars',
    'external_calendars' => 'External Calendars',
    'last_sync' => 'Last Sync',
    'never' => 'Never',
    'sync' => 'Sync',
    'sync_all' => 'Sync All',
    'download' => 'Download',
    'edit' => 'Edit iCal for :name',

    // Statistics
    'total_rooms' => 'Total Rooms',
    'synced_calendars' => 'Synced Calendars',
    'rooms_with_calendars' => 'Rooms with Calendars',
    'last_sync_time' => 'Last Sync',

    // Table columns
    'calendars' => 'Calendars',
    'status' => 'Status',
    'not_applicable' => 'N/A',

    // Status badges
    'synced' => 'Synced',
    'needs_sync' => 'Needs Sync',
    'not_synced' => 'Not Synced',
    'no_calendars' => 'No Calendars',

    // Empty states
    'no_rooms_found' => 'No Rooms Found',
    'no_rooms_desc' => 'Create a room first to start managing calendar synchronization.',

    // Actions
    'manage_calendars' => 'Manage Calendars',

    // Export calendar
    'export_calendar_title' => 'Export Calendar Feed',
    'copy_url_instruction' => 'Copy this URL and paste it into your external booking platform (Airbnb, Booking.com, etc.)',
    'how_to_export' => 'How to Export?',

    // Empty states for calendars
    'no_external_calendars' => 'No external calendars configured',
    'add_calendar_desc' => 'Add an external calendar URL to start importing bookings from other platforms.',

    'calendar_name' => 'Calendar Name',
    'calendar_name_placeholder' => 'Enter calendar name',
    'calendar_name_help' => 'Give this calendar a recognizable name (e.g., "Booking.com", "Airbnb")',
    'calendar_url' => 'Calendar URL',
    'calendar_url_placeholder' => 'Enter calendar URL (e.g., from Booking.com, Airbnb, etc.)',
    'calendar_url_help' => 'Paste the iCal feed URL from your external booking platform',

    // Form instructions
    'add_external_calendar_title' => 'Add External Calendar',
    'add_external_calendar_desc' => 'Import bookings from external platforms to prevent double bookings and keep your availability synchronized.',
    'supported_platforms' => 'Supported Platforms',
    'and_more' => 'and more',
    'benefits_title' => 'Benefits',
    'benefit_1' => 'Prevent double bookings automatically',
    'benefit_2' => 'Keep availability synchronized across platforms',
    'benefit_3' => 'Save time with automatic updates',
    'quick_tip' => 'Quick Tip',
    'tip_desc' => 'You can add multiple calendar feeds from different platforms for the same room.',
    'view_guide' => 'View Step-by-Step Guide',
    'existing_calendars' => 'Existing Calendars',
    'export_calendar' => 'Export Calendar',
    'export_calendar_to_platforms' => 'Export to External Platforms',
    'export_url' => 'Export URL',
    'export_description' => 'Use this URL to export your bookings to external platforms like Booking.com, Airbnb, etc.',
    'download_ical_file' => 'Download iCal File',
    'sync_success' => 'Successfully synced :success calendars. Failed: :failed',
    'sync_all_success' => 'Successfully synced :success calendars. Failed: :failed',

    // Logs
    'logs' => 'iCal Sync Logs',
    'log_details' => 'Sync Log #:id',
    'message' => 'Message',
    'data' => 'Data',
    'calendar' => 'Calendar',
    'all_calendars' => 'All Calendars',
    'clear_logs' => 'Clear All Logs',
    'clear_logs_confirm' => 'Are you sure you want to clear all logs?',
    'logs_cleared' => 'All logs have been cleared successfully',
    'error_occurred' => 'An error occurred. Please try again.',

    // Status
    'status_success' => 'Success',
    'status_warning' => 'Warning',
    'status_error' => 'Error',

    // Email
    'email' => [
        'subject' => 'iCal Sync Failure Alert',
        'greeting' => 'Hello,',
        'message' => 'We detected an issue with the iCal synchronization for your hotel booking system.',
        'details' => 'Error Details',
        'time' => 'Time',
        'error' => 'Error Message',
        'action' => 'Please check your iCal sync settings and ensure that all external calendar URLs are correct and accessible.',
        'view_logs' => 'View Sync Logs',
        'closing' => 'Thank you for your attention to this matter.',
        'footer' => 'This is an automated message from :site_name.',
    ],

    // Documentation
    'documentation' => 'iCal Sync Documentation',
    'documentation_title' => 'How to Use iCal Sync',
    'what_is_ical' => 'What is iCal Sync?',
    'what_is_ical_desc' => 'iCal (or iCalendar) is a standard format for calendar data exchange. The iCal Sync feature allows you to synchronize your hotel room bookings with external platforms like Booking.com, Airbnb, and other booking systems that support iCal.',

    'export_bookings' => 'Exporting Your Bookings',
    'export_bookings_desc' => 'You can export your hotel bookings to external platforms to prevent double bookings.',
    'export_step1_title' => 'Go to iCal Sync page',
    'export_step1_desc' => 'Navigate to Booking > iCal Sync in the admin panel.',
    'export_step2_title' => 'Find the room you want to export',
    'export_step2_desc' => 'Click on "Edit" for the room you want to export bookings for.',
    'export_step3_title' => 'Copy the export URL or download the iCal file',
    'export_step3_desc' => 'Copy the export URL provided or download the iCal file. Add this URL to your external platform (Booking.com, Airbnb, etc.) following their instructions for importing iCal feeds.',

    'import_bookings' => 'Importing External Bookings',
    'import_bookings_desc' => 'You can import bookings from external platforms to prevent double bookings in your system.',
    'import_step1_title' => 'Get the iCal URL from your external platform',
    'import_step1_desc' => 'Log in to your external platform (Booking.com, Airbnb, etc.) and find the option to export calendar as iCal. Copy the URL provided.',
    'import_step2_title' => 'Go to iCal Sync page',
    'import_step2_desc' => 'Navigate to Booking > iCal Sync in the admin panel.',
    'import_step3_title' => 'Add the external calendar',
    'import_step3_desc' => 'Click on "Edit" for the room you want to import bookings for. Enter a name for the calendar (e.g., "Booking.com") and paste the iCal URL you copied earlier.',
    'import_step4_title' => 'Sync the calendar',
    'import_step4_desc' => 'Click on "Sync" to manually sync the calendar or wait for the automatic sync to run.',

    'sync_logs' => 'iCal Sync Logs',
    'sync_logs_desc' => 'The system keeps detailed logs of all synchronization activities. You can view these logs by clicking on "Sync Logs" in the iCal Sync page. This helps you troubleshoot any issues with the synchronization.',

    'troubleshooting' => 'Troubleshooting',
    'troubleshooting_desc' => 'Here are some common issues and their solutions:',
    'faq1_title' => 'The external calendar is not syncing',
    'faq1_desc' => 'Make sure the URL is correct and accessible. Some platforms require you to be logged in to access the iCal feed. Try downloading the iCal file directly from the URL to verify it works.',
    'faq2_title' => 'There are conflicts between bookings',
    'faq2_desc' => 'The system will detect conflicts between bookings and log them. You can view these conflicts in the Sync Logs. You will need to resolve these conflicts manually by adjusting the bookings in one of the systems.',
    'faq3_title' => 'The automatic sync is not working',
    'faq3_desc' => 'Make sure your server\'s cron jobs are set up correctly. The system uses Laravel\'s scheduler to run the sync command every hour. You can also run the sync manually by clicking on "Sync" or "Sync All Calendars" in the iCal Sync page.',
];
