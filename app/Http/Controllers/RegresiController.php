<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\Produk;
use App\Http\Libraries\RegresiLinier;
use Illuminate\Http\Request;

class RegresiController extends Controller
{
    public $regresiLinier;

    public function index(Request $request)
    {
        $tanggalAwal = date('Y-m', mktime(0, 0, 0, date('m'), 0, date('Y')));
        $tanggalAkhir = date('Y-m');

        $produk = null;        
        $produk_id = $request->produk_id;
        if($produk_id){
            $produk = Produk::findOrFail($produk_id);
        }

        if ($request->has('tanggal_awal') && $request->tanggal_awal != "" && $request->has('tanggal_akhir') && $request->tanggal_akhir) {
            $tanggalAwal = $request->tanggal_awal;
            $tanggalAkhir = $request->tanggal_akhir;
        }

        $list_produk = Produk::get(['id_produk', 'nama_produk', 'kode_produk']);
        return view('regresi.index', compact('tanggalAwal', 'tanggalAkhir', 'produk', 'produk_id', 'list_produk'));
    }

    public function chart(Request $request, $awal, $akhir){
        // will return view of chart
        $datatableData = $this->getData($awal, $akhir, 'short', $request->produk_id);
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

    public function getData($awal, $akhir, $date_mode='long', $produk_id=null)
    {
        $no = 1;
        $data = array();
        $item = 0;

        while (strtotime($awal) <= strtotime($akhir)) {
            $tanggal = $awal;
            $awal = date('Y-m', strtotime("+1 month", strtotime($awal)));

            if($produk_id > 0){
                // jika ada parameter produk_id, cari ditabel detail saja
                $jumlah_penjualan = PenjualanDetail::where('created_at', 'LIKE', "%$tanggal%")
                    ->where('id_produk', $produk_id)
                    ->sum('jumlah');
            }
            else{
                $jumlah_penjualan = Penjualan::where('created_at', 'LIKE', "%$tanggal%")->sum('total_item');
            }

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

    public function data(Request $request, $awal, $akhir)
    {
        // raw results of "penjualan"
        $data = $this->getData($awal, $akhir, null, $request->produk_id);
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
        $total_em = 0;
        $count_data = 0;
        foreach($data as $index => $row){
            if(intval($row['DT_RowIndex']) > 0){
                $used_key = array_search($new_iteration, $this->regresiLinier->x);
                if($used_key !== false){
                    $current_forecasting_value = $this->regresiLinier->all[$used_key] ?? 0;
                    $em = abs($current_forecasting_value - $row['penjualan']);
                    $data[$index]['forecasting'] = round($current_forecasting_value, 2);
                    $data[$index]['error_margin'] = round($em, 2);
                    $total_forecasting += $current_forecasting_value;
                    $total_em += $em;
                }
                $total_data += $row['penjualan'];
                $new_iteration++;
                $count_data++;
            }
        }

        if($additionalRow){
            // additional rows
            $data[] = [
                'DT_RowIndex' => '',
                'tanggal' => '',
                'penjualan' => '',
                'forecasting' => '',
                'error_margin' => '',
            ];
            $data[] = [
                'DT_RowIndex' => '',
                'tanggal' => 'Jumlah Penjualan',
                'penjualan' => ($total_data),
                'forecasting' => round($total_forecasting, 2),
                'error_margin' => '',
            ];
            $data[] = [
                'DT_RowIndex' => '',
                'tanggal' => 'Next Forecast',
                'penjualan' => '',
                'forecasting' => round($forecasting_value, 2), //total of forecasting
                'error_margin' => '',
            ];
            $data[] = [
                'DT_RowIndex' => '',
                'tanggal' => 'Error Margin Average',
                'penjualan' => '',
                'forecasting' => '',
                'error_margin' => round($total_em/$count_data, 2), //total of forecasting
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