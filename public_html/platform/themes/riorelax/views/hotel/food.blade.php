@php
    Theme::set('pageTitle', $food->name);
@endphp

<section>
    <div class="about-area5 about-p p-relative">
        <div class="container pt-30 pb-90">
            <div class="row align-items-center mb-50">
                <div class="col-12">
                    <div class="price">
                        <h2>{{ $food->name }}</h2>
                        <span>{{ format_price($food->price) }}</span>
                    </div>
                </div>
            </div>

            <div class="row align-items-center mb-50">
                {{ RvMedia::image($food->image, $food->name, 'room-image') }}
            </div>

            <div class="row ck-content">
                {!! BaseHelper::clean($food->content) !!}
            </div>
        </div>
    </div>
</section>
