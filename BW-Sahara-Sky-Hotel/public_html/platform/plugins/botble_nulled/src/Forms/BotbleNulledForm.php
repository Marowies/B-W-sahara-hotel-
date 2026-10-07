<?php

namespace Botble\BotbleNulled\Forms;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Forms\FormAbstract;
use Botble\BotbleNulled\Http\Requests\BotbleNulledRequest;
use Botble\BotbleNulled\Models\BotbleNulled;

class BotbleNulledForm extends FormAbstract
{
    public function setup(): void
    {
        $this
            ->setupModel(new BotbleNulled())
            ->setValidatorClass(BotbleNulledRequest::class)
            ->withCustomFields()
            ->add('name', 'text', [
                'label' => trans('core/base::forms.name'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('core/base::forms.name_placeholder'),
                    'data-counter' => 120,
                ],
            ])
            ->add('status', 'customSelect', [
                'label' => trans('core/base::tables.status'),
                'required' => true,
                'choices' => BaseStatusEnum::labels(),
            ])
            ->setBreakFieldPoint('status');
    }
}
