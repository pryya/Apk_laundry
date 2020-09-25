<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Transaction;
use App\DetailTransaction;
use Carbon\Carbon;
use App\Payment;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        //VALIDASI
        $this->validate($request, [
            'customer_id' => 'required',
            'detail' => 'required'
        ]);

        //MENGGUNAKAN DATABASE TRANSACTION
        DB::beginTransaction();
        try {
            $user = $request->user(); //GET USER YANG SEDANG LOGIN
            //BUAT DATA TRANSAKSI
            $transaction = Transaction::create([
                'customer_id' => $request->customer_id['id'],
                'user_id' => $user->id,
                'amount' => 0
            ]);

            $amount = 0; // UNTUK DI CALCULATE SEBAGAI AMOUNT TOTAL TRANSAKSI

            //KARENA DATA ITEM NYA LEBIH DARI SATU MAKA KITA LOOPING
            foreach ($request->detail as $row) {
                //DIMANA DATA YANG DITERIMA HANYALAH ITEM YANG LAUNDRY_PRICE (PRODUCT) NYA SUDAH DIPILIH
                if (!is_null($row['laundry_price'])) {
                    //MELAKUKAN PERHITUNGAN KEMBALI DARI SISI BACKEND UNTUK MENENTUKAN SUBTOTAL
                    $subtotal = $row['laundry_price']['price'] * $row['qty'];
                    if ($row['laundry_price']['unit_type'] == 'Kilogram') {
                       $subtotal = ($row['laundry_price']['price'] * $row['qty']) / 1000;
                    }

                    $start_date = Carbon::now(); // DEFINISIKAN UNTUK START DATE-NYA
                    $end_date = Carbon
                    ::now()->addHours($row['laundry_price']['service']); // DEFAULTNYA  KITA DEFINISIKAN END DATE MENGGUNAKAN ADDHOURS
                    if ($row['laundry_price']['service_type'] == 'Hari') {
                        // AKAN TETAPI, JIKA SERVICENYA ADALAH HARI MAKA END_DATE AKAN DI-REPLACE DENGAN ADDDAYS()
                        $end_date = Carbon::now()->addDays($row['laundry_price']['service']);
                    }
                    //MENYIMPAN DATA DETAIL TRANSAKSI
                    DetailTransaction::create([
                        'transaction_id' => $transaction->id,
                        'laundry_price_id' => $row['laundry_price']['id'],
                        'laundry_type_id' => $row['laundry_price']['laundry_type_id'],

                        // SIMPAN INFORMASINYA KE DATABASE
                        'start_date' => $start_date->format('Y-m-d H:i:s'),
                        'end_date' => $end_date->format('Y-m-d H:i:s'),

                        'qty' => $row['qty'],
                        'price' => $row['laundry_price']['price'],
                        'subtotal' => $subtotal
                    ]);

                    $amount += $subtotal; // KALKULASIKAN AMOUNT UNTUK SETIAP LOOPINYA
                }
            }
            $transaction->update(['amount' => $amount]); // UPDATE INFORMASI PADA TABLE TRANSACTIONS
            //APABILA TIDAK TERJADI ERROR, MAKA KITA COMMIT AGAR BENAR2 MENYIMPAN DATANYA
            DB::commit();
            return response()->json(['status' => 'success', 'data' => $transaction]);
        } catch (\Exception $e) {
            DB::rollback(); //JIKA TERJADI ERROR, MAKA DIROLLBACK AGAR DATA YANG BERHASIL DISIMPAN DIHAPUS
            return response()->json(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        //LAKUKAN QUERY KE DATABASE UNTUK MENCARI ID TRANSAKSI TERKAIT
        //KITA JUGA ME-LOAD DATA LAINNYA MENGGUNAKAN EAGER LOADING, MAKA SELANJUTNYA AKAN DI DEFINISIKAN FUNGSINYA
        $transaction = Transaction::with(['customer', 'payment', 'detail', 'detail.product'])->find($id);
        return response()->json(['status' => 'success', 'data' => $transaction]);
    }

    public function completeItem(Request $request)
    {
        //VALIDASI UNTUK MENGECEK IDNYA ADA ATAU TIDAK
        $this->validate($request, [
            'id' => 'required|exists:detail_transactions,id'
        ]);

        //LOAD DATA DETAIL TRANSAKSI BERDASARKAN ID
        $transaction = DetailTransaction::with(['transaction.customer'])->find($request->id);
        //UPDATE STATUS DETAIL TRANSAKSI MENJADI 1 YANG BERARTI SELESAI
        $transaction->update(['status' => 1]);
        //UPDATE DATA CUSTOMER TERKAIT DENGAN MENAMBAHKAN 1 POINT
        $transaction->transaction->customer()->update(['point' => $transaction->transaction->customer->point + 1]);
        return response()->json(['status' => 'success']);
    }

    public function makePayment(Request $request)
    {
        //VALIDASI REQUEST
        $this->validate($request, [
            'transaction_id' => 'required|exists:transactions,id',
            'amount' => 'required|integer'
        ]);

        DB::beginTransaction();
        try {
            //CARI TRANSAKSI BERDASARKAN ID
            $transaction = Transaction::find($request->transaction_id);

            //SET DEFAULT KEMBALI = 0
            $customer_change = 0;
            if ($request->customer_change) {
                //JIKA CUSTOMER_CHANGE BERNILAI TRUE
                $customer_change = $request->amount - $transaction->amount; //MAKA DAPATKAN BERAPA BESARAN KEMBALIANNYA

                //TAMBAHKAN KE DEPOSIT CUSTOMER
                $transaction->customer()->update(['deposit' => $transaction->customer->deposit + $customer_change]);
            }

            //SIMPAN INFO PEMBAYARAN
            Payment::create([
                'transaction_id' => $transaction->id,
                'amount' => $request->amount,
                'customer_change' => $customer_change,
                'type' => false
            ]);
            //UPDATE STATUS TRANSAKSI JADI 1 BERARTI SUDAH DIBAYAR
            $transaction->update(['status' => 1]);
            //JIKA TIDAK ADA ERROR, COMMIT PERUBAHAN
            DB::commit();
            return response()->json(['status' => 'success', 'data' => $transaction]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'failed', 'data' => $e->getMessage()]);
        }
    }
}
