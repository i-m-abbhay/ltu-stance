<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\System;
use App\Models\ProductstockShelf;
use App\Models\Warehouse;
use Session;
use Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 商材選択
 */
class CounterSaleController extends Controller
{

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }

    /**
     * 
     * 初期表示
     * @return type
     */
    public function index()
    {
        //税率取得
        $System = new System();
        $list = $System->getByPeriod();
        $taxRate = 1;
        if ($list != null) {
            $taxRate =  $list['tax_rate'] / 100;
        }

        // 倉庫リスト取得
        $Warehouse = new Warehouse();
        $latitude = Session::get('latitude');
        $longitude = Session::get('longitude');
        $warehouseList = $Warehouse->getOrderByLocation($latitude, $longitude);
        $default_warehouse_id = null;
        if ($warehouseList != null && count($warehouseList) > 0) {
            $default_warehouse_id = $warehouseList[0]['id'];
        }

        // 商品取得
        $ProductstockShelf = new ProductstockShelf();
        $productList = $ProductstockShelf->getComboCounterSale($default_warehouse_id);

        //メーカーリスト
        $makerList = [];
        $index = 0;
        foreach ($productList as $row) {
            $warehouse = [];
            //メーカーリスト存在チェック
            $isExist = false;
            $makerIndex = 0;
            foreach ($makerList as $maker) {
                if ($maker['maker_id'] == $row['maker_id']) {
                    //倉庫追加
                    array_push($makerList[$makerIndex]['warehouseList'], $row['warehouse_id']);
                    $isExist = true;
                }
                $makerIndex++;
            }
            //存在しなかったらリストに追加
            if (!$isExist) {
                $warehouse[] = $row['warehouse_id'];
                $makerList[] = array('maker_id' => $row['maker_id'], 'supplier_name' => $row['supplier_name'], 'warehouseList' => $warehouse);
            }

            $index++;
        }

        //商品リスト
        $itemList = [];
        $index = 0;
        foreach ($productList as $row) {
            $warehouse = [];
            $makerIdList = [];
            $row['normal_sales_price'] = (int)$row['normal_sales_price'];
            //商品ID存在チェック
            $isExist = false;
            $makerIndex = 0;
            foreach ($itemList as $item) {
                if ($item['id'] == $row['id']) {
                    //倉庫,メーカー追加
                    array_push($itemList[$makerIndex]['warehouseList'], $row['warehouse_id']);
                    array_push($itemList[$makerIndex]['makerIdList'], $row['maker_id']);
                    $isExist = true;
                }
                $makerIndex++;
            }
            //存在しなかったらリストに追加
            if (!$isExist) {
                $warehouse[] = $row['warehouse_id'];
                $makerIdList[] = $row['maker_id'];
                $itemList[] = array(
                    'id' => $row['id'], 'product_code' => $row['product_code'], 'unit' => $row['unit'], 'price' => $row['price'],
                    'normal_sales_price' => $row['normal_sales_price'], 'product_name' => $row['product_name'], 'model' => $row['model'],
                    'normal_purchase_price' => $row['normal_purchase_price'],
                    'purchase_price' => $row['purchase_price'],
                    'actual_quantity' => $row['actual_quantity'],
                    'min_quantity' => $row['min_quantity'],
                    'next_arrival_date' => $row['next_arrival_date'],
                    'arrival_quantity' => $row['arrival_quantity'],
                    'maker_id' => $row['maker_id'],
                    'supplier_name' => $row['supplier_name'],
                    'stock_unit' => $row['stock_unit'],
                    'warehouseList' => $warehouse, 'makerIdList' => $makerIdList
                );
            }

            $index++;
        }

        return view('Sales.counter-sale')
            ->with('default_warehouse_id', $default_warehouse_id)
            ->with('productList', json_encode($itemList))
            ->with('makerList', json_encode($makerList))
            ->with('warehouseList', json_encode($warehouseList))
            ->with('taxRate', $taxRate);
    }

    /**
     * 検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            $productId = $params['productId'];
            $warehouseId = $params['warehouseId'];

            // 一覧データ取得
            $ProductstockShelf = new ProductstockShelf();
            $data = $ProductstockShelf->getProduct($productId, $warehouseId);
            
            // if (count($data) != 0) {
            //     foreach ($data as $row) {
            //         $row['normal_sales_price'] = (int)$row['normal_sales_price'];
            //     }
            // }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return \Response::json($data);
    }

    /**
     * 
     * コンボボックスデータ取得
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function getList(Request $request)
    {
        // リクエストから検索条件取得
        $warehouseId = $request->request->all();

        // 商品取得
        $ProductstockShelf = new ProductstockShelf();
        $productList = $ProductstockShelf->getComboCounterSale($warehouseId);

        //メーカーリスト
        $makerList = [];
        $index = 0;
        foreach ($productList as $row) {
            $warehouse = [];
            //メーカーリスト存在チェック
            $isExist = false;
            $makerIndex = 0;
            foreach ($makerList as $maker) {
                if ($maker['maker_id'] == $row['maker_id']) {
                    //倉庫追加
                    array_push($makerList[$makerIndex]['warehouseList'], $row['warehouse_id']);
                    $isExist = true;
                }
                $makerIndex++;
            }
            //存在しなかったらリストに追加
            if (!$isExist) {
                $warehouse[] = $row['warehouse_id'];
                $makerList[] = array('maker_id' => $row['maker_id'], 'supplier_name' => $row['supplier_name'], 'warehouseList' => $warehouse);
            }

            $index++;
        }

        //商品リスト
        $itemList = [];
        $index = 0;
        foreach ($productList as $row) {
            $warehouse = [];
            $makerIdList = [];
            //商品ID存在チェック
            $isExist = false;
            $makerIndex = 0;
            foreach ($itemList as $item) {
                if ($item['id'] == $row['id']) {
                    //倉庫,メーカー追加
                    array_push($itemList[$makerIndex]['warehouseList'], $row['warehouse_id']);
                    array_push($itemList[$makerIndex]['makerIdList'], $row['maker_id']);
                    $isExist = true;
                }
                $makerIndex++;
            }
            //存在しなかったらリストに追加
            if (!$isExist) {
                $warehouse[] = $row['warehouse_id'];
                $makerIdList[] = $row['maker_id'];
                $itemList[] = array(
                    'id' => $row['id'], 'product_code' => $row['product_code'], 'unit' => $row['unit'], 'price' => $row['price'],
                    'normal_sales_price' => $row['normal_sales_price'], 'product_name' => $row['product_name'], 'model' => $row['model'],
                    'normal_purchase_price' => $row['normal_purchase_price'],
                    'purchase_price' => $row['purchase_price'],
                    'actual_quantity' => $row['actual_quantity'],
                    'min_quantity' => $row['min_quantity'],
                    'next_arrival_date' => $row['next_arrival_date'],
                    'arrival_quantity' => $row['arrival_quantity'],
                    'maker_id' => $row['maker_id'],
                    'supplier_name' => $row['supplier_name'],
                    'stock_unit' => $row['stock_unit'],
                    'warehouseList' => $warehouse, 'makerIdList' => $makerIdList
                );
            }

            $index++;
        }

        $data = ['productList'=> $itemList,
                 'makerList'=> $makerList];

        return \Response::json($data);
    }
}
