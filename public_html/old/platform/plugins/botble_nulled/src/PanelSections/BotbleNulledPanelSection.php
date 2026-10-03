<?php

namespace Botble\BotbleNulled\PanelSections;

use Botble\Base\PanelSections\PanelSection;

class BotbleNulledPanelSection extends PanelSection
{
    public function setup(): void
    {
        $this
            ->setId('settings.{id}')
            ->setTitle('{title}')
            ->withItems([
                //
            ]);
    }
}
