<?php

namespace Botble\BotbleNulled\Http\Controllers\Settings;

use Botble\Base\Forms\FormBuilder;
use Botble\BotbleNulled\Forms\Settings\BotbleNulledForm;
use Botble\BotbleNulled\Http\Requests\Settings\BotbleNulledRequest;
use Botble\Setting\Http\Controllers\SettingController;

class BotbleNulledController extends SettingController
{
    public function edit(FormBuilder $formBuilder)
    {
        $this->pageTitle('Page title');

        return $formBuilder->create(BotbleNulledForm::class)->renderForm();
    }

    public function update(BotbleNulledRequest $request)
    {
        return $this->performUpdate($request->validated());
    }
}
