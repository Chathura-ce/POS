<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function daily(Request $request): JsonResponse
    {
        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

        $report = Sale::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as transaction_count, COALESCE(SUM(total), 0) as total_sales')
            ->first();

        return response()->json([
            'date' => $date->toDateString(),
            'total_sales' => (float) $report->total_sales,
            'transaction_count' => (int) $report->transaction_count,
        ]);
    }
}
