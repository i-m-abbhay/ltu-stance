<?php
namespace App\Http\Controllers;

use Storage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\QuoteRequest;
use App\Models\Matter;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Staff;
use App\Models\StaffDepartment;
use App\Models\Construction;
use App\Models\CalendarData;
use App\Models\LockManage;
use App\Models\Quote;

/**
 * 見積依頼一覧
 */
class QuoteRequestListController extends Controller
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
        $Customer = new Customer();
        $Department = new Department();
        $Staff = new Staff();
        $Matter = new Matter();
        $Construction = new Construction();
        $CalendarData = new CalendarData();

        // 得意先
        $customerData = $Customer->getComboList(); 
        // 部門
        $departmentData = $Department->getComboList();
        // 担当
        $staffData = $Staff->getComboList();
        // 担当者部門
        $staffDepartmentData = (new StaffDepartment)->getCurrentTermList();
        $staffDepartmentData = $staffDepartmentData->mapToGroups(function ($item, $key) {
            return [
                $item->department_id => $item->staff_id,
            ];
        });
        // 案件
        $matterData = $Matter->getComboList();
        // 見積依頼項目
        $quoteRequestKbnData = $Construction->getQreqData(config('const.flg.on'));

        $departmentId = (new StaffDepartment())->getMainDepartmentByStaffId(Auth::id())['department_id'];
        $initSearchParams = collect([
            'staff_id' => Auth::id(),
            'department_id' => $departmentId,
            'request_date_from' => Carbon::today()->subMonthNoOverFlow(1)->format('Y/m/d'),
            'request_date_to' => Carbon::today()->format('Y/m/d'),
        ]);

        return view('QuoteRequest.quote-request-list')
            ->with('matterData', $matterData)
            ->with('customerData', $customerData)
            ->with('departmentData', $departmentData)
            ->with('staffData', $staffData)
            ->with('staffDepartmentData', $staffDepartmentData)
            ->with('quoteRequestKbnData', $quoteRequestKbnData)
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
            
            $Quote = new Quote();
            $QuoteRequest = new QuoteRequest();
            $LockManage = new LockManage();

            // 一覧データ取得(キーをIDに変更)
            $quoteRequests = $QuoteRequest->getList($params)->keyBy('id');

            // 見積依頼IDの配列を作成
            $quoteRequestIDs = $quoteRequests->pluck('id')->toArray();
            $quoteIDs = $quoteRequests->pluck('quote_id')->toArray();

            // 見積依頼に紐づく見積明細を取得し、見積依頼IDごとにcollectionを作成する
            $createdQuoteRequestKbn = [];
            foreach ($QuoteRequest->getCreatedQuoteRequestKbn($quoteRequestIDs) as $value) {
                // 見積依頼IDのキーが存在しない場合は作成する
                if (!isset($createdQuoteRequestKbn[$value['id']])) {
                    $createdQuoteRequestKbn[$value['id']] = collect();
                }
                $createdQuoteRequestKbn[$value['id']]->put($value['detail_id'], $value);
            }

            // 見積依頼項目（アイコン使用するだけなので削除フラグを考慮せずallで取得）
            $allConstruction = (Construction::all())->keyBy('id');

            // ロック情報取得
            $lockData = $LockManage->getLockDataForList(QuoteEditController::SCREEN_NAME, $Quote->getTable(), $quoteIDs);

            // 添付書類、見積依頼項目、作成状況
            foreach ($quoteRequests as $qRequestId => $value) {
                /**
                 * view側の制御用
                 */
                $elemCtrl = [
                    'btn_create_quote' => [ 
                        'disabled' => true,
                    ],
                ];
                // 見積作成ボタンチェック
                if ($quoteRequests[$qRequestId]['status'] == config('const.quoteRequestStatus.val.requested')) {
                    $elemCtrl['btn_create_quote']['disabled'] = false;
                }
                $quoteRequests[$qRequestId]['elem_ctrl'] = $elemCtrl;

                /**
                 * 添付書類
                 */
                $attachedDocuments = [];
                $fileList = Storage::files(config('const.uploadPath.quote_request'). $value['id']);
                foreach ($fileList as $file) {
                    $ext = substr($file, strrpos($file, '.') + 1);
                    $document['filename'] = substr($file, strrpos($file, '/') + 1);
                    switch ($ext) {
                        case in_array($ext, config('const.AttachedFilesIcon.extension.excel')):
                            $document['icon'] = config('const.AttachedFilesIcon.icon.excel');
                            $attachedDocuments[] = $document;
                            break;
                        case in_array($ext, config('const.AttachedFilesIcon.extension.word')):
                            $document['icon'] = config('const.AttachedFilesIcon.icon.word');
                            $attachedDocuments[] = $document;
                            break;
                        case in_array($ext, config('const.AttachedFilesIcon.extension.cad')):
                            $document['icon'] = config('const.AttachedFilesIcon.icon.cad');
                            $attachedDocuments[] = $document;
                            break;
                        case config('const.AttachedFilesIcon.extension.pdf'):
                            $document['icon'] = config('const.AttachedFilesIcon.icon.pdf');
                            $attachedDocuments[] = $document;
                            break;
                        default:
                            $document['icon'] = config('const.AttachedFilesIcon.icon.etcetera');
                            $attachedDocuments[] = $document;
                            break;
                    }
                }
                $quoteRequests[$qRequestId]['attached_documents'] = $attachedDocuments;

                /**
                 * 見積依頼項目、作成状況
                 */
                // 見積依頼項目の値がキーの連想配列を作成 [1, 2, 3] ⇒ [ '1'=>[], '2'=>[], '3'=>[] ]
                $quoteRequestKbn = [];
                foreach (json_decode($quoteRequests[$qRequestId]['quote_request_kbn']) as $constructionId) {
                    $quoteRequestKbn[$constructionId] = [];
                }
                // 見積依頼項目の状態をカウント（作成状況の算出に使用）
                $creationStatus = [
                    config('const.quoteRequestListCreationStatus.status.not_treated') => 0,
                    config('const.quoteRequestListCreationStatus.status.making') => 0,
                    config('const.quoteRequestListCreationStatus.status.complete') => 0,
                ];
                foreach (array_keys($quoteRequestKbn) as $constructionId) {
                    // アイコン紐づけ
                    $quoteRequestKbn[$constructionId]['icon'] = $allConstruction[$constructionId]['icon'];

                    // 見積階層に見積依頼の見積依頼項目が存在するか？ ※存在する場合はキー、しない場合はfalse
                    $searchResult = $createdQuoteRequestKbn[$qRequestId]->search(function($item, $k) use($constructionId){
                        return ($item['construction_id'] == $constructionId);
                    });
                    if ($searchResult) {
                        // 存在する場合（作成中、積算完了）
                        $searchResult = $createdQuoteRequestKbn[$qRequestId]->search(function($item, $k) use($constructionId){
                            return (
                                $item['construction_id'] == $constructionId
                                && $item['complete_flg'] == config('const.flg.on')
                            );
                        });
                        if ($searchResult) {
                            // 積算完了
                            $quoteRequestKbn[$constructionId]['class'] = config('const.quoteRequestListKbn.complete.class');
                            $creationStatus[config('const.quoteRequestListCreationStatus.status.complete')]++;
                        } else {
                            // 作成中
                            $quoteRequestKbn[$constructionId]['class'] = config('const.quoteRequestListKbn.making.class');
                            $creationStatus[config('const.quoteRequestListCreationStatus.status.making')]++;
                        }
                    } else {
                        // 存在しない場合（未処理）
                        $quoteRequestKbn[$constructionId]['class'] = config('const.quoteRequestListKbn.not_treated.class');
                        $creationStatus[config('const.quoteRequestListCreationStatus.status.not_treated')]++;
                    }
                }
                $quoteRequests[$qRequestId]['quote_request_kbn'] = array_values($quoteRequestKbn);

                // 作成状況セット
                if ($creationStatus[config('const.quoteRequestListCreationStatus.status.not_treated')] == count($quoteRequestKbn)) {
                    // 未処理
                    $quoteRequests[$qRequestId]['creation_status'] = [
                        'text' => config('const.quoteRequestListCreationStatus.not_treated.text'),
                        'class' => config('const.quoteRequestListCreationStatus.not_treated.class')
                    ];
                } elseif ($creationStatus[config('const.quoteRequestListCreationStatus.status.complete')] == count($quoteRequestKbn)) {
                    // 積算済
                    $quoteRequests[$qRequestId]['creation_status'] = [
                        'text' => config('const.quoteRequestListCreationStatus.complete.text'),
                        'class' => config('const.quoteRequestListCreationStatus.complete.class')
                    ];
                } else {
                    // 作成中 or 編集中
                    $quoteRequests[$qRequestId]['creation_status'] = [
                        'text' => config('const.quoteRequestListCreationStatus.making.text'),
                        'class' => config('const.quoteRequestListCreationStatus.making.class')
                    ];
                    if ($lockData->contains('key_id', $value['quote_id'])) {
                        $quoteRequests[$qRequestId]['creation_status'] = [
                            'text' => config('const.quoteRequestListCreationStatus.editing.text'),
                            'class' => config('const.quoteRequestListCreationStatus.editing.class')
                        ];
                    }
                }

            }

        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($quoteRequests->values());
    }
}