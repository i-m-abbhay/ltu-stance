<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Matter;
use App\Models\General;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Address;
use App\Models\StaffDepartment;
use App\Models\Authority;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use \Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 案件一覧
 */
class MatterListController extends Controller
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
     * 初期表示
     * @return type
     */
    public function index()
    {
        // 得意先
        $customerData = (new Customer)->getComboList();
        // 部門
        $departmentData = (new Department())->getComboList();
        // 担当
        $staffData = (new Staff)->getComboList();
        // 担当者部門
        $staffDepartmentData = (new StaffDepartment)->getCurrentTermList();
        $staffDepartmentData = $staffDepartmentData->mapToGroups(function ($item, $key) {
            return [
                $item->department_id => $item->staff_id,
            ];
        });
        // 案件
        $matterData = (new Matter)->getComboList();
        // 住所
        $addressData = (new Address)->getComboList();
        // 案件データの権限保持しているか
        $hasMatterAuth = Authority::hasAuthority(Auth::id(), config('const.auth.matter.edit'));

        $departmentId = (new StaffDepartment())->getMainDepartmentByStaffId(Auth::id())['department_id'];
        $initSearchParams = collect([
            'staff_id' => Auth::id(),
            'department_id' => $departmentId,
            'matter_create_date_from' => Carbon::today()->subMonth(3)->format('Y/m/d'),
            'matter_create_date_to' => Carbon::today()->format('Y/m/d'),
        ]);

        return view('Matter.matter-list')
            ->with('matterData', $matterData)
            ->with('customerData', $customerData)
            ->with('departmentData', $departmentData)
            ->with('staffData', $staffData)
            ->with('staffDepartmentData', $staffDepartmentData)
            ->with('addressData', $addressData)
            ->with('hasMatterAuth', $hasMatterAuth)
            ->with('initSearchParams', $initSearchParams)
        ;
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
            
            // 一覧データ取得
            $Matter = new Matter();
            $list = $Matter->getList($params);

            $matters = $list->keyBy('id');
            $matterIDs = $list->pluck('id')->toArray();

            // 案件と紐づく見積依頼取得, Collectionのキーを案件IDに変更
            $quoteRequest = $Matter->getQuoteRequestStatus($matterIDs);
            $quoteRequest = $quoteRequest->mapToGroups(function ($item, $key) {
                return [$item['id'] => $item];
            });

            // 案件と紐づく見積取得, Collectionのキーを案件IDに変更
            $quote = $Matter->getQuoteStatus($matterIDs);
            $quote = $quote->keyBy('id');

            // 案件と紐づく発注取得, Collectionのキーを案件IDに変更
            $order = $Matter->getOrderStatus($matterIDs);
            $order = $order->keyBy('id');

            // 案件に見積依頼、見積、発注を紐づける。
            foreach ($matters as $key => $value) {
                // 見積依頼
                $quoteRequest[$key] = $quoteRequest[$key]->firstWhere('quote_request_id', $quoteRequest[$key]->max('quote_request_id'));
                $quoteRequestKey = $quoteRequest[$key]['quote_request_status'];
                $matters[$key]['quote_request_status'] = array(
                    'id' => $quoteRequest[$key]['quote_request_id'],
                    'text' => config('const.matterListProgress.quote_request.'. $quoteRequestKey. '.text'),
                    'class' => config('const.matterListProgress.quote_request.'. $quoteRequestKey. '.class'),
                );

                // 見積
                $quoteKey = $quote[$key]['quote_status'];
                $matters[$key]['quote_status'] = array(
                    'text' => config('const.matterListProgress.quote.'. $quoteKey. '.text'),
                    'class' => config('const.matterListProgress.quote.'. $quoteKey. '.class'),
                );

                // 発注
                $orderKey = $order[$key]['order_status'];
                $matters[$key]['order_status'] = array(
                    'text' => config('const.matterListProgress.order.'. $orderKey. '.text'),
                    'class' => config('const.matterListProgress.order.'. $orderKey. '.class'),
                );
            }

        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($matters->values());
    }

    /**
     * CSV出力
     *
     * @param Request $request
     * @return void
     */
    public function download(Request $request) 
    {
        $response = new StreamedResponse();
        // パラメータ取得
        $params = $request->request->all();
        $matterIDs = json_decode($params['matter_id'], true);

        // ヘッダー行作成
        $columns[] = array(
                '"案件番号"'
                ,'"案件名"'
                , '"得意先名"'
                , '"郵便番号"'
                , '"住所1"'
                , '"住所2"'
                , '"緯度（日本測地計）"'
                , '"経度（日本測地計）"'
                , '"緯度（世界測地計）"'
                , '"経度（世界測地計）"'
        );

        $Matter = new Matter();
        // データ取得
        $csvData = $Matter->getCsvData($matterIDs);
        $csvData = $csvData->toArray();

        $response->setCallBack(function() use($csvData, $columns){
            $file = new \SplFileObject('php://output', 'w');

            // ヘッダー挿入
            foreach ($columns as $row) {
                $file->fputcsv(array_map(function ($value) {
                    return mb_convert_encoding($value, 'SJIS-win', 'ASCII, JIS, UTF-8, SJIS');
                }, $row), ",", "\n");
            }

            // データ挿入
            foreach ($csvData as $row) {

                $file->fputcsv(array_map(function ($value) {
                    return mb_convert_encoding($value, 'SJIS-win', 'ASCII, JIS, UTF-8, SJIS');
                }, $row), ",");
            }
            flush();
        });

        // レスポンス作成
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/octet-stream');
        // ファイル名作成
        $fileNm = 'MatterList_';
        $fileNm .= Carbon::now()->format('Ymd');
        $fileNm .= ".csv";
        $response->headers->set('Content-Disposition', 'attachment; filename='.$fileNm);
        $response->send();

        return $response;
    }
}