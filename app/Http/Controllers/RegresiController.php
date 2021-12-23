<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Http\Libraries\RegresiLinier;
use Illuminate\Http\Request;

class RegresiController extends Controller
{
    public $regresiLinier;

    public function index(Request $request)
    {
        $tanggalAwal = date('Y-m', mktime(0, 0, 0, date('m'), 0, date('Y')));
        $tanggalAkhir = date('Y-m');

        if ($request->has('tanggal_awal') && $request->tanggal_awal != "" && $request->has('tanggal_akhir') && $request->tanggal_akhir) {
            $tanggalAwal = $request->tanggal_awal;
            $tanggalAkhir = $request->tanggal_akhir;
        }

        return view('regresi.index', compact('tanggalAwal', 'tanggalAkhir'));
    }

    public function chart($awal, $akhir){
        // will return view of chart
        $datatableData = $this->getData($awal, $akhir, 'short');
        $data = $this->generateForecasting($datatableData, false);

        // chartjs need array of field & array of values
        $chartData = [];
        $chartData['color'] = [];
        $i = 1;
        foreach($data as $row){
            $i++;
            $chartData['field'][] = trim($row['tanggal']);
            $chartData['value']['original'][] = floatval($row['penjualan']);
            $chartData['value']['forecasting'][] = $row['forecasting'];
            $chartData['color'] = [
                'original' => '#4169E1',
                'forecasting' => '#67E7C2',
            ];
        }


        $chartData['field'][] = "Forecasting";
        $chartData['value']['original'][] = round($this->regresiLinier->forecast($i), 2);
        $chartData['value']['forecasting'][] = round($this->regresiLinier->forecast($i), 2);

        return view('regresi.chart', compact('chartData'));
    }

    public function getData($awal, $akhir, $date_mode='long')
    {
        $no = 1;
        $data = array();
        $item = 0;

        while (strtotime($awal) <= strtotime($akhir)) {
            $tanggal = $awal;
            $awal = date('Y-m', strtotime("+1 month", strtotime($awal)));

            $jumlah_penjualan = Penjualan::where('created_at', 'LIKE', "%$tanggal%")->sum('total_item');

            $item += $jumlah_penjualan;

            $row = array();
            $row['DT_RowIndex'] = $no++;
            if($date_mode == 'long'){
                $row['tanggal'] = tanggal_indonesia($tanggal, false);
            }
            else{
                $row['tanggal'] = short_tanggal_indonesia($tanggal, false);
            }
            $row['penjualan'] = ($jumlah_penjualan);
            $row['forecasting'] = 0;

            $data[] = $row;
        }

        return $data;
    }

    public function data($awal, $akhir)
    {
        // raw results of "penjualan"
        $data = $this->getData($awal, $akhir);
        $additionalRow = true;
        $data = $this->generateForecasting($data, $additionalRow);

        return datatables()
            ->of($data)
            ->make(true);
    }

    public function generateForecasting($data, $additionalRow = false){
        $x = $y = []; //create blank array to collect x index & raw data
        $iteration = 1;
        foreach($data as $row){
            // if datatable row has index = contain data
            if(intval($row['DT_RowIndex']) > 0){
                $x[] = $iteration;
                $iteration++;
                $y[] = $row['penjualan'];
            }
        }

        $this->regresiLinier = new RegresiLinier($x, $y);
        $forecasting_value = $this->regresiLinier->forecast($iteration); 

        $new_iteration = 1;
        $total_data = 0;
        $total_forecasting = 0;
        foreach($data as $index => $row){
            if(intval($row['DT_RowIndex']) > 0){
                $used_key = array_search($new_iteration, $this->regresiLinier->x);
                if($used_key !== false){
                    $current_forecasting_value = $this->regresiLinier->all[$used_key] ?? 0;
                    $data[$index]['forecasting'] = round($current_forecasting_value, 2);
                    $total_forecasting += $current_forecasting_value;
                }
                $total_data += $row['penjualan'];
                $new_iteration++;
            }
        }

        if($additionalRow){
            // additional rows
            $data[] = [
                'DT_RowIndex' => '',
                'tanggal' => '',
                'penjualan' => '',
                'forecasting' => '',
            ];
            $data[] = [
                'DT_RowIndex' => '',
                'tanggal' => 'Jumlah Penjualan',
                'penjualan' => ($total_data),
                'forecasting' => round($total_forecasting, 2),
            ];
            $data[] = [
                'DT_RowIndex' => '',
                'tanggal' => 'Next Forecast',
                'penjualan' => '',
                'forecasting' => round($forecasting_value, 2), //total of forecasting
            ];
        }

        return $data;
    }
    
    public function perhitungan()
    {
        $awal = '2020-01-01';
        $no = 1;
        $data = array();
        $item = 0;

        while (strtotime($awal) <= strtotime($akhir)) {
            $tanggal = $awal;
            $awal = date('Y-m', strtotime("+1 month", strtotime($awal)));

            $jumlah_penjualan = Penjualan::where('created_at', 'LIKE', "%$tanggal%")->sum('total_item');

            $item += $jumlah_penjualan;

            $row = array();
            $row['DT_RowIndex'] = $no++;
            $row['tanggal'] = tanggal_indonesia($tanggal, false);
            $row['penjualan'] = ($jumlah_penjualan);

            $data[] = $row;
        }

        $data[] = [
            'DT_RowIndex' => '',
            'tanggal' => 'Jumlah Penjualan',
            'penjualan' => ($item),
        ];

        return $data;
    }
}