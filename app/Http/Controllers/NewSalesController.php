<?php

namespace App\Http\Controllers;

use App\Models\Staff;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class NewSalesController extends Controller


{
    public function getTotalSales()
    {
        // Run the raw SQL query to calculate the sum of total sales
        
        try {
            $result = DB::select(DB::raw('SELECT
            matter_department_id,
            matter_staff_id,
            SUM(cost_unit_price) AS total_cost_unit_price,
            SUM(sales_unit_price) AS total_sales_unit_price,
            SUM(profit_total) AS total_profit
            FROM t_sales
            GROUP BY matter_department_id, matter_staff_id
            LIMIT 1250;'));
        
            return response()->json(['result' => $result]);
        } catch (\Exception $e) {
            // Handle the exception, log the error, or return an appropriate response
            return response()->json(['error' => $e->getMessage()], 500);
        }
        
        // Extract the total sales value from the result
        // $totalSales = $result[0]->total_sales;
        
        // Return the total sales as a response
    }
    public function getTotalAmount(Request $request, $staffId)
    {
        try {
            // Run the SQL query to calculate the sum of total sales for the selected staff
            $result = DB::select(DB::raw('
                SELECT
                    SUM(cost_unit_price) AS total_cost_unit_price,
                    SUM(sales_unit_price) AS total_sales_unit_price,
                    SUM(profit_total) AS total_profit
                FROM t_sales
                WHERE matter_staff_id = :staffId
            '), ['staffId' => $staffId]);

            return response()->json(['result' => $result[0]]);
        } catch (\Exception $e) {
            // Handle the exception, log the error, or return an appropriate response
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
