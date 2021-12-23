<div style="width:100%; height:200px;">
    <canvas id="regresiChart" style="width:100%; height:200px;"></canvas>
</div>
<script src="{{ asset('AdminLTE-2/bower_components/chart.js/Chart.js') }}"></script>
<script>
$(function() {
    var regresiChartCanvas = $('#regresiChart').get(0).getContext('2d');
    var regresiChart = new Chart(regresiChartCanvas);

    var regresiChartData = {
        labels: {!! json_encode($chartData['field']) !!},
        datasets: [
            @foreach($chartData['value'] as $cname => $cvalues)
            {
                label: '{{ ucwords($cname) }}',
                fillColor           : 'transparent',
                strokeColor         : '{{ $chartData['color'][$cname] ?? '#333' }}',
                pointColor          : '{{ $chartData['color'][$cname] ?? '#333' }}',
                pointStrokeColor    : '{{ $chartData['color'][$cname] ?? '#333' }}',
                pointHighlightFill  : '#fff',
                pointHighlightStroke: '{{ $chartData['color'][$cname] ?? '#333' }}',
                data: {!! json_encode($cvalues) !!}
            },
            @endforeach
        ]
    };

    var regresiChartOptions = {
        responsive : true,
    };

    regresiChart.Line(regresiChartData, regresiChartOptions);
});
</script>