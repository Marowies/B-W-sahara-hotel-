$(document).ready(function () {

    // Handle clear logs button click
    $(document).on('click', '.action-item.clear_logs', function (e) {
        e.preventDefault();

        $('.delete-logs-entry').data('section', route('ical.logs.clear'));
        $('.modal-confirm-clear-logs').modal('show');
    });

    $('.delete-logs-entry').on('click', function (e) {
        e.preventDefault();
        $('.modal-confirm-clear-logs').modal('hide');

        const clearURL = $(e.currentTarget).data('section');

        $httpClient
            .make()
            .post(clearURL)
            .then(({ data }) => {
                Botble.showSuccess(data.message);
                window.location.reload();
            });
    });

    // Handle sync calendar button click
    $(document).on('click', '.sync-calendar', function (e) {
        e.preventDefault();

        const button = $(this);
        const roomId = button.data('room-id');
        const row = button.closest('tr');
        const statusBadge = row.find('td:nth-child(3) .badge');
        const lastSyncCell = row.find('td:nth-child(4)');

        button.prop('disabled', true).addClass('button-loading');

        // Show syncing indicator
        if (statusBadge.length) {
            statusBadge.removeClass('badge-success badge-warning badge-secondary')
                .addClass('badge-info')
                .html('<i class="ti ti-refresh me-1"></i> Syncing...');
        }

        $.ajax({
            url: route('ical.sync'),
            type: 'POST',
            data: {
                room_id: roomId,
            },
            success: function (res) {
                if (res.error) {
                    Botble.showError(res.message);
                    // Restore previous state on error
                    if (statusBadge.length) {
                        statusBadge.removeClass('badge-info')
                            .addClass('badge-danger')
                            .html('<i class="ti ti-alert-circle me-1"></i> Sync Failed');
                    }
                } else {
                    Botble.showSuccess(res.message);
                    // Update status badge
                    if (statusBadge.length) {
                        statusBadge.removeClass('badge-info badge-warning badge-secondary')
                            .addClass('badge-success')
                            .html('<i class="ti ti-check me-1"></i> Synced');
                    }
                    // Update last sync time
                    if (lastSyncCell.length) {
                        lastSyncCell.html('<span class="text-muted">just now</span>');
                    }
                }
                button.prop('disabled', false).removeClass('button-loading');
            },
            error: function (res) {
                Botble.handleError(res);
                // Restore previous state on error
                if (statusBadge.length) {
                    statusBadge.removeClass('badge-info')
                        .addClass('badge-danger')
                        .html('<i class="ti ti-alert-circle me-1"></i> Error');
                }
                button.prop('disabled', false).removeClass('button-loading');
            }
        });
    });

    // Handle sync all calendars button click
    $(document).on('click', '.sync-all-calendars', function (e) {
        e.preventDefault();

        const button = $(this);
        const originalText = button.html();

        button.prop('disabled', true).addClass('button-loading');
        button.html('<i class="ti ti-refresh me-1"></i> Syncing all calendars...');

        // Show syncing state for all rooms with calendars
        $('table tbody tr').each(function() {
            const statusBadge = $(this).find('td:nth-child(3) .badge');
            if (statusBadge.length && !statusBadge.hasClass('badge-light')) {
                statusBadge.removeClass('badge-success badge-warning badge-secondary')
                    .addClass('badge-info')
                    .html('<i class="ti ti-refresh me-1"></i> Syncing...');
            }
        });

        $.ajax({
            url: route('ical.sync.all'),
            type: 'POST',
            success: function (res) {
                if (res.error) {
                    Botble.showError(res.message);
                } else {
                    Botble.showSuccess(res.message);

                    // Show detailed results if available
                    if (res.data) {
                        const { success, failed, events } = res.data;
                        let detailsHtml = `<div class="mt-2">`;
                        detailsHtml += `<strong>Sync Summary:</strong><br>`;
                        detailsHtml += `✓ Success: ${success}<br>`;
                        detailsHtml += `✗ Failed: ${failed}<br>`;
                        if (events) {
                            detailsHtml += `📅 Events imported: ${events}`;
                        }
                        detailsHtml += `</div>`;

                        // Could show this in a modal or notification
                        console.log('Sync results:', res.data);
                    }

                    // Reload page to show updated sync times
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
                button.prop('disabled', false).removeClass('button-loading');
                button.html(originalText);
            },
            error: function (res) {
                Botble.handleError(res);
                button.prop('disabled', false).removeClass('button-loading');
                button.html(originalText);

                // Reset status badges on error
                $('table tbody tr').each(function() {
                    const statusBadge = $(this).find('td:nth-child(3) .badge');
                    if (statusBadge.length && statusBadge.hasClass('badge-info')) {
                        statusBadge.removeClass('badge-info')
                            .addClass('badge-danger')
                            .html('<i class="ti ti-alert-circle me-1"></i> Error');
                    }
                });
            }
        });
    });

    // Handle delete calendar button click
    $(document).on('click', '.deleteDialog', function (e) {
        e.preventDefault();

        $('.delete-crud-entry').data('section', $(e.currentTarget).data('section'));
        $('.modal-confirm-delete').modal('show');
    });

    $('.delete-crud-entry').on('click', function (e) {
        e.preventDefault();
        $('.modal-confirm-delete').modal('hide');

        const deleteURL = $(e.currentTarget).data('section');

        $httpClient
            .make()
            .delete(deleteURL)
            .then(({ data }) => {
                const $button = $(`a[data-section="${deleteURL}"]`);
                const $table = $button.closest('table');

                $button.closest('tr').remove();
                Botble.showSuccess(data.message);

                // Reload page if no more calendars in the table
                if ($table.find('tbody tr').length === 0) {
                    window.location.reload();
                }
            });
    });
});
