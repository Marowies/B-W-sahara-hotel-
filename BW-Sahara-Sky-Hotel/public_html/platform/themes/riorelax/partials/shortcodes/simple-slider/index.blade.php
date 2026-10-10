{{-- Inset rounded hero matching design; keep slider text & buttons --}}
<div class="bw-hero-wrapper">
    <div class="bw-hero-image-clip">
        <section id="home" class="slider-area fix p-relative">
            <div class="slider-active">
                @php
                    // Slides fade in place, so the first slide's background is the first hero image painted.
                    $firstSlide = collect($sliders)->first();
                    Botble\Theme\Supports\HeroImagePreload::homepageHero($firstSlide?->image ? RvMedia::getImageUrl($firstSlide->image) : null);

                    // One H1 per page: the first hero title, unless the breadcrumb banner already shows the page title as H1.
                    $heroHeading = Theme::get('heroHeading') || (Theme::get('breadcrumb', true) && Theme::get('pageTitle')) ? 'h2' : 'h1';
                @endphp
                @foreach($sliders as $slider)
                    <div class="single-slider slider-bg d-flex align-items-center" style="background-image:url({{ RvMedia::getImageUrl($slider->image) }}); background-size: cover;">
                        <div class="container">
                            <div class="row justify-content-center align-items-center">
                                <div class="col-lg-7 col-md-7">
                                    <div class="slider-content s-slider-content mt-80 text-center">
                                        @if ($title = $slider->title)
                                            <{{ $heroHeading }} data-animation="fadeInUp" data-delay=".4s">{!! BaseHelper::clean($title) !!}</{{ $heroHeading }}>
                                            @php
                                                Theme::set('heroHeading', $heroHeading = 'h2');
                                            @endphp
                                        @endif

                                        @if ($description = $slider->description)
                                            <p data-animation="fadeInUp" data-delay=".6s">{!! BaseHelper::clean($description) !!}</p>
                                        @endif

                                        <div class="slider-btn mt-30 mb-105">
                                            @php
                                                $buttonPrimaryLabel = $slider->getMetaData('button_primary_label', true);
                                                $buttonPrimaryUrl = $slider->getMetaData('button_primary_url', true);
                                                $buttonPlayLabel = $slider->getMetaData('button_play_label', true);
                                                $linkYoutubeUrl = $slider->getMetaData('youtube_url', true);

                                                if ($linkYoutubeUrl) {
                                                    $linkYoutubeUrl = Botble\Theme\Supports\Youtube::getYoutubeVideoID($linkYoutubeUrl);
                                                }
                                            @endphp

                                            @if ($buttonPrimaryUrl && $buttonPrimaryLabel)
                                                <a href="{{ $buttonPrimaryUrl }}" class="btn ss-btn active mr-15" data-animation="fadeInLeft" data-delay=".4s">
                                                    {!! BaseHelper::clean($buttonPrimaryLabel) !!}
                                                </a>
                                            @endif

                                            @if ($buttonPlayLabel && $linkYoutubeUrl)
                                                <a href="https://www.youtube.com/watch?v={{ $linkYoutubeUrl }}" class="video-i popup-video" data-animation="fadeInUp" data-delay=".8s" style="animation-delay: 0.8s;" tabindex="0">
                                                    <i class="fas fa-play"></i>
                                                    {!! BaseHelper::clean($buttonPlayLabel) !!}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
