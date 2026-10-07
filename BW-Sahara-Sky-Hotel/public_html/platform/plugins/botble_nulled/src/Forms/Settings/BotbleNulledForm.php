<?php

namespace Botble\BotbleNulled\Forms\Settings;

use Botble\BotbleNulled\Http\Requests\Settings\BotbleNulledRequest;
use Botble\Setting\Forms\SettingForm;

class BotbleNulledForm extends SettingForm
{
    public function buildForm(): void
    {
        parent::buildForm();

        $this
            ->setSectionTitle('Setting title')
            ->setSectionDescription('Setting description')
            ->setValidatorClass(BotbleNulledRequest::class);
    }
}
