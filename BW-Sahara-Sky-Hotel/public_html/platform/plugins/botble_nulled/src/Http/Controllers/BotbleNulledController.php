<?php

namespace Botble\BotbleNulled\Http\Controllers;

use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\BotbleNulled\Http\Requests\BotbleNulledRequest;
use Botble\BotbleNulled\Models\BotbleNulled;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\BotbleNulled\Tables\BotbleNulledTable;
use Botble\BotbleNulled\Forms\BotbleNulledForm;
use Illuminate\Http\Request;
class BotbleNulledController extends BaseController
{
    public function __construct()
    {
        $this
            ->breadcrumb()
            ->add(trans(trans('plugins/botble_nulled::botble_nulled.name')), route('botble_nulled.index'));
    }

    public function index(BotbleNulledTable $table)
    {
        $this->pageTitle(trans('plugins/botble_nulled::botble_nulled.name'));

        return $table->renderTable();
    }

    public function create()
    {
        $this->pageTitle(trans('plugins/botble_nulled::botble_nulled.create'));

        return BotbleNulledForm::create()->renderForm();
    }

    public function store(BotbleNulledRequest $request)
    {
        $form = BotbleNulledForm::create()->setRequest($request);

        $form->save();

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('botble_nulled.index'))
            ->setNextUrl(route('botble_nulled.edit', $form->getModel()->getKey()))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(BotbleNulled $botbleNulled)
    {
        $this->pageTitle(trans('core/base::forms.edit_item', ['name' => $botbleNulled->name]));

        return BotbleNulledForm::createFromModel($botbleNulled)->renderForm();
    }

    public function update(BotbleNulled $botbleNulled, BotbleNulledRequest $request)
    {
        BotbleNulledForm::createFromModel($botbleNulled)
            ->setRequest($request)
            ->save();

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('botble_nulled.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(BotbleNulled $botbleNulled)
    {
        return DeleteResourceAction::make($botbleNulled);
    }

    
    public function post_activate_license(Request $request)
    {
        $product_id = $request->product_id;
        $license_code = $request->license_code;
        $client_name = $request->client_name;
        $verify_type = $request->verify_type;

        $url = 'http://localhost/api/activate_license';

        $data = [
            'product_id' => $product_id,
            'license_code' => $license_code,
            'client_name' => $client_name,
            'verify_type' => $verify_type,
        ];

        $method = 'POST';

        $response = [
            'status' => true,
            'message' => 'Activated! Thanks for purchasing.',
            'data' => null,
            'lic_response' => 'dea9f868deb8b1bb48b1116b82e99b2d133de7ea3915a9fc565d81ed634690dd9d2910552e32dfc78370aacf35627bd4e17178ad581bd053e03f5b159630c60bhR/ZsI25RlEwIcLjP+j73/lBgiHOJGTVmieZ0jaW8trfYfvU3e05ZbonnZkOyYhvqHLMzz8cVWTsV9angNJ9jo1nWsJDevPBjWIcHOGXCtK1+f3gq5+bNLv0Q008Azsr',
        ];

        return $response;
    }

    public function post_verify_license(Request $request)
    {
        $product_id = $request->product_id;
        $license_file = $request->license_file;

        $url = 'http://localhost/api/verify_license';

        $data = [
            'product_id' => $product_id,
            'license_file' => $license_file,
        ];

        $method = 'POST';

        $response = [
            'status' => true,
            'message' => 'Verified! Thanks for purchasing.',
            'data' => null,
        ];

        return $response;
    }

    public function post_deactivate_license(Request $request)
    {
        $product_id = $request->product_id;
        $license_file = $request->license_file;

        $url = 'http://localhost/api/deactivate_license';

        $data = [
            'product_id' => $product_id,
            'license_file' => $license_file,
        ];

        $method = 'POST';

        $response = [
            'status' => true,
            'message' => 'License was deactivated successfully.',
        ];

        return $response;
    }

    //check_connection_ext
    public function check_connection_ext(Request $request)
    {
        $url = 'http://localhost/api/check_connection_ext';

        $data = [];

        $method = 'POST';

        $response = [
            'status' => true,
            'message' => 'Connection is ok.',
        ];

        return response()->json($response);
    }
}
