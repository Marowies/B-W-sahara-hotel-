<?php

namespace Botble\Faq;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\MetaBox;
use Botble\Base\Models\BaseModel;
use Botble\Faq\Contracts\Faq as FaqContract;
use Botble\Faq\Models\Faq;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Supports\JsonLd;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class FaqSupport implements FaqContract
{
    public function registerSchema(FaqCollection $faqs): void
    {
        if (! $schema = static::schema($faqs->toArray())) {
            return;
        }

        Theme::asset()
            ->container('header')
            ->writeScript('faq-schema', JsonLd::encode($schema), attributes: ['type' => 'application/ld+json']);
    }

    /**
     * FAQPage from question/answer pairs. Questions are plain text decoded once; answers keep their already
     * cleaned HTML (allowed in Answer.text). Pairs missing a question or answer are skipped.
     */
    public static function schema(array $faqs): ?array
    {
        $questions = [];

        foreach ($faqs as $faq) {
            $question = JsonLd::text($faq->getQuestion());
            $answer = trim($faq->getAnswer());

            if (! $question || JsonLd::text($answer) === null) {
                continue;
            }

            $questions[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
            ];
        }

        return $questions ? ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $questions] : null;
    }

    public function saveConfigs(BaseModel|Model $model, string|array|null $data): void
    {
        try {
            $config = $data;

            if (Str::isJson($config)) {
                $config = json_decode($config, true);
            }

            if (! empty($config) && is_array($config)) {
                foreach ($config as $key => $item) {
                    if (! $item[0]['value'] && ! $item[1]['value']) {
                        Arr::forget($config, $key);
                    }
                }
            }

            if (empty($config)) {
                MetaBox::deleteMetaData($model, 'faq_schema_config');
            } else {
                MetaBox::saveMetaBoxData($model, 'faq_schema_config', $config);
            }
        } catch (Throwable $exception) {
            BaseHelper::logError($exception);
        }
    }

    public function renderMetaBox(?Model $model = null): string
    {
        Assets::addStylesDirectly(['vendor/core/plugins/faq/css/faq.css'])
            ->addScriptsDirectly(['vendor/core/plugins/faq/js/faq.js']);
        $value = [];
        $selectedFaqs = [];

        if ($model && $model->getKey()) {
            $value = MetaBox::getMetaData($model, 'faq_schema_config', true);
            $selectedFaqs = MetaBox::getMetaData($model, 'faq_ids', true) ?: [];
        }

        $hasValue = ! empty($value);

        $value = (array) $value;

        foreach ($value as $key => $item) {
            if (! is_array($item)) {
                continue;
            }

            foreach ($item as $subItem) {
                if (is_array($subItem['value'])) {
                    Arr::forget($value, $key);
                }
            }
        }

        $value = json_encode($value);

        $faqs = Faq::query()
            ->select(['id', 'question'])
            ->wherePublished()
            ->latest()
            ->get()
            ->mapWithKeys(fn ($item) => [$item->id => $item->question])
            ->all();

        return view('plugins/faq::schema-config-box', compact('value', 'hasValue', 'faqs', 'selectedFaqs'))->render();
    }
}
