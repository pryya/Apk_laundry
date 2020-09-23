<?php

namespace App\Http\Controllers\API;

use App\Expense;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseCollection;
use Illuminate\Http\Request;

class ExpensesController extends Controller
{
    public function index()
    {
        $user = request()->user(); // AMBIL DATA USER YANG SEDANG LOGIN
        $expenses = Expense::with(['user'])->orderBy('created_at', 'DESC'); // GET DATA EXPENSES YANG DIURUTKAN BERDASARKAN DATA TERBARU BESERTA SEBUAH EAGER LOADING

        // APABILA ADA PENCARIAN
        if (request()->q != '') {
            // MAKA AMBIL DATA BERDASARKAN PENCARIAN YANG DILAKUKAN
            $expenses = $expenses->where('description', 'LIKE', '%' . request()->q . '%');
        }

        // JIKA ROLE USER YANG LOGIN ADALAH 1 (ADMIN) & 3 (KURIR), MAKA AMBIL DATA KHUSUS MEREKA SAJA
        if (in_array($user->role, [1, 3])){
            $expenses = $expenses->where('user_id', $user->id);
        }
        return (new ExpenseCollection($expenses->paginate(10)));
    }
}
