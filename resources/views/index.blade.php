<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laravel Database Query</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Laravel Database Query</h1>

    @if(session('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <form method="post" action="{{ route('connect') }}">
        @csrf
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="host">MySQL Host</label>
                    <input type="text" class="form-control" id="host" name="host" placeholder="Enter host name" value="{{$host ?? ''}}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="database">MySQL Database</label>
                    <input type="text" class="form-control" id="database" name="database" placeholder="Enter database name"  value="{{$database ?? ''}}" required>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="username">MySQL Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" value="{{$username ?? ''}}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="password">MySQL Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" value="{{$password ?? ''}}" required>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Connect</button>
    </form>

    @if(session('connected'))
        <form method="post" action="{{ route('execute-query') }}" class="mt-4">
            @csrf
            <div class="form-group">
                <label for="query">SQL Query:</label>
                <textarea name="query" rows="5" class="form-control" required>{{$query ?? ''}}</textarea>
            </div>
            <button type="submit"  style="margin: 5px" class="btn btn-danger">Execute Query</button>
        </form>
    @endif

    @if(session('message') && isset($results))
        <h2 class="mt-4">Query Chart Visualize:</h2>
        <div id="chart_div" class="mt-4"></div>

        <h2 class="mt-4">Query Results:</h2>
        <table class="table table-bordered">
            <thead>
            <tr>
                @foreach($results['headers'] as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach($results['rows'] as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
@isset($chartData)
<script type="text/javascript">
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawChart);
    function drawChart() {
        var data = google.visualization.arrayToDataTable({!!$chartData!!});
        var chartType = '{{$chartType}}';
        var options = {
            title: chartType,
            bars: 'vertical', // 'horizontal' for a horizontal bar chart
        };

        switch(chartType) {
            case 'LineChart':
                chart = new google.visualization.LineChart(document.getElementById('chart_div'));
                break;
            case 'ColumnChart':
                chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
                break;
            case 'PieChart':
                chart = new google.visualization.PieChart(document.getElementById('chart_div'));
                break;
            default:
                chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        }
        chart.draw(data, options);
    }
</script>
@endif
</body>
</html>
