<?php

namespace Botble\Blog\Supports;

use Botble\Blog\Models\Post;
use Botble\Media\Facades\RvMedia;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Supports\JsonLd;

class PostSchema
{
    public const TYPES = ['NewsArticle', 'News', 'Article', 'BlogPosting'];

    /**
     * Article node from the post as published. Empty optional properties are omitted, and the author is a
     * name only: posts store no verified author profile URL, so none is invented.
     */
    public static function make(Post $post, ?string $type = null): array
    {
        $logo = Theme::getLogo();
        $authorName = class_exists((string) $post->author_type) ? JsonLd::text($post->author?->name) : null;

        return self::compact([
            '@context' => 'https://schema.org',
            '@type' => in_array($type, self::TYPES, true) ? $type : 'NewsArticle',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $post->url,
            ],
            'headline' => JsonLd::text($post->name),
            'description' => JsonLd::text($post->description),
            // Only the post's own image: the site placeholder does not represent the article.
            'image' => $post->image ? ['@type' => 'ImageObject', 'url' => RvMedia::getImageUrl($post->image)] : null,
            'author' => $authorName ? ['@type' => 'Person', 'name' => $authorName] : null,
            'publisher' => [
                '@type' => 'Organization',
                'name' => JsonLd::text(Theme::getSiteTitle()),
                'logo' => $logo ? ['@type' => 'ImageObject', 'url' => RvMedia::getImageUrl($logo)] : null,
            ],
            'datePublished' => $post->created_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
        ]);
    }

    // Drops null/empty values, and nested nodes left with nothing but their @type.
    protected static function compact(array $node): array
    {
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $value = self::compact($value);
                $node[$key] = array_diff_key($value, ['@type' => true]) === [] ? null : $value;
            }

            if ($node[$key] === null || $node[$key] === '') {
                unset($node[$key]);
            }
        }

        return $node;
    }
}
