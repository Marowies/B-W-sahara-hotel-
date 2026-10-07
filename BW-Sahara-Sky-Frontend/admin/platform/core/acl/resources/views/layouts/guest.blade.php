<x-core::layouts.base :body-attributes="['data-bs-theme' => 'dark']">
    <main class="row g-0 flex-fill vh-100">
        <div class="col-12 col-lg-6 col-xl-4 border-top-wide border-primary d-flex flex-column justify-content-center">
            <div class="container container-tight my-5 px-lg-5">
                <div class="text-center mb-4">
                    @include('core/base::partials.logo', ['defaultLogoHeight' => 50])
                </div>

                @yield('content')
            </div>
        </div>
        <div class="position-relative col-12 col-lg-6 col-xl-8 d-none d-lg-block">
            <div
                class="bg-cover bg-white h-100 min-vh-100"
                style="background-image: url({{ $backgroundUrl }})"
            ></div>
            @php($bwTitle = setting('admin_title', config('core.base.general.base_name')))
            <div class="bw-login-brand">
                <h1 class="bw-login-title">{{ $bwTitle }}</h1>
                <span class="bw-login-rule" aria-hidden="true"></span>
                <p class="bw-login-copy">Copyright {{ now()->format('Y') }} {{ $bwTitle }}.</p>
            </div>
        </div>
    </main>
</x-core::layouts.base>
