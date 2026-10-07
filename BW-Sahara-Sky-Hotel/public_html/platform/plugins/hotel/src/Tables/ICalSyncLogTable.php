<?php

namespace Botble\Hotel\Tables;

use Botble\Base\Facades\Html;
use Botble\Hotel\Models\ICalSyncLog;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\IdColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;

class ICalSyncLogTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(ICalSyncLog::class)
            ->addActions([
                DeleteAction::make()->route('ical.logs.destroy'),
            ])
            ->addBulkActions([
                DeleteBulkAction::make()->permission('ical.logs.destroy'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('room_id', function (ICalSyncLog $item) {
                return $item->room->name;
            })
            ->editColumn('status', function (ICalSyncLog $item) {
                return view('plugins/hotel::ical.partials.status', compact('item'))->render();
            })
            ->editColumn('message', function (ICalSyncLog $item) {
                return Html::link(route('ical.logs.show', $item->id), $item->message);
            });

        return $this->toJson($data);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $query = $this
            ->getModel()
            ->query()
            ->select([
                'id',
                'room_id',
                'calendar_id',
                'status',
                'message',
                'created_at',
            ])
            ->with(['room', 'calendar']);

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            Column::make('room_id')
                ->title(trans('plugins/hotel::room.name'))
                ->searchable(false)
                ->orderable(false),
            Column::make('status')
                ->title(trans('core/base::tables.status'))
                ->searchable(false)
                ->orderable(false),
            Column::make('message')
                ->title(trans('plugins/hotel::ical.message'))
                ->searchable()
                ->orderable(false),
            CreatedAtColumn::make(),
        ];
    }
}
