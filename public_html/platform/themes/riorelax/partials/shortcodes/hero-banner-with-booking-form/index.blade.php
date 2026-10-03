@php($bgColor = $shortcode->background_color ?: '#101010')

{{-- Outer wrapper: inset from page edges; booking floats over bottom edge --}}
<div class="bw-hero-wrapper">

    {{-- Image clip: large uniform radius on all corners --}}
    <div class="bw-hero-image-clip">
        <div class="slider-area fix p-relative">
            <div class="slider-active">
                <div class="single-slider slider-bg d-flex align-items-center"
                     style="@if ($bgImage = $shortcode->background_image) background-image: url('{{ RvMedia::getImageUrl($bgImage) }}'); @endif"
                >
                    <div class="container">
                        <div class="row justify-content-center text-center">
                            <div class="col-lg-10">
                                <div class="slider-content s-slider-content mt-30">
                                    @if ($title = $shortcode->title)
                                        <h2 data-animation="fadeInUp" data-delay=".4s" class="mb-15">
                                            {!! BaseHelper::clean($title) !!}
                                        </h2>
                                    @endif

                                    @if ($description = $shortcode->description)
                                        <p data-animation="fadeInUp" data-delay=".6s" class="mb-20">{!! BaseHelper::clean($description) !!}</p>
                                    @endif

                                    @if (($buttonLabel = $shortcode->button_label) && ($buttonUrl = $shortcode->button_url))
                                        <div class="slider-btn mt-15 mb-40">
                                            <a href="{{ $buttonUrl }}" class="btn ss-btn active" data-animation="fadeInLeft" data-delay=".4s">
                                                {!! BaseHelper::clean($buttonLabel) !!}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>{{-- end .bw-hero-image-clip --}}

    {{-- Floating booking capsule: overlaps hero bottom edge --}}
    <div class="bw-booking-pill">
        <div class="booking-area homepage p-relative">
            {!! Theme::partial('hotel.forms.form', ['style' => 2, 'availableForBooking' => false, 'title' => $shortcode->form_title]) !!}
        </div>
    </div>{{-- end .bw-booking-pill --}}

</div>{{-- end .bw-hero-wrapper --}}
