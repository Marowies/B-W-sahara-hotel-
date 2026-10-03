<?php

namespace Botble\Hotel\Forms\Fronts\Auth;

use Botble\Base\Facades\Html;
use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\Fields\EmailField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\PasswordField;
use Botble\Hotel\Forms\Fronts\Auth\FieldOptions\EmailFieldOption;
use Botble\Hotel\Forms\Fronts\Auth\FieldOptions\TextFieldOption;
use Botble\Hotel\Http\Requests\Fronts\Auth\LoginRequest;
use Botble\Hotel\Models\Customer;

class LoginForm extends AuthForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setUrl(route('customer.login.post'))
            ->setValidatorClass(LoginRequest::class)
            ->icon('ti ti-lock')
            ->heading(trans('plugins/hotel::customer.login_to_your_account'))
            ->description(trans('plugins/hotel::customer.login_description'))
            ->when(
                theme_option('login_background'),
                fn (AuthForm $form, string $background) => $form->banner($background)
            )
            ->add(
                'email',
                EmailField::class,
                EmailFieldOption::make()
                    ->label(trans('plugins/hotel::customer.form.email'))
                    ->placeholder(trans('plugins/hotel::customer.form.email_placeholder'))
                    ->icon('ti ti-mail')
                    ->addAttribute('autocomplete', 'email')
                    ->required()
            )
            ->add(
                'password',
                PasswordField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/hotel::customer.form.password'))
                    ->placeholder(trans('plugins/hotel::customer.form.password'))
                    ->icon('ti ti-lock')
            )
            ->add('openRow', HtmlField::class, [
                'html' => '<div class="row g-0 mb-3">',
            ])
            ->add(
                'remember',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(trans('plugins/hotel::customer.remember_me'))
                    ->wrapperAttributes(['class' => 'col-6'])
            )
            ->add(
                'forgot_password',
                HtmlField::class,
                [
                    'html' => Html::link(route('customer.password.request'), trans('plugins/hotel::customer.forgot_password_question'), attributes: ['class' => 'text-decoration-underline']),
                    'wrapper' => [
                        'class' => 'col-6 text-end',
                    ],
                ]
            )
            ->add('closeRow', HtmlField::class, [
                'html' => '</div>',
            ])
            ->submitButton(trans('plugins/hotel::customer.login'), 'ti ti-arrow-narrow-right')
            ->add('register', HtmlField::class, [
                'html' => sprintf(
                    '<div class="mt-3 text-center">%s <a href="%s" class="text-decoration-underline">%s</a></div>',
                    trans('plugins/hotel::customer.dont_have_account'),
                    route('customer.register'),
                    trans('plugins/hotel::customer.register_now')
                ),
            ])
            ->add('filters', HtmlField::class, [
                'html' => apply_filters(BASE_FILTER_AFTER_LOGIN_OR_REGISTER_FORM, null, Customer::class),
            ]);
    }
}
