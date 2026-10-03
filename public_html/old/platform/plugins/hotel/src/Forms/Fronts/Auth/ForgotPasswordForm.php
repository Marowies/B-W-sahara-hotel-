<?php

namespace Botble\Hotel\Forms\Fronts\Auth;

use Botble\Base\Forms\Fields\EmailField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Hotel\Forms\Fronts\Auth\FieldOptions\EmailFieldOption;
use Botble\Hotel\Http\Requests\Fronts\Auth\ForgotPasswordRequest;

class ForgotPasswordForm extends AuthForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setUrl(route('customer.password.email'))
            ->setValidatorClass(ForgotPasswordRequest::class)
            ->icon('ti ti-lock-question')
            ->heading(trans('plugins/hotel::customer.forgot_password'))
            ->description(trans('plugins/hotel::customer.forgot_password_description'))
            ->add(
                'email',
                EmailField::class,
                EmailFieldOption::make()
                    ->label(trans('plugins/hotel::customer.email'))
                    ->placeholder(trans('plugins/hotel::customer.email_address'))
                    ->icon('ti ti-mail')
                    ->addAttribute('autocomplete', 'email')
                    ->required()
            )
            ->submitButton(trans('plugins/hotel::customer.send_password_reset_link'))
            ->add('back_to_login', HtmlField::class, [
                'html' => sprintf(
                    '<div class="mt-3 text-center"><a href="%s" class="text-decoration-underline">%s</a></div>',
                    route('customer.login'),
                    trans('plugins/hotel::customer.back_to_login_page')
                ),
            ]);
    }
}
