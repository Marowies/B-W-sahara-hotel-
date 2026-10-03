<?php

namespace Botble\Base\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Supports\Core;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnlicensedController extends BaseController
{
    public function __construct(private readonly Core $core)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        return redirect()->route('dashboard.index');
    }

    public function postSkip(Request $request): RedirectResponse
    {
        $this->validateRedirectUrl($request);

        $this->core->skipLicenseReminder();

        return $request->filled('redirect_url')
            ? redirect()->to($request->input('redirect_url'))
            : redirect()->route('dashboard.index');
    }

    protected function validateRedirectUrl(Request $request): void
    {
        $request->validate(['redirect_url' => ['nullable', 'string', 'url']]);
    }
}
