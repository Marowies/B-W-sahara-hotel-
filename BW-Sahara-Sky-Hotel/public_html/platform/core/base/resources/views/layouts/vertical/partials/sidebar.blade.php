<div
    class="collapse navbar-collapse"
    id="sidebar-menu"
>
    <div class="bw-mobile-menu-heading d-lg-none">
        <span>B&W Sahara Sky</span>
        <button class="btn" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-label="{{ trans('core/base::forms.close') }}">
            <x-core::icon name="ti ti-x" />
        </button>
    </div>
    @include('core/base::layouts.partials.navbar-nav', [
        'autoClose' => 'false',
    ])
</div>
