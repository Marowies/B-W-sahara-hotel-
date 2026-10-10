<?php

namespace Botble\Hotel\Listeners;

use Botble\Hotel\Models\Place;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Models\RoomCategory;
use Botble\Hotel\Models\Service;
use Botble\Theme\Events\RenderingSiteMapEvent;
use Botble\Theme\Facades\SiteMapManager;
use Illuminate\Database\Eloquent\Builder;

class AddSitemapListener
{
    // Sitemap key => [model, priority]. Foods are excluded: menu items are thin pages pending an owner decision.
    public const CONTENT = [
        'room-categories' => [RoomCategory::class, '0.6'],
        'services' => [Service::class, '0.5'],
        'places' => [Place::class, '0.5'],
    ];

    public function handle(RenderingSiteMapEvent $event): void
    {
        if ($event->key == 'rooms') {
            SiteMapManager::add(route('public.rooms'), $this->lastUpdated(Room::class), '0.4', 'monthly');

            $this->addItems($this->published(Room::class), '0.6');
        }

        if (isset(self::CONTENT[$event->key])) {
            [$model, $priority] = self::CONTENT[$event->key];

            $this->addItems($this->published($model), $priority);
        }

        SiteMapManager::addSitemap(SiteMapManager::route('rooms'), $this->lastUpdated(Room::class));

        foreach (self::CONTENT as $key => [$model]) {
            if ($lastUpdated = $this->lastUpdated($model)) {
                SiteMapManager::addSitemap(SiteMapManager::route($key), $lastUpdated);
            }
        }
    }

    protected function published(string $model): Builder
    {
        $query = $model::query()->wherePublished();

        // A category page lists its rooms; without published rooms it is empty.
        if ($model === RoomCategory::class) {
            $query->whereHas('rooms', fn (Builder $rooms) => $rooms->wherePublished());
        }

        return $query;
    }

    protected function lastUpdated(string $model): ?string
    {
        return $this->published($model)->latest('updated_at')->value('updated_at');
    }

    protected function addItems(Builder $query, string $priority): void
    {
        foreach ($query->with(['slugable', 'metadata'])->get() as $item) {
            // Without a slug the URL falls back to the homepage, which is listed elsewhere.
            if (! $item->slugable || ! $item->slugable->key) {
                continue;
            }

            // Published does not necessarily mean indexable: respect the CMS SEO control.
            $seoMeta = $item->getMetaData('seo_meta', true);
            if (is_array($seoMeta) && ($seoMeta['index'] ?? 'index') === 'noindex') {
                continue;
            }

            SiteMapManager::add($item->url, $item->updated_at, $priority);
        }
    }
}
