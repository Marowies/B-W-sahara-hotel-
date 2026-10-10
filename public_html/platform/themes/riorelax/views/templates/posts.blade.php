@if ($posts->isNotEmpty())
    {!! Theme::partial('blog.posts', compact('posts')) !!}
@else
    {{-- The breadcrumb banner already holds the page H1; the empty-state message keeps the H1 size only. --}}
    <h2 class="text-center h1">{{ __('Ops! No results found') }}</h2>
    <p class="text-center">
        {{ __('We couldn’t find what you searched for. Try searching again or') }}
        <a class="link-primary custom-link" href="{{ route('public.single', 'blog') }}">{{ __('Back here') }}</a>
    </p>
@endif
