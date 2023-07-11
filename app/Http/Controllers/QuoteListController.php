<?php
namespace App\Http\Controllers;

use DB;
use Storage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\ApprovalDetail;
use App\Models\ApprovalHeader;
use App\Models\Quote;
use App\Models\QuoteVersion;
use App\Models\QuoteDetail;
use App\Models\Matter;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Staff;
use App\Models\StaffDepartment;
use App\Models\Company;
use App\Models\Construction;
use App\Libs\SystemUtil;
use App\Libs\Common;
use App\Models\Notice;
use App\Exceptions\ValidationException;

/**
 * 見積一覧
 */
class QuoteListController extends Controller
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
        $StaffDepartment= new StaffDepartment();
        $Matter = new Matter();
        // $Quote = new Quote();
        $Construction = new Construction();

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
        // 見積
        // $quoteData = $Quote->getComboList();
        // 見積項目
        $quoteItemData = $Construction->getQreqData(config('const.flg.on'));

        $departmentId = $StaffDepartment->getMainDepartmentByStaffId(Auth::id())['department_id'];
        $initSearchParams = collect([
            'staff_id' => Auth::id(),
            'department_id' => $departmentId,
        ]);

        return view('Quote.quote-list')
            // ->with('quoteData', $quoteData)
            ->with('matterData', $matterData)
            ->with('customerData', $customerData)
            ->with('departmentData', $departmentData)
            ->with('staffData', $staffData)
            ->with('staffDepartmentData', $staffDepartmentData)
            ->with('quoteItemData', $quoteItemData)
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
            
            $QuoteVersion = new QuoteVersion();
            $ApprovalHeader = new ApprovalHeader();
            $ApprovalDetail = new ApprovalDetail();

            // 一覧データ取得(Collectionのキーを見積版IDに変更)
            $quoteVer = $QuoteVersion->getList($params)->keyBy('id');

            // 見積依頼IDの配列を作成
            $quoteVerIDs = $quoteVer->pluck('id')->toArray();

            // 見積項目（以降の処理で項目名称を引き当てるだけなので、削除フラグを気にせずall()で取得）
            $constructionAll = (Construction::all())->keyBy('id');

            // 承認ヘッダ取得（見積版、見積版ID、過去フラグOff）及び紐づく承認明細を取得
            $approvalHeader = $ApprovalHeader->getByProcessId(config('const.approvalProcessKbn.quote'), $quoteVerIDs, config('const.flg.off'));
            $approvalHeaderIDs = $approvalHeader->pluck('id')->toArray();
            $approvalDetail = $ApprovalDetail->getByHeaderId($approvalHeaderIDs);

            // 担当者情報取得（申請者、承認者の情報取得） 
            $approvalStaffIDs = array_unique(array_merge(
                                    $approvalHeader->pluck('apply_staff_id')->unique()->toArray(),
                                    $approvalDetail->pluck('approval_staff_id')->unique()->toArray()
                                ));
            $approvalStaffs = (Staff::whereIn('id', $approvalStaffIDs)->get())->keyBy('id');

            $loginId = Auth::id();

            // 承認ヘッダをループさせ、一覧表示用の承認データを作成する（キーは見積版ID）
            $approvalData = [];
            foreach ($approvalHeader as $header) {
                // 見積版ID
                $quoteVerId = $header->process_id;

                // 見積版IDのキーが存在しない場合は作成する
                if (!isset($approvalData[$quoteVerId])) {
                    $approvalData[$quoteVerId] = [];
                }
                
                // 明細抽出（承認ヘッダのIDに紐づく）
                $details = $approvalDetail->where('approval_header_id', $header->id);

                // (承認ヘッダ有 And 承認明細無) = 自動承認データ
                if ($details->count() == 0) {
                    $approvalData[$quoteVerId]['histories'][] = [
                        'staff_name' => $approvalStaffs[$header['apply_staff_id']]['staff_name'],
                        'comment' => config('const.autoApprovalComment'),
                        'date' => Carbon::parse($header['update_at'])->format('m/d H:i'),
                        'class' => config('const.approvalDetailStatus.class')[config('const.approvalDetailStatus.val.approved')]
                    ];
                    $approvalData[$quoteVerId]['judge_btn']['show'] = false;
                    $approvalData[$quoteVerId]['judge_btn']['is_valid'] = false;
                    continue;
                }

                $showJudgeBtn = true;
                $isValidJudgeBtn = true;

                // 承認済 or 差戻の承認明細を取得
                $histories = $details
                                ->whereIn('status',[
                                    config('const.approvalDetailStatus.val.approved'),
                                    config('const.approvalDetailStatus.val.sendback')
                                ]);
                if ($histories->count() > 0) {
                    // 承認者名、承認日時、背景色（承認 or 差戻）
                    foreach ($histories as  $history) {
                        $approvalData[$quoteVerId]['histories'][] = [
                            'staff_name' => $approvalStaffs[$history['approval_staff_id']]['staff_name'],
                            'comment' => $history['comment'],
                            'date' => Carbon::parse($history['update_at'])->format('m/d H:i'),
                            'class' => config('const.approvalDetailStatus.class')[$history['status']]
                        ];
                    }
                }

                // ヘッダが承認済み又は差戻なら承認・差戻ボタンを表示しない
                if ($header->status == config('const.approvalHeaderStatus.val.approved') ||
                    $header->status == config('const.approvalHeaderStatus.val.sendback')) {
                        $showJudgeBtn = false;
                }

                if ($showJudgeBtn) {
                    // ログイン者の承認明細を取得
                    $detailByLoginStaff = $details->where('approval_staff_id', $loginId);
                    if ($detailByLoginStaff->count() > 0) {
                        // 承認者である
                        // 承認済ならdisabled
                        if ($detailByLoginStaff->contains('status', config('const.approvalDetailStatus.val.approved'))) {
                            $isValidJudgeBtn = false;
                        }
                    }else{
                        // 承認者ではない
                        $isValidJudgeBtn = false;
                    }
                }

                $approvalData[$quoteVerId]['judge_btn']['show'] = $showJudgeBtn;
                $approvalData[$quoteVerId]['judge_btn']['is_valid'] = $isValidJudgeBtn;
            }

            // 添付書類、見積依頼項目、作成状況
            foreach ($quoteVer as $key => $value) {
                $quoteVer[$key]['isLatest'] = false;
                $maxQuoteVerNo = $quoteVer->where('quote_no', $value['quote_no'])->max('quote_version');
                if ($quoteVer[$key]['quote_version'] == $maxQuoteVerNo) {
                    $quoteVer[$key]['isLatest'] = true;
                }
                
                $quoteVer[$key]['isNotApproved'] = false;
                if ($quoteVer[$key]['status'] != config('const.quoteVersionStatus.val.approved')) {
                    $quoteVer[$key]['isNotApproved'] = true;
                }

                /**
                 * 添付書類
                 */
                $attachedDocuments = [];
                $fileList = Storage::files(config('const.uploadPath.quote_version'). $value['id']);
                foreach ($fileList as $filekey => $file) {
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
                $quoteVer[$key]['attached_documents'] = $attachedDocuments;

                /**
                 * 見積項目
                 */
                // 配列の値（見積依頼項目）がキーの連想配列を作成  [1, 2, 3] ⇒ ['1'=>0, '2'=>1, '3'=>2]
                $quoteItem = [];
                foreach (json_decode($quoteVer[$key]['quote_item']) as $constructionId) {
                    $iconParam = [];
                    $iconParam['icon'] = $constructionAll[$constructionId]['icon'];
                    switch ($quoteVer[$key]['status']) {
                        case config('const.quoteVersionStatus.val.editing'):
                            $iconParam['class'] = config('const.quoteListStatus.editing.class');
                            break;
                        case config('const.quoteVersionStatus.val.applying'):
                            $iconParam['class'] = config('const.quoteListStatus.applying.class');
                            break;
                        case config('const.quoteVersionStatus.val.approved'):
                            $iconParam['class'] = config('const.quoteListStatus.approved.class');
                            break;
                        case config('const.quoteVersionStatus.val.sendback'):
                            $iconParam['class'] = config('const.quoteListStatus.sendback.class');
                            break;
                    }
                    $quoteItem[$constructionId]['icon_info'] = $iconParam;
                }
                $quoteVer[$key]['quote_item'] = array_values($quoteItem);

                // 承認情報
                $quoteVer[$key]['approval'] = null;
                if (isset($approvalData[$key])) {
                    $quoteVer[$key]['approval'] = $approvalData[$key];
                }
            }
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($quoteVer->values());
    }

    /**
     * 承認、差戻処理
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function judge(Request $request)
    {
        $result = array('status' => false, 'msg' => '');

        // リクエストデータ取得
        $params = $request->request->all();

        // 差戻の場合のみコメントのバリデーションチェックをする
        if (!$params['is_approval']) {
            // バリデーションチェック
            $this->isJudgeValid($request);
        }

        $QuoteVersion = new QuoteVersion();
        $SystemUtil = new SystemUtil();
        $ApprovalHeader = new ApprovalHeader();
        $Notice = new Notice();

        DB::beginTransaction();
        try {
            $SystemUtil->approvalForProcessing(config('const.approvalProcessKbn.quote'), $params);

            $quoteVersion = $QuoteVersion->getById($params['process_id']);

            // 承認ステータス取得
            $approvalInfo = $ApprovalHeader->getByProcessId(config('const.approvalProcessKbn.quote'), $params['process_id'], config('const.flg.off'));
            $approvalInfo = $approvalInfo->first();

            $quoteVersionStatus = config('const.quoteVersionStatus.val.applying');
            switch ($approvalInfo->status) {
                case config('const.approvalHeaderStatus.val.approved'):
                    $noticeParam['content'] = config('message.notice.quote_approval');
                    $quoteVersionStatus = config('const.quoteVersionStatus.val.approved');
                    break;
                case config('const.approvalHeaderStatus.val.sendback'):
                    $noticeParam['content'] = config('message.notice.quote_sendback');
                    $quoteVersionStatus = config('const.quoteVersionStatus.val.sendback');
                    break;
            }

            // 見積版 見積状況を更新
            $QuoteVersion->updateStatusById($params['process_id'], $quoteVersionStatus);

            // 承認済 or 差戻なら申請者に通知
            if ($quoteVersionStatus == config('const.quoteVersionStatus.val.approved') || $quoteVersionStatus == config('const.quoteVersionStatus.val.sendback')) {
                // お知らせ追加
                $noticeParam = [
                    'notice_flg' => config('const.flg.off'),
                    'staff_id' => $quoteVersion->staff_id,
                    'content' => $quoteVersionStatus == config('const.quoteVersionStatus.val.approved') ? config('message.notice.quote_approval'):config('message.notice.quote_sendback'),
                    'redirect_url' => 'quote-list/' . '?quote_no=' . $quoteVersion->quote_no
                ];
                if ($quoteVersionStatus == config('const.quoteVersionStatus.val.approved')) {
                    $noticeParam['redirect_url'] .= '&'. 'fil_chk_not_approved_only_id=0';
                }
                $noticeParam['redirect_url'] = url($noticeParam['redirect_url']);
                $Notice->add($noticeParam);
            }

            DB::commit();
            $result['status'] = true;
        }catch(ValidationException $e) {
            DB::rollBack();
            $result['msg'] = $e->getErrorMessage();
        }catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
        }
        return \Response::json($result);
    }

    /**
     * バリデーションチェック
     * @param Request $request
     * @return type
     */
    private function isJudgeValid($request)
    {
        $this->validate($request, [
            'comment' => 'required',
        ]);
    }

}