<?php

namespace Botble\Hotel\Http\Controllers\Front;

use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Hotel\Enums\ReviewStatusEnum;
use Botble\Hotel\Facades\HotelHelper;
use Botble\Hotel\Http\Requests\ReviewRequest;
use Botble\Hotel\Models\Room;
use Botble\Slug\Facades\SlugHelper;
use Botble\Theme\Facades\Theme;
use Closure;
use Illuminate\Http\Request;

class ReviewController extends BaseController
{
    public function __construct()
    {
        $this->middleware(function (Request $request, Closure $next) {
            abort_unless($request->ajax(), 404);

            abort_unless(HotelHelper::isReviewEnabled(), 404);

            return $next($request);
        });
    }

    public function index(string $key, Request $request, BaseHttpResponse $response)
    {
        $slug = SlugHelper::getSlug($key, SlugHelper::getPrefix(Room::class));

        abort_unless($slug, 404);

        $room = $slug->reference;

        abort_unless($room, 404);

        $reviews = $room
            ->reviews()
            ->where('status', ReviewStatusEnum::APPROVED)
            ->with(['author', 'author.avatar'])
            ->latest()
            ->paginate((int) setting('hotel_reviews_per_page', 10) ?: 10);

        return $response->setData(
            view(Theme::getThemeNamespace('views.hotel.partials.reviews-list'), [
                'reviews' => $reviews,
            ])->render()
        );
    }

    public function store(string $key, ReviewRequest $request, BaseHttpResponse $response)
    {
        $slug = SlugHelper::getSlug($key, SlugHelper::getPrefix(Room::class));

        abort_unless($slug, 404);

        $reviewable = $slug->reference;

        abort_unless($reviewable, 404);

        if (auth('customer')->check() && ! auth('customer')->user()->canReview($reviewable)) {
            return $response
                ->setCode(422)
                ->setMessage(trans('plugins/hotel::review.already_submitted'));
        }

        $review = $reviewable->reviews()->create(
            array_merge($request->validated(), [
                'customer_id' => auth('customer')->id(),
            ])
        );

        event(new CreatedContentEvent(REVIEW_MODULE_SCREEN_NAME, $request, $review));

        $viewsCount = $reviewable->reviews->count();

        return $response->setData([
            'count' => trans('plugins/hotel::review.count_reviews', ['count' => number_format($viewsCount)]),
            'message' => trans('plugins/hotel::review.review_submitted'),
        ]);
    }
}
