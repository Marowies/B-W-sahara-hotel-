<?php

namespace App\Http\Controllers;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Hotel\Facades\HotelHelper;
use Botble\Hotel\Http\Controllers\PublicController;
use Botble\Hotel\Http\Requests\InitBookingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HotelBookingStartController extends Controller
{
    /** Adapt the frontend dates; the existing hotel controller owns all booking rules. */
    public function start(Request $request, BaseHttpResponse $response)
    {
        $input = $request->validate([
            'room_id' => ['required', 'integer', 'min:1'],
            'arrival' => ['bail', 'required', 'string', 'date_format:Y-m-d'],
            'departure' => ['bail', 'required', 'string', 'date_format:Y-m-d'],
            'adults' => ['required', 'integer', 'min:1', 'max:30'],
            'children' => ['required', 'integer', 'min:0', 'max:30'],
            'rooms' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'locale' => ['sometimes', 'string', 'in:en,ar,zh'],
        ]);
        // Compare dates only after both have passed scalar/format validation.
        $request->validate(['arrival' => ['after_or_equal:today'], 'departure' => ['after:arrival']]);
        $selection = InitBookingRequest::createFrom($request);
        $selection->replace([
            'room_id' => $input['room_id'],
            'start_date' => Carbon::createFromFormat('!Y-m-d', $input['arrival'])->format(HotelHelper::getDateFormat()),
            'end_date' => Carbon::createFromFormat('!Y-m-d', $input['departure'])->format(HotelHelper::getDateFormat()),
            'adults' => $input['adults'], 'children' => $input['children'], 'rooms' => $input['rooms'] ?? 1,
        ]);
        $selection->setContainer(app())->setRedirector(app('redirect'));
        $selection->validateResolved();

        $result = app(PublicController::class)->postBooking($selection, $response)->toArray();
        if (! $result['error']) {
            $parameters = ['token' => $request->session()->get('checkout_token')];
            if (isset($input['locale'])) {
                $request->session()->put('hotel_ui_locale', $input['locale']);
                $parameters['lang'] = $input['locale'];
            }
            $result['next_url'] = route('public.booking.form', $parameters);
        }

        return response()->json($result, $result['error'] ? 422 : 200)->header('Cache-Control', 'no-store');
    }
}
