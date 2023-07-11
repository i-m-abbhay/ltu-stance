<?php
namespace App\Http\Controllers;

use Session;
use DB;
use Storage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ApprovalDetail;
use App\Models\ApprovalHeader;
use App\Models\QuoteVersion;
use App\Models\QuoteDetail;
use App\Models\Staff;
use App\Models\Company;
use App\Models\Quote;
use App\Models\Matter;
use App\Libs\Common;

/**
 * 見積書
 */
class QuoteReportController extends Controller
{

    const SUMMARY_CONSTRUCTION_ID = 0;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }

    /**
     * 初期表示（印刷）
     *
     * @param [type] $quoteVersionId
     * @return void
     */
    public function print($quoteVersionId){
        Session::forget('flash_success');

        $QuoteVersion = new QuoteVersion();

        $data = $this->getPrintData($quoteVersionId, false);

        return view('Quote.quote-report')
            ->with('quoteVersion', $QuoteVersion->getById($quoteVersionId))
            ->with('dataSource', json_encode($data))
        ;
    }

    /**
     * 初期表示（指定印刷）
     *
     * @param [type] $quoteVersionId
     * @return void
     */
    public function printTarget($quoteVersionId){
        Session::forget('flash_success');

        $QuoteVersion = new QuoteVersion();

        $data = $this->getPrintData($quoteVersionId, true);

        return view('Quote.quote-report')
            ->with('quoteVersion', $QuoteVersion->getById($quoteVersionId))
            ->with('dataSource', json_encode($data))
        ;
    }

    /**
     * 更新
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)  
    {
        $result['status'] = true;

        // リクエストから検索条件取得
        $params = $request->request->all();

        $Quote = new Quote();
        $QuoteVersion = new QuoteVersion();
        $Matter = new Matter();

        DB::beginTransaction();
        try {
            $qVer = $QuoteVersion->getById($params['quote_version_id']);
            $quote = $Quote->getByQuoteNo($qVer->quote_no);

            // 見積更新
            $seqNo = ($Quote->lockForUpdate()->where('id', '=', $quote->id)->first())['quote_report_seq_no'];
            $seqNo++;
            $quoteParam = ['id' => $quote->id, 'quote_report_seq_no' => $seqNo];
            $Quote->updateById($quoteParam);

            // 見積版更新
            $data = [
                'id' => $qVer->id,
                'quote_no' => $qVer->quote_no,
                'quote_version' => $qVer->quote_version,
                'print_date' => Carbon::today(),
            ];
            $QuoteVersion->updateById($data);

            $matterNo = ($Quote->getByQuoteNo($qVer->quote_no))->matter_no;
            $matter = $Matter->getbyMatterNo($matterNo);

            $file = request()->file;
            request()->file->storeAs(
                config('const.uploadPath.quote_version'). '/'. $qVer->id,
                config('const.quoteReport.file_prefix'). $matter->matter_name. '_'. Carbon::now()->format('Ymd'). '_'. $seqNo. '.pdf'
            );
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            Log::error($e);
        }
        
        return \Response::json($result);
    }

    /**
     * 見積書データ取得
     *
     * @param [type] $quoteVersionId 見積版ID
     * @param [type] $isTarget 指定印刷？
     * @return void
     */
    private function getPrintData($quoteVersionId, $isTarget){
        $Staff = new Staff();
        $Company = new Company();
        $QuoteVersion = new QuoteVersion();
        $QuoteDetail = new QuoteDetail();
        $ApprovalHeader = new ApprovalHeader();
        $ApprovalDetail = new ApprovalDetail();

        // 会社情報(1レコードしか無い想定)
        $companyInfo = $Company::all()->first();

        // 従業員情報（印鑑取得用）
        $allStaff = $Staff::all()->keyBy('id');

        // 見積データ
        $quoteVer = $QuoteVersion->getQuoteReportDataById($quoteVersionId);

        /* 2ページ目以降 */
        $quoteDetails = $QuoteDetail->getQuoteReportByKey($quoteVer->quote_no, $quoteVer->quote_version, $isTarget);

        // 親見積IDごと
        $layers['total']    // 小計,合計
        = $layers['cnt']    // 階層配下の明細数（階層配下の小計,合計、 自階層の小計,合計含む）
        = $layers['forgive_overflow']    // 見積書で溢れを許容する
        = $quoteDetails->where('layer_flg', config('const.flg.on'))->mapWithKeys(function ($item) {
            return [$item['id'] => 0];
        })->toArray();

        // 工事区分の親見積明細IDは0なのでセット
        $layers['total'][config('const.quoteConstructionInfo.parent_quote_detail_id')]
        = $layers['cnt'][config('const.quoteConstructionInfo.parent_quote_detail_id')]
        = $layers['forgive_overflow'][config('const.quoteConstructionInfo.parent_quote_detail_id')]
        = 0;

        $maxDepth = $quoteDetails->max('depth');
        for ($i = $maxDepth; $i > -1; $i--) { 
            // 見積明細取得（深さ別）
            $detailsByDepth = $quoteDetails->where('depth', $i);
            foreach ($detailsByDepth as $key => $detail) {
                if ($detail->layer_flg) {
                    // 階層
                    if ($detail->sales_use_flg) {
                        // 販売額使用
                        $layers['total'][$detail->id] = $detail->sales_total;                           // 自合計書き換え
                        $layers['total'][$detail->parent_quote_detail_id] += $detail->sales_total;      // 親合計に販売額を加算
                    }else{
                        // 販売額不使用
                        $layers['total'][$detail->parent_quote_detail_id] += $layers['total'][$detail->id]; // 親合計に自合計を加算
                    }

                    // 階層は小計,合計行を含んでカウントする為、+1ではなく+2
                    $layers['cnt'][$detail->id] = $layers['cnt'][$detail->id] + 2;
                    $layers['cnt'][$detail->parent_quote_detail_id] = $layers['cnt'][$detail->parent_quote_detail_id] + $layers['cnt'][$detail->id];
                }else{
                    // 明細
                    $layers['total'][$detail->parent_quote_detail_id] += $detail->sales_total;          // 親合計に販売額を加算
                    $layers['cnt'][$detail->parent_quote_detail_id]++;
                }
            }
        }

        // 出力データに見積データを格納する為の再帰処理
        // 出力データ
        $detailData = [];
        $template = clone $QuoteDetail;
        Common::initModelProperty($template);
        $insRec = function($childRecords) use(&$insRec, &$detailData, $quoteDetails, $layers, $template){
            foreach ($childRecords as $record) {
                // 明細印字フラグが立っている
                if ($record->row_print_flg) {
                    $detailData[] = $this->formatRec($record, $quoteDetails);
                }

                $template->product_name = config('const.quoteReport.product_name.subtotal');
                if ($record->layer_flg == config('const.flg.on')) {
                    // 階層
                    // 子データ取得
                    $grandChildRecords = $quoteDetails->where('parent_quote_detail_id', $record->id)->sortBy('seq_no');
                    if ($grandChildRecords->count() > 0) {
                        // 子データが存在
                        $insRec($grandChildRecords);
                    }else{
                        // 子データが存在しない
                        // 『小計』行印字, 明細印字フラグ・売価印字フラグがたっている
                        if ($record->row_print_flg && $record->price_print_flg) {
                            $template->construction_id = $record->construction_id;
                            $template->sales_total = $layers['total'][$record->id];
                            $template->depth = $record->depth;
                            $template->tree_path = $record->tree_path;
                            $detailData[] = $this->formatRec($template, $quoteDetails);
                        }
                    }
                }else{
                    // 明細
                    $parentQuoteDetail = $quoteDetails->firstWhere('id', $record->parent_quote_detail_id);

                    // 『小計』行印字
                    // 親階層の明細印字フラグ・売価印字フラグがたっている
                    // 同一階層のソート最大値(seq_no) == レコードのソート(seq_no) && 深さ2以上（深さ0=工事区分には小計行を作成しない）
                    if(
                        $parentQuoteDetail->row_print_flg && $parentQuoteDetail->price_print_flg &&
                        $childRecords->max('seq_no') == $record->seq_no && $record->depth > 1
                    ){
                        $template->construction_id = $parentQuoteDetail->construction_id;
                        $template->sales_total = $layers['total'][$record->parent_quote_detail_id];
                        $template->depth = $record->depth-1;
                        $template->tree_path = $quoteDetails->firstWhere('id', $record->parent_quote_detail_id)->tree_path;
                        $detailData[] = $this->formatRec($template, $quoteDetails);
                    }
                }
            }
        };

        Common::initModelProperty($template);
        $detailsDepth0 = $quoteDetails->where('depth', 0)->sortBy('seq_no');
        // 見積明細の最初は各工事種別の合計値
        foreach ($detailsDepth0 as $key => $detail) {
            if ($isTarget) {
                // 指定印刷の場合は階層金額を変更する
                $detail->sales_total = $layers['total'][$detail->id];
            }
            $detailData[] = $this->formatRec($detail, $quoteDetails, true);
        }
        $template->product_name = config('const.quoteReport.product_name.total');
        $template->sales_total = $layers['total'][config('const.quoteConstructionInfo.parent_quote_detail_id')];
        $template->depth = config('const.quoteConstructionInfo.depth');
        $template->tree_path = config('const.quoteConstructionInfo.tree_path');
        $detailData[] = $this->formatRec($template, $quoteDetails, true);

        Common::initModelProperty($template);
        foreach ($detailsDepth0 as $key => $detail) {
            // 明細行印字フラグがたっている
            if ($detail->row_print_flg) {
                $detailData[] = $this->formatRec($detail, $quoteDetails);
            }
            $childRecords = $quoteDetails->where('parent_quote_detail_id', $detail->id)->sortBy('seq_no');
            $insRec($childRecords);
            $template->construction_id = $detail->construction_id;
            $template->product_name = config('const.quoteReport.product_name.total');
            $template->sales_total = $layers['total'][$detail->id];
            $template->depth = $detail->depth;
            $template->tree_path = $detail->tree_path;
            // 『合計』行印字, 明細行印字フラグ・売価印字フラグがたっている
            if ($detail->row_print_flg && $detail->price_print_flg) {
                $detailData[] = $this->formatRec($template, $quoteDetails);
            }
        }

        $detailData = $this->processPageBreak($detailData, $layers);

        /* 1ページ目 */
        $stampFilePath = null;
        $companyStamp = null;
        $stamp = [];

        // 社印
        if ($quoteVer->status == config('const.quoteVersionStatus.val.approved')) {
            $stampFilePath = config('const.uploadPath.company_stamp'). $companyInfo->id. '/'. $companyInfo->stamp;
            if (Storage::exists($stampFilePath)) {
                $companyStamp = base64_encode(Storage::get($stampFilePath));
            }
        }

        // 担当者印
        $stampFilePath = config('const.uploadPath.staff_stamp'). $quoteVer->staff_id. '/'. $allStaff[$quoteVer->staff_id]->stamp;
        if (Storage::exists($stampFilePath)) {
            $stamp[] = base64_encode(Storage::get($stampFilePath));
        }

        // 承認者ハンコ
        $approvalHeader = $ApprovalHeader->getByProcessId(config('const.approvalProcessKbn.quote'), $quoteVersionId, config('const.flg.off'));
        // 承認ヘッダが存在する
        if ($approvalHeader->count() > 0) {
            $approvalHeader = $approvalHeader->first(); 
            $approvalDetails = $ApprovalDetail->getByHeaderId($approvalHeader->id);
            // 承認明細が存在する　※承認明細が存在しない(=得意先ランクを満たして自動承認されている)
            if ($approvalDetails->count() > 0) {
                // 承認明細取得(直近の承認者2人。コレクション反転)
                $approvalDetails = $approvalDetails
                    ->where('approval_staff_id', '!=', $quoteVer->staff_id)                 // 申請者が承認ルートにいる場合は除く
                    ->where('status', config('const.approvalDetailStatus.val.approved'))    // 承認済
                    ->sortByDesc('approval_order')
                    ->slice(0, 2)
                    ->sortBy('approval_order');

                // 承認済 And 承認者が1人　※承認者のハンコ欄は2つある
                if ($approvalHeader->status == config('const.approvalHeaderStatus.val.approved') && $approvalDetails->count() == 1) {
                    if ($approvalHeader->final_approval_order == 1) {
                        // ハンコ例）無:承認者印:担当者印
                        $approvalStaffId = $approvalDetails->first()->approval_staff_id;
                        $stampFilePath = config('const.uploadPath.staff_stamp'). $approvalStaffId. '/'. $allStaff[$approvalStaffId]->stamp;
                    }else{
                        // ハンコ例）承認者印:無:担当者印
                        $approvalStaffId = $approvalDetails->first()->approval_staff_id;
                        $stampFilePath = config('const.uploadPath.staff_stamp'). $approvalStaffId. '/'. $allStaff[$approvalStaffId]->stamp;
                        $stamp[] = null;
                    }
                    if (Storage::exists($stampFilePath)) {
                        $stamp[] = base64_encode(Storage::get($stampFilePath));
                    }
                }else{
                    foreach ($approvalDetails as $key => $value) {
                        $approvalStaffId = $value->approval_staff_id;
                        $stampFilePath = config('const.uploadPath.staff_stamp'). $approvalStaffId. '/'. $allStaff[$approvalStaffId]->stamp;
                        if (Storage::exists($stampFilePath)) {
                            $stamp[] = base64_encode(Storage::get($stampFilePath));
                        }
                    }
                }
            }
        }
        // 配列の要素数を3つに整える
        $stamp = array_pad($stamp, 3, null);

        $taxAmount = $layers['total'][config('const.quoteConstructionInfo.parent_quote_detail_id')];
        switch ($quoteVer->tax_rounding) {
            case config('const.taxRounding.val.round_down'):
                $taxAmount = floor($taxAmount * ($quoteVer->tax_rate / 100));
                break;
            case config('const.taxRounding.val.round_up'):
                $taxAmount = ceil($taxAmount * ($quoteVer->tax_rate / 100));
                break;
            case config('const.taxRounding.val.round'):
                $taxAmount = round($taxAmount * ($quoteVer->tax_rate / 100));
                break;
        }


        $data = array(
            // 印鑑
            config('const.quoteReport.jpn_key.stamp.parentkey') => [
                config('const.quoteReport.jpn_key.stamp.company_stamp') => $companyStamp,
                config('const.quoteReport.jpn_key.stamp.stamp1') => $stamp[0],
                config('const.quoteReport.jpn_key.stamp.stamp2') => $stamp[1],
                config('const.quoteReport.jpn_key.stamp.stamp3') => $stamp[2],
            ],
            // 会社情報
            config('const.quoteReport.jpn_key.company.parentkey') => [
                config('const.quoteReport.jpn_key.company.name') => $companyInfo->company_name,
                config('const.quoteReport.jpn_key.company.zipcode') => substr($companyInfo->zipcode, 0, 3). '-'. substr($companyInfo->zipcode, 3, 4),
                config('const.quoteReport.jpn_key.company.address1') => $companyInfo->address1,
                config('const.quoteReport.jpn_key.company.address2') => $companyInfo->address2,
                config('const.quoteReport.jpn_key.company.tel') => $companyInfo->tel,
                config('const.quoteReport.jpn_key.company.fax') => $companyInfo->fax,
            ],
            // 基本情報
            config('const.quoteReport.jpn_key.basic.parentkey')=> [
                config('const.quoteReport.jpn_key.basic.quote_date') => $quoteVer->quote_create_date,
                config('const.quoteReport.jpn_key.basic.quote_no') => $quoteVer->quote_no. '-'. $quoteVer->quote_version,
                config('const.quoteReport.jpn_key.basic.customer_name') => $quoteVer->customer_name,
                config('const.quoteReport.jpn_key.basic.total_amount_tax') => $layers['total'][config('const.quoteConstructionInfo.parent_quote_detail_id')] + $taxAmount,
                config('const.quoteReport.jpn_key.basic.total_amount') => $layers['total'][config('const.quoteConstructionInfo.parent_quote_detail_id')],
                config('const.quoteReport.jpn_key.basic.tax_amount') => $taxAmount,
                config('const.quoteReport.jpn_key.basic.construction_subject') => $quoteVer->owner_name. "  ". $quoteVer->architecture_name,
                config('const.quoteReport.jpn_key.basic.construction_period') => $quoteVer->construction_period,
                config('const.quoteReport.jpn_key.basic.address') => $quoteVer->address1. $quoteVer->address2,
                config('const.quoteReport.jpn_key.basic.construction_outline') => $quoteVer->construction_outline,
                config('const.quoteReport.jpn_key.basic.payment_condition') => $quoteVer->payment_condition,
                config('const.quoteReport.jpn_key.basic.limit_date') => $quoteVer->quote_enabled_limit_date,
                config('const.quoteReport.jpn_key.basic.comment') => $quoteVer->customer_comment,
            ],
        );

        // 内訳
        $data[config('const.quoteReport.jpn_key.detail.parentkey')] = [];
        foreach ($detailData as  $value) {
            $data[config('const.quoteReport.jpn_key.detail.parentkey')][] = [
                // ActiveReportsJSが前後の半角スペースを省略するので全角スペース
                config('const.quoteReport.jpn_key.detail.product_info_r1') => $value['product_info_r1'],
                config('const.quoteReport.jpn_key.detail.product_info_r2') => $value['product_info_r2'],
                config('const.quoteReport.jpn_key.detail.product_name') => $value['product_name'],
                config('const.quoteReport.jpn_key.detail.model') => $value['model'],
                config('const.quoteReport.jpn_key.detail.unit') => $value['unit'],
                config('const.quoteReport.jpn_key.detail.quote_quantity') => $value['quote_quantity'],
                config('const.quoteReport.jpn_key.detail.sales_unit_price') => $value['sales_unit_price'],
                config('const.quoteReport.jpn_key.detail.sales_total') => $value['sales_total'],
                config('const.quoteReport.jpn_key.detail.regular_price') => $value['regular_price'],
                config('const.quoteReport.jpn_key.detail.memo') => $value['memo'],
            ];
        }

        return $data
        ;
    }

    /**
     * 印刷用にフォーマット
     *
     * @param [type] $value
     * @param boolean $isReal
     * @return void
     */
    private function formatRec($value, $quoteDetails, $isSummaryPage=false)
    {
        // ActiveReportsJSが半角スペースを勝手に省略するので基本的に全角スペース
        // nullやemptyを結合したらActiveReportsJSで半角スペースが印字されている為、三項演算子で分岐など 
        $data = [
            'quote_detail_id' => null,
            'construction_id' => ($isSummaryPage) ? self::SUMMARY_CONSTRUCTION_ID:$value->construction_id,
            'product_info_r1' => str_repeat('　', $value->depth),   
            'product_info_r2' => str_repeat('　', $value->depth),   
            'product_name' => $value->product_name,   
            'model' => $value->model,
            'unit' => $value->unit,
            'quote_quantity' => null,
            'sales_unit_price' => null,
            'sales_total' => (int)$value->sales_total,
            'regular_price' => (int)$value->regular_price,
            'memo' => $value->memo,
            'is_layer' => false,
        ];

        // 小計、合計行ではない場合の処理（金額隠しの処理が必要）
        if ($value->id) {
            $data['quote_detail_id'] = $value->id;
            // 数値系は文字になっているのでintキャストする　※見積書にカンマがつかない
            $data['quote_quantity'] = (float)$value->quote_quantity;
            $data['sales_unit_price'] = (int)$value->sales_unit_price;
            // 目隠し処理など
            if ($isSummaryPage) {
                // サマリページはただの明細という扱いで処理したいので、元データが階層行でも階層フラグをたてない
                $data['product_info_r1'] .= $value->product_name;
                $data['sales_unit_price'] = null;
            }else {
                if ($value->layer_flg) {
                    // 階層
                    $data['is_layer'] = true;
                    $data['product_info_r1'] .= $value->product_name;
                }else{
                    // 明細
                    $data['product_info_r1'] .= $value->product_name;
                    // 仕入先名
                    if (!empty($value->supplier_name)) {
                        $data['product_info_r2'] .= $value->supplier_name;
                    }
                    // 商品コード
                    if (!empty($value->product_code)) {
                        if (!empty($value->supplier_name) && !is_null($value->supplier_name)) {
                            $data['product_info_r2'] .= ' ／ ';
                        }
                        $data['product_info_r2'] .= $value->product_code;
                    }
                    // 型式・規格
                    if (!empty($value->model)) {
                        if (!empty($value->supplier_name) || !empty($value->product_code)) {
                            $data['product_info_r2'] .= ' ／ ';
                        }
                        $data['product_info_r2'] .= $value->model;
                    }
                }

                // 階層行 Or 売価印字フラグが立っていない
                if ($value->layer_flg || !$value->price_print_flg) {
                    $data['sales_unit_price'] = null;
                    $data['sales_total'] = null;
                }
            }
        }else{
            $data['product_info_r1'] .= $value->product_name;
        }

        // 親階層の売価印字フラグをチェック
        foreach (explode('_', $value->tree_path) as $pQuoteDetailId) {
            $quoteDetail = $quoteDetails->firstWhere('id', $pQuoteDetailId);
            // 存在する && 売価印字フラグがたっていない
            if ($quoteDetail && !$quoteDetail->price_print_flg) {
                $data['sales_unit_price'] = null;
                $data['sales_total'] = null;
                break;
            }
        }

        return $data;
    }

    /**
     * 改ページ処理
     *
     * @param [type] $data
     * @param [type] $layers
     * @return void
     */
    private function processPageBreak($data, $layers){
        $NUMBER_OF_LINES = config('const.quoteReport.number_of_lines');
        $EMPTY_RECORD = [
            'product_info_r1' => null, 'product_info_r2' => null, 'product_name' => null, 'model' => null,
            'unit' => null, 'quote_quantity' => null, 'regular_price' => null, 'sales_unit_price' => null, 'sales_total' => null, 'memo' => null,
        ];

        $printData = [];
        for ($i=0, $lineCnt=0; $i < count($data); $i++, $lineCnt++) {
            // 既定行数に達したらカウントリセット
            if ($NUMBER_OF_LINES == $lineCnt) { $lineCnt = 0; }

            // 明細の1行目は改ページしない
            if ($lineCnt != 0) {
                // 指定条件の場合、改ページとカウントリセットを行う
                if (
                    // 工事区分IDが前読込んだ工事区分IDと異なる
                    ($data[$i]['construction_id'] != $data[$i-1]['construction_id'])
                    // 階層行 && 明細数のオーバーフローを許容しない && 自階層の明細数(自階層小計含む)が見積書明細の残行数を上回っている 
                    || ($data[$i]['is_layer'] && !$layers['forgive_overflow'][$data[$i]['quote_detail_id']] && ($layers['cnt'][$data[$i]['quote_detail_id']] > ($NUMBER_OF_LINES-$lineCnt)))
                ){
                    // 改ページ処理
                    for ($j=0; $j < ($NUMBER_OF_LINES - $lineCnt); $j++) { 
                        $printData[] = $EMPTY_RECORD;
                    }
                    // カウントリセット
                    $lineCnt = 0;
                }
            }

            // 階層 & 許容フラグがたっていない
            if ($data[$i]['is_layer'] && !$layers['forgive_overflow'][$data[$i]['quote_detail_id']]) {
                for ($j=$i; $j < count($data); $j++) { 
                    // 明細行ならbreak
                    if (!$data[$j]['is_layer']) {
                        break;
                    }
                    // 階層に対して明細数のオーバーフローを許容するフラグをたてる
                    $layers['forgive_overflow'][$data[$j]['quote_detail_id']] = config('const.flg.on');
                }
            }
            $printData[] = $data[$i];
        }
        return $printData;
    }

}