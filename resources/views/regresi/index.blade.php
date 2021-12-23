@extends('layouts.master')

@section('title')
    Data Penjualan
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('/AdminLTE-2/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
@endpush

@section('breadcrumb')
    @parent
    <li class="active">Data Penjualan</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <button onclick="updatePeriode()" class="btn btn-info btn-xs btn-flat"><i class="fa fa-plus-circle"></i> Ubah Periode</button>
            </div>
            <div class="box-body chart-container">
                Please Wait ...
            </div>
            <div class="box-body table-responsive">
                <table class="table table-stiped table-bordered">
                    <thead>
                        <th width="5%">No</th>
                        <th>Tanggal</th>
                        <th>Penjualan</th>
                        <th>Forecasting</th>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@includeIf('regresi.form')
@endsection

@push('scripts')
<script src="{{ asset('/AdminLTE-2/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
<script>
    let table;

    $(function () {
        table = $('.table').DataTable({
            processing: true,
            autoWidth: false,
            ajax: {
                url: '{{ route('regresi.data', [$tanggalAwal, $tanggalAkhir]) }}',
            },
            columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'tanggal'},
                {data: 'penjualan'},
                {data: 'forecasting'},
            ],
            dom: 'Brt',
            bSort: false,
            bPaginate: false,
        });

        $('.datepicker').datepicker({
            format: 'yyyy-mm',
            minViewMode: 'months',
            autoclose: true
        });
        
        loadChart();
    });

    function loadChart(){
        $.ajax({
            url : '{{ route('regresi.chart', [$tanggalAwal, $tanggalAkhir]) }}',
            dataType : 'html',
            success : function(resp){
                $(".chart-container").html(resp);
            },
            error : function(resp){
                alert("Sorry, we cannot process your request right now");
            }
        });
    }

    function updatePeriode() {
        $('#modal-form').modal('show');
    }
</script>
@endpush