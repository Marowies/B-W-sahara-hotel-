<?php

namespace Botble\Hotel\Forms\Fronts\Auth;

use Botble\Base\Forms\Fields\EmailField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\PasswordField;
use Botble\Hotel\Forms\Fronts\Auth\FieldOptions\EmailFieldOption;
use Botble\Hotel\Forms\Fronts\Auth\FieldOptions\TextFieldOption;
use Botble\Hotel\Http\Requests\Fronts\Auth\ResetPasswordRequest;

class ResetPasswordForm extends AuthForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setUrl(route('customer.password.reset.update'))
            ->icon('ti ti-lock')
            ->setValidatorClass(ResetPasswordRequest::class)
            ->heading(trans('plugins/hotel::customer.reset_password'))
            ->add(
                'token',
                'hidden',
                TextFieldOption::make()
                    ->value($this->request->route('token'))
            )
            ->add(
                'email',
                EmailField::class,
                EmailFieldOption::make()
                    ->label(trans('plugins/hotel::customer.email_address'))
                    ->value($this->request->query('email'))
                    ->icon('ti ti-mail')
                    ->addAttribute('autocomplete', 'email')
                    ->required()
            )
            ->add(
                'password',
                PasswordField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/hotel::customer.password'))
                    ->icon('ti ti-lock')
            )
            ->add(
                'password_confirmation',
                PasswordField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/hotel::customer.password_confirmation'))
                    ->icon('ti ti-lock')
            )
            ->submitButton(trans('plugins/hotel::customer.reset_password'))
            ->add('back_to_login', HtmlField::class, [
                'html' => sprintf(
                    '<div class="mt-3 text-center"><a href="%s" class="text-decoration-underline">%s</a></div>',
                    route('customer.login'),
                    trans('plugins/hotel::customer.back_to_login_page')
                ),
            ]);
    }
}
