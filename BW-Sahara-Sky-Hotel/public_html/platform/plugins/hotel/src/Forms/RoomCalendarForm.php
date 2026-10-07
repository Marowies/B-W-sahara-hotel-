<?php

namespace Botble\Hotel\Forms;

use Botble\Base\Forms\Fields\HiddenField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\Hotel\Http\Requests\RoomCalendarRequest;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Models\RoomCalendar;

class RoomCalendarForm extends FormAbstract
{
    public function setup(): void
    {
        $room = Room::query()->findOrFail(request()->route('id'));

        $this
            ->setupModel(new RoomCalendar())
            ->setValidatorClass(RoomCalendarRequest::class)
            ->setFormOption('url', route('ical.store'))
            ->setFormOption('method', 'POST')
            ->disablePermalinkField()
            ->add('room_id', HiddenField::class, [
                'value' => $room->id,
            ])
            ->addBefore('room_id', 'instructions', 'html', [
                'html' => view('plugins/hotel::ical.partials.form-instructions', compact('room'))->render(),
            ])
            ->add('name', TextField::class, [
                'label' => trans('plugins/hotel::ical.calendar_name'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('plugins/hotel::ical.calendar_name_placeholder'),
                ],
                'help_block' => [
                    'text' => trans('plugins/hotel::ical.calendar_name_help'),
                ],
            ])
            ->add('url', TextareaField::class, [
                'label' => trans('plugins/hotel::ical.calendar_url'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('plugins/hotel::ical.calendar_url_placeholder'),
                    'rows' => 3,
                ],
                'help_block' => [
                    'text' => trans('plugins/hotel::ical.calendar_url_help'),
                ],
            ]);

        $this->setBreakFieldPoint('status');

        // Add existing calendars
        $calendars = $room->calendars;

        if ($calendars->isNotEmpty()) {
            $this->addMetaBoxes([
                'existing_calendars' => [
                    'title' => trans('plugins/hotel::ical.existing_calendars') . ' (' . $calendars->count() . ')',
                    'content' => view('plugins/hotel::ical.calendars', compact('calendars', 'room'))->render(),
                    'priority' => 0,
                    'header_actions' => view('plugins/hotel::ical.partials.calendar-actions', compact('room'))->render(),
                ],
            ]);
        }

        // Add export calendar info
        $this->addMetaBoxes([
            'export_calendar' => [
                'title' => trans('plugins/hotel::ical.export_calendar_to_platforms'),
                'content' => view('plugins/hotel::ical.export', compact('room'))->render(),
                'priority' => 1,
            ],
        ]);
    }
}
