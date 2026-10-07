<?php

namespace Botble\Hotel\Forms\Fronts\Auth;

use Botble\Base\Facades\Html;
use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\Fields\EmailField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\PasswordField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Hotel\Forms\Fronts\Auth\FieldOptions\EmailFieldOption;
use Botble\Hotel\Forms\Fronts\Auth\FieldOptions\TextFieldOption;
use Botble\Hotel\Http\Requests\Fronts\Auth\RegisterRequest;
use Botble\Hotel\Models\Customer;

class RegisterForm extends AuthForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setUrl(route('customer.register.post'))
            ->setValidatorClass(RegisterRequest::class)
            ->icon('ti ti-user-plus')
            ->heading(trans('plugins/hotel::customer.register_an_account'))
            ->description(trans('plugins/hotel::customer.personal_data_description'))
            ->when(
                theme_option('register_background'),
                fn (AuthForm $form, string $background) => $form->banner($background)
            )
            ->add(
                'first_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/hotel::customer.first_name'))
                    ->placeholder(trans('plugins/hotel::customer.first_name'))
                    ->icon('ti ti-user')
            )
            ->add(
                'last_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/hotel::customer.last_name'))
                    ->placeholder(trans('plugins/hotel::customer.last_name'))
                    ->icon('ti ti-user')
            )
            ->add(
                'email',
                EmailField::class,
                EmailFieldOption::make()
                    ->label(trans('plugins/hotel::customer.email'))
                    ->placeholder(trans('plugins/hotel::customer.your_email'))
                    ->icon('ti ti-mail')
                    ->addAttribute('autocomplete', 'email')
                    ->required()
            )
            ->add(
                'password',
                PasswordField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/hotel::customer.password'))
                    ->placeholder(trans('plugins/hotel::customer.password'))
                    ->icon('ti ti-lock')
                    ->required()
            )
            ->add(
                'password_confirmation',
                PasswordField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/hotel::customer.password_confirmation'))
                    ->placeholder(trans('plugins/hotel::customer.password_confirmation'))
                    ->icon('ti ti-lock')
                    ->required()
            )
            ->add(
                'agree_terms_and_policy',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->when(
                        $privacyPolicyUrl = theme_option('term_and_privacy_policy_url'),
                        function (CheckboxFieldOption $fieldOption, string $url): void {
                            $fieldOption->label(trans('plugins/hotel::customer.agree_terms_and_policy_with_link', ['link' => Html::link($url, trans('plugins/hotel::customer.terms_and_privacy_policy'), attributes: ['class' => 'text-decoration-underline', 'target' => '_blank'])]));
                        }
                    )
                    ->when(! $privacyPolicyUrl, function (CheckboxFieldOption $fieldOption): void {
                        $fieldOption->label(trans('plugins/hotel::customer.agree_terms_and_policy'));
                    })
            )
            ->submitButton(trans('plugins/hotel::customer.register'), 'ti ti-arrow-narrow-right')
            ->add('login', HtmlField::class, [
                'html' => sprintf(
                    '<div class="mt-3 text-center">%s <a href="%s" class="text-decoration-underline">%s</a></div>',
                    trans('plugins/hotel::customer.already_have_account'),
                    route('customer.login'),
                    trans('plugins/hotel::customer.login')
                ),
            ])
            ->add('filters', HtmlField::class, [
                'html' => apply_filters(BASE_FILTER_AFTER_LOGIN_OR_REGISTER_FORM, null, Customer::class),
            ]);
    }
}
