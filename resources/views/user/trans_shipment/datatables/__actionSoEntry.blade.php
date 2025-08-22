@if (empty($model->so_no))
    <button class="btn btn-warning btn-sm" style="color:black"
            id="ship-addSoNo"
            title="Add"
            data-idShipHead="{{ $model->id_ship_head }}">
        Add SO No
    </button>
@else
    {{ $model->so_no }} &nbsp;&nbsp;
    <button class="btn btn-warning btn-sm" style="color:white"
            id="ship-editSoNo"
            title="Edit"
            data-idShipHead="{{ $model->id_ship_head }}"
            data-soNo="{{ $model->so_no }}">
    </button>
@endif
