<div class="modal fade" id="create-customer-modal" tabindex="-1" role="dialog" aria-labelledby="create-customer-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="create-customer-modal-label">{{ trans('plugins/hotel::booking.create_new_customer') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="create-customer-form" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="modal_first_name" class="control-label required">{{ trans('plugins/hotel::customer.form.first_name') }}</label>
                                <input type="text" class="form-control" id="modal_first_name" name="first_name" placeholder="{{ trans('plugins/hotel::customer.form.first_name_placeholder') }}" required>
                                <small class="form-text text-muted">{{ trans('plugins/hotel::customer.form.first_name_helper') }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="modal_last_name" class="control-label required">{{ trans('plugins/hotel::customer.form.last_name') }}</label>
                                <input type="text" class="form-control" id="modal_last_name" name="last_name" placeholder="{{ trans('plugins/hotel::customer.form.last_name_placeholder') }}" required>
                                <small class="form-text text-muted">{{ trans('plugins/hotel::customer.form.last_name_helper') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="modal_email" class="control-label required">{{ trans('plugins/hotel::customer.form.email') }}</label>
                                <input type="email" class="form-control" id="modal_email" name="email" placeholder="{{ trans('plugins/hotel::customer.form.email_placeholder') }}" required>
                                <small class="form-text text-muted">{{ trans('plugins/hotel::customer.form.email_helper') }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="modal_phone" class="control-label">{{ trans('plugins/hotel::customer.form.phone') }}</label>
                                <input type="text" class="form-control" id="modal_phone" name="phone" placeholder="{{ trans('plugins/hotel::customer.form.phone_placeholder') }}">
                                <small class="form-text text-muted">{{ trans('plugins/hotel::customer.form.phone_helper') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="modal_address" class="control-label">{{ trans('plugins/hotel::booking.address') }}</label>
                                <input type="text" class="form-control" id="modal_address" name="address" placeholder="{{ trans('plugins/hotel::customer.form.address_placeholder') }}">
                                <small class="form-text text-muted">{{ trans('plugins/hotel::customer.form.address_helper') }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="modal_city" class="control-label">{{ trans('plugins/hotel::booking.city') }}</label>
                                <input type="text" class="form-control" id="modal_city" name="city" placeholder="{{ trans('plugins/hotel::customer.form.city_placeholder') }}">
                                <small class="form-text text-muted">{{ trans('plugins/hotel::customer.form.city_helper') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="modal_state" class="control-label">{{ trans('plugins/hotel::booking.state') }}</label>
                                <input type="text" class="form-control" id="modal_state" name="state" placeholder="{{ trans('plugins/hotel::customer.form.state_placeholder') }}">
                                <small class="form-text text-muted">{{ trans('plugins/hotel::customer.form.state_helper') }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="modal_country" class="control-label">{{ trans('plugins/hotel::booking.country') }}</label>
                                <input type="text" class="form-control" id="modal_country" name="country" placeholder="{{ trans('plugins/hotel::customer.form.country_placeholder') }}">
                                <small class="form-text text-muted">{{ trans('plugins/hotel::customer.form.country_helper') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="modal_zip" class="control-label">{{ trans('plugins/hotel::booking.zip_code') }}</label>
                                <input type="text" class="form-control" id="modal_zip" name="zip" placeholder="{{ trans('plugins/hotel::customer.form.zip_placeholder') }}">
                                <small class="form-text text-muted">{{ trans('plugins/hotel::customer.form.zip_helper') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <div id="modal-error-msg" class="alert alert-danger d-none"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('core/base::forms.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="create-customer-button">{{ trans('core/base::forms.create') }}</button>
            </div>
        </div>
    </div>
</div>
