<?php

namespace App\Http\Controllers;

use App\Services\DatabaseManager;
use App\Util\ChartTypeUtil;
use DateTime;
use Illuminate\Http\Request;

/**
 * DatabaseController responsible of execute queries and visualize output
 */
class DatabaseController extends Controller
{
    /**
     * display the main page
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('index', [
            'host' => session('host'),
            'username' => session('username'),
            'password' => session('password'),
            'database' => session('database'),
            'results' => session('results'),
            'connected' => session('connected'),
            'message' => session('message')
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function connect(Request $request)
    {
        $request->validate([
            'host' => 'required',
            'username' => 'required',
            'password' => 'required',
            'database' => 'required',
        ]);

        // use singleton
        $dbManager = DatabaseManager::getInstance();
        $dbManager->connect(
            $request->input('host'),
            $request->input('username'),
            $request->input('password'),
            $request->input('database')
        );

        if ($dbManager->isConnected()) {
            $response = [
                'host' => $request->input('host'),
                'username' => $request->input('username'),
                'password' => $request->input('password'),
                'database' => $request->input('database'),
                'connected' => $dbManager->isConnected(),
                'message' => 'Connected successfully!',
            ];
        } else {
            $response = [
                'message' => 'Connection Failed!'
            ];
        }
        $this->connectionSession($response);

        return redirect()->route('index');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function executeQuery(Request $request)
    {
        $request->validate([
            'query' => 'required',
        ]);

        $dbManager = DatabaseManager::getInstance();
        $connection = $dbManager->getConnection();
        $query = $request->input('query');
        $results = $connection->select($request->input('query'));

        $chart = null;
        $groupByColumn = $this->validateGroupBy($query);

        if (!empty($results)) {
            $results = $this->prepareResult($results);
            $chart = $this->determineChartConfig($results, $groupByColumn);
        }

        return view('index', [
            'query' => $query,
            'chartData' => json_encode($chart['chart_data']),
            'chartType' => $chart['chart_type'],
            'host' => session('host'),
            'username' => session('username'),
            'password' => session('password'),
            'database' => session('database'),
            'results' => $results,
            'connected' => session('connected'),
            'message' => session('message')
        ]);
    }

    /**
     * Use the dataset to create table of results
     * @param $results
     * @return array
     */
    private function prepareResult($results)
    {
        $headers = isset($results[0]) ? array_keys((array) $results[0]) : [];
        $rows = [];
        foreach ($results as $row) {
            $rows[] = (array) $row;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * validate and check if query has group by to get categorized column
     * @param $query
     * @return string|null
     */
    private function validateGroupBy($query)
    {
        $groupByColumn = null;
        if (preg_match('/\bGROUP BY\b/i', $query)) {
            preg_match('/\bGROUP BY\s+(.+?)\b/i', $query, $matches);
            $groupByColumn = isset($matches[1]) ? $matches[1] : null;
        }
        return $groupByColumn;
    }

    /**
     * prepare and determine the chart configurations
     * @param $dataset
     * @param $groupByColumn
     * @return array
     * @throws \Exception
     */
    private function determineChartConfig($dataset, $groupByColumn)
    {
        $headers = $dataset['headers'];
        $rows = $dataset['rows'];
        $newRows = [];

        // detect and organize the data type of each column in dataset rows
        foreach ($rows as $row) {
            $newRow = [];
            foreach ($row as $key => $value) {
                if (is_numeric($value)) {
                    $newRow[$key] = floatval($value);
                } elseif (strtotime($value) !== false) {
                    $dateTime = new DateTime($value);
                    $datetimeJs = $dateTime->format('Y, n-1, j, G, i, s');
                    $newRow[$key] = $datetimeJs;
                } else {
                    $newRow[$key] = $value;
                }
            }

            $newRows[] = $newRow;
        }

        $rows = $newRows;
        $chartX = null;
        $chartType = null;

        // if query has a group by set by default the chart to be LineChart type
        if ($groupByColumn) {
            $chartX = $groupByColumn;
            $chartType = ChartTypeUtil::LINECHART;
        } else {
            foreach ($rows[0] as $key => $value) {
                if (is_string($value)) {
                    $chartX = $key;
                }
            }
        }

        // search about string column header to shift it in the beginning of the dataset array as X-Axis must be string
        if ($chartX) {
            $indexOfXKey = array_search($chartX, $headers);
            unset($headers[$indexOfXKey]);
            array_unshift($headers, $chartX);

            $rows = array_map(function ($row) use ($chartX) {
                $valueOfX = $row[$chartX];
                unset($row["$chartX"]);
                return array_values(array_merge(["$chartX" => $valueOfX], $row));
            }, $rows);
        } else {
            $rows = array_map(function ($row) {
                return array_values($row);
            }, $rows);
        }

        // if not categorized dataset use ColumnChart or PieChart based on number of columns in header
        if (!$chartType) {
            $chartType = count($headers) > 5 ? ChartTypeUtil::COLUMNCHART : ChartTypeUtil::PIECHART;
        }
        $graph[] = $headers;

        return [
            'chart_type' => $chartType,
            'chart_data' => array_merge($graph, $rows)
        ];
    }

    private function connectionSession($response)
    {
        session([
            'host' => $response['host'] ?? null,
            'username' => $response['username'] ?? null,
            'password' => $response['password'] ?? null,
            'database' => $response['password'] ?? null,
            'connected' => $response['connected'] ?? null,
            'message' => $response['message'] ?? null,
        ]);
    }
}
