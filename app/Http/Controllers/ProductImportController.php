<?php
namespace App\Http\Controllers;

use App\Libs\Common;
use Illuminate\Http\Request;
use Session;
use DB;
use Log;
use App\Models\Authority;
use App\Models\ClassBig;
use App\Models\ClassConv;
use Auth;
use App\Models\Product;
use App\Models\ClassMiddle;
use App\Models\ClassSmall;
use App\Models\Construction;
use App\Models\General;
use App\Models\Supplier;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductImportController extends Controller
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
        // 閲覧権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }
        
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        $Product = new Product();

        return view('Product.product-import')
                ;
    }
    
    /**
     * CSV取込
     *
     * @param Request $request
     * @return void
     */
    public function import(Request $request)
    {
        $rtnResult = false;
        $cntArray = [];
        $cntArray['target'] = 0;
        $cntArray['add'] = 0;
        $cntArray['update'] = 0;
        $cntArray['error'] = 0;
        $errMsg = '';

        // 権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }

        // リクエストデータ取得
        $params = $request->all();
        $formatKbn = $params['format_kbn'];

        // バリデーションチェック
        $this->fileValid($request);
        $Product = new Product();

        DB::beginTransaction();        
        try{
            $Product = new Product(); 
            $saveResult = false;

            // 文字コード変換 (SJIS → UTF-8)
            $file = $request->file('file');
            $sjis = file_get_contents($file);
            $utf8 = mb_convert_encoding($sjis, 'UTF-8', 'SJIS-win');
            file_put_contents('product.csv', $utf8);
            $csv = [];

            $row = 1;
            // ファイルが存在しているかチェックする
            if (($handle = fopen('product.csv', "r")) !== FALSE) {
                // 1行ずつfgetcsv()関数を使って読み込む
                while (($data = fgetcsv($handle))) {
                    // 最終行スキップ
                    if ($data === null) continue;
                    if ($row == 1) {
                        // ヘッダー
                        $errHeaderAry = $this->checkCsvHeader($data, $formatKbn);

                        $errCnt = count($errHeaderAry);
                        if ($errCnt > 0) {
                            $errMsg = config('message.error.product_csv_import.csv_not_match');
                            $errMsg .= implode(",", $errHeaderAry);
                            $cntArray['error'] = $errCnt;

                            return ['result' => $rtnResult,'cnt' => $cntArray, 'errorMessage' => $errMsg];
                        } 
                    }

                    // CSVファイルを配列化
                    $csv[] = $data;
                    $row++;
                }

                $csvObj = $this->convertCsvObject($csv, $formatKbn);
                // CSVの中身に対するバリデーションを実施
                $csv_errors = $this->validateCsvData($csvObj, $formatKbn);

                $errMsg .= implode("<br>", $csv_errors);
                $errIndex = [];
                foreach ($csv_errors as $msg) {
                    $errIndex[] = (int)substr($msg, 0, mb_strpos($msg, '行目', 0, 'UTF-8'));
                    $errIndex = array_unique($errIndex);
                }
                $cntArray['error'] = count($errIndex);

                // $saveList = $this->convertSaveObject($csvObj, $formatKbn);
                $cntArray['target'] = count($csvObj) - 1;

                foreach ($csvObj as $i => $record) {
                    if ($i == 0) {
                        // ヘッダーをスキップ
                        continue;
                    }
                    if (!in_array($i, $errIndex)){
                        // 登録/更新
                        $result = $Product->importCsv($record);

                        if ($result['result'] == true) {
                            if ($result['kbn'] == config('const.flg.off')) {
                                $cntArray['add']++;
                            } else {
                                $cntArray['update']++;
                            }
                        } else {
                            $cntArray['error']++;
                        }
                    }
                }
                
                fclose($handle);
            }

            $saveResult = true;
            
            if ($saveResult) {
                DB::commit();
                $rtnResult = true;
                // Session::flash('flash_success', config('message.success.save'));
            }
        } catch (\Exception $e) {
            DB::rollback();
            $rtnResult = false;
            Log::error($e);
            // Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json(['result' => $rtnResult, 'cnt' => $cntArray, 'errorMessage' => $errMsg]);
    }

    /**
     * 工事区分/大分類/中分類/工程/メーカーID 存在チェック
     *
     * @param  $record
     * @return boolean
     */
    protected function isExistIDs($record, $line_num = null) 
    {
        $Common = new Common();
        $rtnList = [];
        $rtnList['result'] = true;
        $rtnList['err'] = [];
        $pCode = $record['product_code'];

        $ClassBig = new ClassBig();
        $ClassMiddle = new ClassMiddle();
        $ClassSmall = new ClassSmall();
        $Construction = new Construction();

        $cBigList = $ClassBig->getComboList();
        $cMidList = $ClassMiddle->getComboList();
        $cSmallList = $ClassSmall->getComboList();
        $constList = $Construction->getComboList();
        // メーカーリスト取得
        $kbn = [];
        $kbn[] = config('const.supplierMakerKbn.direct');
        $kbn[] = config('const.supplierMakerKbn.maker');
        $makerList = (new Supplier())->getBySupplierKbn($kbn);
        
        // 分類チェック
        if (count($cBigList->where('id', $Common->nullorBlankToZero($record['class_big_id']))) == 0) {
            $rtnList['result'] = false;
            $rtnList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_big_id'). 'が'. config('message.error.product_csv_import.exist_error');
        };

        if (count($cMidList->where('id', $Common->nullorBlankToZero($record['class_middle_id']))) == 0) {
            $rtnList['result'] = false;
            $rtnList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_middle_id'). 'が'. config('message.error.product_csv_import.exist_error');
        }

        if (!empty($record['class_small_id_1'])) {
            if (count($cSmallList->where('id', $Common->nullorBlankToZero($record['class_small_id_1']))) == 0) {
                $rtnList['result'] = false;
                $rtnList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_small_id_1'). 'が'. config('message.error.product_csv_import.exist_error');
            }
        }

        if (!empty($record['class_small_id_2'])) {
            if (count($cSmallList->where('id', $Common->nullorBlankToZero($record['class_small_id_2']))) == 0) {
                $rtnList['result'] = false;
                $rtnList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_small_id_2'). 'が'. config('message.error.product_csv_import.exist_error');    
            }
        }
        if (!empty($record['class_small_id_3'])) {
            if (count($cSmallList->where('id', $Common->nullorBlankToZero($record['class_small_id_3']))) == 0) {
                $rtnList['result'] = false;
                $rtnList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_small_id_3'). 'が'. config('message.error.product_csv_import.exist_error');    
            }
        }

        if (count($constList->where('id', $Common->nullorBlankToZero($record['construction_id_1']))) == 0) {
            $rtnList['result'] = false;
            $rtnList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_1'). 'が'. config('message.error.product_csv_import.exist_error');    
        }
        if (!empty($record['construction_id_2'])) {
            if (count($constList->where('id', $Common->nullorBlankToZero($record['construction_id_2']))) == 0) {
                $rtnList['result'] = false;
                $rtnList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_2'). 'が'. config('message.error.product_csv_import.exist_error');        
            }
        }   
        if (!empty($record['construction_id_3'])) {
            if (count($constList->where('id', $Common->nullorBlankToZero($record['construction_id_3']))) == 0) {
                $rtnList['result'] = false;
                $rtnList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_3'). 'が'. config('message.error.product_csv_import.exist_error');        
            }
        }
        // 仕入先メーカー区分が「メーカー」「メーカー直接取引」で存在チェック
        // $makerList = $makerList->where('id', $Common->nullorBlankToZero($record['maker_id']));
        if (count($makerList->where('id', $Common->nullorBlankToZero($record['maker_id']))) == 0) {
            $rtnList['result'] = false;
            $rtnList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.maker_id'). 'が'. config('message.error.product_csv_import.exist_error');        
        }

        return $rtnList;
    }

    /**
     * 大分類IDから取得
     *
     * @param  $classBigId
     * @return boolean　True: 木材フォーマット　False: その他
     */
    protected function isWoodFormat($classBigId) 
    {
        $isWoodFormat = false;
        $ClassBig = new ClassBig();
        $classBigData = null;
        if (!empty($classBigId)) {
            $classBigData = $ClassBig->getProductClass();
            $classBigData = collect($classBigData)->where('class_big_id', $classBigId)->first();
        }
        // 木材かどうかチェック
        if ($classBigData['format_kbn'] == config('const.flg.on')) {
            $isWoodFormat = true;
        }
        return $isWoodFormat;
    }

     /**
     * 共通見積フォーマット 
     * "中分類"から大分類ID、中分類ID、工程ID、工事区分IDを変換
     *
     * @param  $middleName
     * @return boolean　
     */
    protected function convertClassByMiddleName($middleName)
    {
        $ClassConv = new ClassConv();
        $classIDs = null;
        $classIDs = $ClassConv->convertClassIdByMiddleName($middleName);        

        return $classIDs;
    }

    /**
     * CSVファイルをオブジェクト化
     *
     * @return $rtnAry 整形後の配列
     */
    private function convertCsvObject($file, $formatKbn) 
    {
        $rtnAry = [];
        $isWood = false;
        $leng = '';
        $thickness = '';
        $width = '';
        $Supplier = new Supplier();
        $ClassConv = new ClassConv();
        $General = new General();
        $woodData = $General->getCategoryList(config('const.general.wood'));
        $gradeData = $General->getCategoryList(config('const.general.grade'));

        // メーカーリスト取得
        $kbn = [];
        $kbn[] = config('const.supplierMakerKbn.direct');
        $kbn[] = config('const.supplierMakerKbn.maker');
        $makerList = $Supplier->getBySupplierKbn($kbn);

        $treeSpecObj = null;
        $gradeObj = null;
        $headerRow = [];
        foreach ($file as $i => $record) {            
            $str = implode($record);
            if (1 === count($record) || $str == '') {
                // 余分な空白がCSVの途中に混ざっている場合は無視
                continue;
            }
            if ($i == 0) {
                foreach ($record as $j => $c) {
                    $headerRow[] = $c;
                                            
                    switch($c) {
                        case config('const.productCsvHeader.productHeader.class_big_id'):
                            // 分類が木材フォーマットかどうか
                            $isWood = $this->isWoodFormat($record[$j]);
                            break;
                    }
                }
                // $headerRow = $record;
                $rtnAry[] = $headerRow;
                continue;
            }            
            if ($isWood) {
                switch ($formatKbn) {
                    case config('const.productCsvHeader.format_kbn.product.val'):
                        foreach ($record as $key => $col) {
                            // 木材フォーマットの場合、商品コード、商品番号を自動生成
                            switch($key) {
                                case config('const.productCsvHeader.productHeader.tree_species'):
                                    if (!is_null($record[$key])) {
                                        $treeSpecObj = collect($woodData)->where('value_code', $record[$key])->first();
                                    }
                                    break;
                                case config('const.productCsvHeader.productHeader.grade'):
                                    if (!is_null($record[$key])) {
                                        $gradeObj = collect($gradeData)->where('value_code', $record[$key])->first();
                                    }
                                    break;
                                case config('const.productCsvHeader.productHeader.length'):
                                    $leng = $record[$key];
                                    break;
                                case config('const.productCsvHeader.productHeader.thickness'):
                                    $thickness = $record[$key];
                                    break;
                                case config('const.productCsvHeader.productHeader.width'):
                                    $width = $record[$key];
                                    break;
                            }
                        }
                }
                break;
            }
            $pCode = '';
            $pName = '';
            foreach ($headerRow as $j => $col) {
                switch ($formatKbn) {
                    case config('const.productCsvHeader.format_kbn.product.val'):
                        // 商品マスタフォーマット
                        switch ($col) {
                            case config('const.productCsvHeader.productHeader.product_code'):
                                if (empty($record[$j]) && $isWood) {
                                    // 商品コード自動生成
                                    if (!empty($treeSpecObj)){
                                        $pCode .= $treeSpecObj['value_code'];
                                    }
                                    if (!empty($gradeObj)){
                                        $pCode .= $gradeObj['value_code'];
                                    }
                                    if (((int)$leng / 1000) >= 1) {
                                        // 長さのみ1000で割った数値にする。元の数値が1000以上なら小数点を除去
                                        $pCode .= str_replace('.', '', strval((int)$leng/1000));
                                    } else {
                                        $pCode .= strval((int)$leng/1000);
                                    }
                                    if (strval($thickness) == strval($width)) {
                                        // 厚み＝幅の場合、片方のみ使う
                                        $pCode .= $thickness;
                                    } else {
                                        $pCode .= $thickness. $width;
                                    }
                                    $rtnAry[$i]['product_code'] = $pCode;
                                } else {
                                    $rtnAry[$i]['product_code'] = $record[$j];
                                }
                                break;
                            case config('const.productCsvHeader.productHeader.product_name'):
                                if (empty($record[$j]) && $isWood) {
                                    // 商品名自動生成
                                    if (!empty($treeSpecObj)){
                                        // 樹種名
                                        $pName .= $treeSpecObj['value_text_1'];
                                    }
                                    if (!empty($gradeObj)){
                                        //　等級名
                                        $pName .= ' '. $gradeObj['value_text_1'];
                                    }
                                    $pName .= ' '. $leng. '×'. $thickness. '×'. $width;
                                    $rtnAry[$i]['product_name'] = $pName;
                                } else {
                                    $rtnAry[$i]['product_name'] = $record[$j];
                                }
                                break;
                            case config('const.productCsvHeader.productHeader.product_short_name'):
                                $rtnAry[$i]['product_short_name'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.class_big_id'):
                                $rtnAry[$i]['class_big_id'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.class_middle_id'):
                                $rtnAry[$i]['class_middle_id'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.construction_id_1'):
                                $rtnAry[$i]['construction_id_1'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.construction_id_2'):
                                $rtnAry[$i]['construction_id_2'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.construction_id_3'):
                                $rtnAry[$i]['construction_id_3'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.class_small_id_1'):
                                $rtnAry[$i]['class_small_id_1'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.class_small_id_2'):
                                $rtnAry[$i]['class_small_id_2'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.class_small_id_3'):
                                $rtnAry[$i]['class_small_id_3'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.set_kbn'):
                                if (empty($record[$j])) {
                                    $rtnAry[$i]['set_kbn'] = 'S';
                                }  else {
                                    $rtnAry[$i]['set_kbn'] = $record[$j];
                                }
                                break;
                            case config('const.productCsvHeader.productHeader.weight'):
                                $rtnAry[$i]['weight'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.maker_id'):
                                $rtnAry[$i]['maker_id'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.price'):
                                $rtnAry[$i]['price'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.open_price_flg'):
                                if (empty($record[$j]) || $record[$j][config('const.productCsvHeader.productHeader.price')] != 0) {
                                    $rtnAry[$i]['open_price_flg'] = config('const.flg.off');
                                } else {
                                    $rtnAry[$i]['open_price_flg'] = config('const.flg.on');
                                }
                                break;
                            case config('const.productCsvHeader.productHeader.min_quantity'):
                                $rtnAry[$i]['min_quantity'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.stock_unit'):
                                $rtnAry[$i]['stock_unit'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.quantity_per_case'):
                                $rtnAry[$i]['quantity_per_case'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.lead_time'):
                                $rtnAry[$i]['lead_time'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.order_lot_quantity'):
                                $rtnAry[$i]['order_lot_quantity'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.purchase_makeup_rate'):
                                $rtnAry[$i]['purchase_makeup_rate'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.normal_purchase_price'):
                                $rtnAry[$i]['normal_purchase_price'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.unit'):
                                $rtnAry[$i]['unit'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.sales_makeup_rate'):
                                $rtnAry[$i]['sales_makeup_rate'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.normal_sales_price'):
                                $rtnAry[$i]['normal_sales_price'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.normal_gross_profit_rate'):
                                $rtnAry[$i]['normal_gross_profit_rate'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.warranty_term'):
                                $rtnAry[$i]['warranty_term'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.housing_history_transfer_kbn'):
                                $rtnAry[$i]['housing_history_transfer_kbn'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.intangible_flg'):
                                $rtnAry[$i]['intangible_flg'] = $record[$j];
                                break;
                            // 木材                
                            case config('const.productCsvHeader.productHeader.tree_species'):
                                $rtnAry[$i]['tree_species'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.grade'):
                                $rtnAry[$i]['grade'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.length'):
                                $rtnAry[$i]['length'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.thickness'):
                                $rtnAry[$i]['thickness'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.width'):
                                $rtnAry[$i]['width'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.model'):
                                $rtnAry[$i]['model'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.start_date'):
                                $rtnAry[$i]['start_date'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.end_date'):
                                $rtnAry[$i]['end_date'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.new_product_id'):
                                $rtnAry[$i]['new_product_id'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.productHeader.memo'):
                                $rtnAry[$i]['memo'] = $record[$j];
                                break;
                            default:
                                unset($rtnAry[0][$j]);
                                break;
                        }
                        break;
                    case config('const.productCsvHeader.format_kbn.beeConnect.val'):
                        $rtnAry[$i]['min_quantity'] = 1;
                        $rtnAry[$i]['order_lot_quantity'] = 1;
                        $rtnAry[$i]['intangible_flg'] = config('const.flg.off');
                        // $rtnAry[$i]['open_price_flg'] = $record['open_price_flg'];
                        // 共通見積フォーマット
                        switch ($col) {
                            case config('const.productCsvHeader.beeConnectHeader.product_code'):
                                $rtnAry[$i]['product_code'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.beeConnectHeader.product_name'):
                                $rtnAry[$i]['product_name'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.beeConnectHeader.class_middle_id'):
                                if (!empty($record[$j])) {
                                    $middleName = $record[$j];
                                    $isExist = DB::table('m_class_conv')->where('middle_name', $middleName)->exists();
    
                                    if ($isExist) {
                                        $rtnAry[$i]['class_middle_id'] = $record[$j];
                                    } else {
                                        $rtnAry[$i]['class_middle_id'] = '';
                                    }
                                    $classIDs = $ClassConv->convertClassIdByMiddleName($middleName);

                                    $rtnAry[$i]['class_big_id'] = $classIDs['class_big_id'];
                                    $rtnAry[$i]['class_middle_id'] = $classIDs['class_middle_id'];
                                    $rtnAry[$i]['construction_id_1'] = $classIDs['construction_id'];
                                    $rtnAry[$i]['class_small_id_1'] = $classIDs['class_small_id_1'];
                                }
                                break;
                            case config('const.productCsvHeader.beeConnectHeader.set_kbn'):
                                if (empty($record[$j])) {
                                    $rtnAry[$i][$key] = 'S';
                                } else {
                                    $rtnAry[$i]['set_kbn'] = $record[$j];
                                }
                                break;
                            case config('const.productCsvHeader.beeConnectHeader.model'):
                                $rtnAry[$i]['model'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.beeConnectHeader.maker_id'):
                                if (!empty($record[$j])) {
                                    $makerName = $record[$j];
    
                                    $makerData = $makerList->where('supplier_name', $makerName)->first();
                                    if (!empty($makerData['supplier_name'])) {
                                        $rtnAry[$i]['maker_id'] = $makerData['id'];
                                    } else {
                                        $rtnAry[$i]['maker_id'] = $record[$j];
                                    }
                                } else {
                                    $rtnAry[$i]['maker_id'] = '';
                                }
                                break;
                            case config('const.productCsvHeader.beeConnectHeader.price'):
                                $rtnAry[$i]['price'] = $record[$j];
                                if ($record[$j] != 0) {
                                    $rtnAry[$i]['open_price_flg'] = config('const.flg.off');
                                } else {
                                    $rtnAry[$i]['open_price_flg'] = config('const.flg.on');
                                }
                                break;
                            case config('const.productCsvHeader.beeConnectHeader.quantity_per_case'):
                                $rtnAry[$i]['quantity_per_case'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.beeConnectHeader.unit'):
                                $rtnAry[$i]['unit'] = $record[$j];
                                break;
                            case config('const.productCsvHeader.beeConnectHeader.memo'):
                                $rtnAry[$i]['memo'] = $record[$j];
                                break;
                            default:
                                unset($rtnAry[0][$j]);
                                break;
                        }
                        break;  
                }
                
            }
        }        
        $rtnAry[0] = array_values($rtnAry[0]);

        return $rtnAry;
    }

    /**
     * CSVリストを保存用に整形
     *
     * @return $rtnAry 整形後の配列
     */
    // private function convertSaveObject($file, $formatKbn) 
    // {
    //     $rtnAry = [];
    //     $headerRow = [];
    //     $ClassConv = new ClassConv();
    //     foreach ($file as $i => $record) {
    //         if ($i == 0) {
    //             $headerRow = $record;
    //             continue;
    //         }
    //         if (1 === count($record)) {
    //             // 余分な空白がCSVの途中に混ざっている場合は無視
    //             continue;
    //         }
    //         switch ($formatKbn) {
    //             case config('const.productCsvHeader.format_kbn.product.val'):
    //                 foreach ($headerRow as $j => $col) {
    //                     switch ($col) {
    //                         case config('const.productCsvHeader.productHeader.product_code'):
    //                             $rtnAry[$i]['product_code'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.product_name'):
    //                             $rtnAry[$i]['product_name'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.product_short_name'):
    //                             $rtnAry[$i]['product_short_name'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.class_big_id'):
    //                             $rtnAry[$i]['class_big_id'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.class_middle_id'):
    //                             $rtnAry[$i]['class_middle_id'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.construction_id_1'):
    //                             $rtnAry[$i]['construction_id_1'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.construction_id_2'):
    //                             $rtnAry[$i]['construction_id_2'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.construction_id_3'):
    //                             $rtnAry[$i]['construction_id_3'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.class_small_id_1'):
    //                             $rtnAry[$i]['class_small_id_1'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.class_small_id_2'):
    //                             $rtnAry[$i]['class_small_id_2'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.class_small_id_3'):
    //                             $rtnAry[$i]['class_small_id_3'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.set_kbn'):
    //                             $rtnAry[$i]['set_kbn'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.weight'):
    //                             $rtnAry[$i]['weight'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.maker_id'):
    //                             $rtnAry[$i]['maker_id'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.price'):
    //                             $rtnAry[$i]['price'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.open_price_flg'):                                
    //                             $rtnAry[$i]['open_price_flg'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.min_quantity'):
    //                             $rtnAry[$i]['min_quantity'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.stock_unit'):
    //                             $rtnAry[$i]['stock_unit'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.quantity_per_case'):
    //                             $rtnAry[$i]['quantity_per_case'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.lead_time'):
    //                             $rtnAry[$i]['lead_time'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.order_lot_quantity'):
    //                             $rtnAry[$i]['order_lot_quantity'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.purchase_makeup_rate'):
    //                             $rtnAry[$i]['purchase_makeup_rate'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.normal_purchase_price'):
    //                             $rtnAry[$i]['normal_purchase_price'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.unit'):
    //                             $rtnAry[$i]['unit'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.sales_makeup_rate'):
    //                             $rtnAry[$i]['sales_makeup_rate'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.normal_sales_price'):
    //                             $rtnAry[$i]['normal_sales_price'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.normal_gross_profit_rate'):
    //                             $rtnAry[$i]['normal_gross_profit_rate'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.warranty_term'):
    //                             $rtnAry[$i]['warranty_term'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.housing_history_transfer_kbn'):
    //                             $rtnAry[$i]['housing_history_transfer_kbn'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.intangible_flg'):
    //                             $rtnAry[$i]['intangible_flg'] = $record[$col];
    //                             break;       
    //                         case config('const.productCsvHeader.productHeader.tree_species'):
    //                             $rtnAry[$i]['tree_species'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.grade'):
    //                             $rtnAry[$i]['grade'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.length'):
    //                             $rtnAry[$i]['length'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.thickness'):
    //                             $rtnAry[$i]['thickness'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.width'):
    //                             $rtnAry[$i]['width'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.model'):
    //                             $rtnAry[$i]['model'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.start_date'):
    //                             $rtnAry[$i]['start_date'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.end_date'):
    //                             $rtnAry[$i]['end_date'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.new_product_id'):
    //                             $rtnAry[$i]['new_product_id'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.memo'):
    //                             $rtnAry[$i]['memo'] = $record[$col];
    //                             break;
    //                         default:
    //                             break;
    //                     }
    //                 }
    //                 break;
    //             case config('const.productCsvHeader.format_kbn.beeConnect.val'):                    
    //                 $rtnAry[$i]['min_quantity'] = 1;
    //                 $rtnAry[$i]['order_lot_quantity'] = 1;
    //                 $rtnAry[$i]['intangible_flg'] = config('const.flg.off');
    //                 $rtnAry[$i]['open_price_flg'] = $record['open_price_flg'];
    //                 foreach ($headerRow as $j => $col) {
    //                     switch ($col) {
    //                         case config('const.productCsvHeader.beeConnectHeader.product_code'):
    //                             $rtnAry[$i]['product_code'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.beeConnectHeader.product_name'):
    //                             $rtnAry[$i]['product_name'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.beeConnectHeader.class_middle_id'):
    //                             $middleName = $record[$col];
    //                             $classIDs = $ClassConv->convertClassIdByMiddleName($middleName);

    //                             $rtnAry[$i]['class_big_id'] = $classIDs['class_big_id'];
    //                             $rtnAry[$i]['class_middle_id'] = $classIDs['class_middle_id'];
    //                             $rtnAry[$i]['construction_id_1'] = $classIDs['construction_id'];
    //                             $rtnAry[$i]['class_small_id_1'] = $classIDs['class_small_id_1'];
    //                             break;
    //                         case config('const.productCsvHeader.beeConnectHeader.set_kbn'):
    //                             $rtnAry[$i]['set_kbn'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.beeConnectHeader.maker_id'):
    //                             $rtnAry[$i]['maker_id'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.beeConnectHeader.price'):
    //                             $rtnAry[$i]['price'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.beeConnectHeader.model'):
    //                             $rtnAry[$i]['model'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.beeConnectHeader.quantity_per_case'):
    //                             $rtnAry[$i]['quantity_per_case'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.beeConnectHeader.memo'):
    //                             $rtnAry[$i]['memo'] = $record[$col];
    //                             break;
    //                         case config('const.productCsvHeader.beeConnectHeader.unit'):
    //                             $rtnAry[$i]['unit'] = $record[$col];
    //                             $rtnAry[$i]['stock_unit'] = $record[$col];
    //                             break;
    //                         default:
    //                             break;
    //                     }
    //                 }
    //                 break;
    //         }
    //     }
    //     return $rtnAry;
    // }

    /**
     * ブランク入力の整形
     *
     * @param mixed $row 整形する行
     * @return $rtnAry 整形後の行
     */
    // private function generateRowData($row, $formatKbn) 
    // {
    //     $rtnAry = [];
    //     $isWood = false;
    //     $treeSpec = '';
    //     $grade = '';
    //     $leng = '';
    //     $thickness = '';
    //     $width = '';
    //     $Supplier = new Supplier();
    //     $ClassConv = new ClassConv();
    //     $ClassBig = new ClassBig();
    //     $General = new General();
    //     $woodData = $General->getCategoryList(config('const.general.wood'));
    //     $gradeData = $General->getCategoryList(config('const.general.grade'));
    //     foreach ($row as $key => $col) {
    //         switch($key) {
    //             case config('const.productCsvHeader.productHeader.class_big_id'):
    //                 // 分類が木材フォーマットかどうか
    //                 $isWood = $this->isWoodFormat($row[$key]);
    //                 break;
    //         }
    //     }
    //     $treeSpecObj = null;
    //     $gradeObj = null;
    //     switch ($formatKbn) {
    //         case config('const.productCsvHeader.format_kbn.product.val'):
    //             if ($isWood) {
    //                 foreach ($row as $key => $col) {
    //                     // 木材フォーマットの場合、商品コード、商品番号を自動生成
    //                     switch($key) {
    //                         case config('const.productCsvHeader.productHeader.tree_species'):
    //                             if (!is_null($row[$key])) {
    //                                 $treeSpecObj = collect($woodData)->where('value_code', $row[$key])->first();
    //                             }
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.grade'):
    //                             if (!is_null($row[$key])) {
    //                                 $gradeObj = collect($gradeData)->where('value_code', $row[$key])->first();
    //                             }
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.length'):
    //                             $leng = $row[$key];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.thickness'):
    //                             $thickness = $row[$key];
    //                             break;
    //                         case config('const.productCsvHeader.productHeader.width'):
    //                             $width = $row[$key];
    //                             break;
    //                     }
    //                 }
    //             }
    //         break;
    //     }
    //     $pCode = '';
    //     $pName = '';
    //     foreach ($row as $key => $col) {
    //         switch ($formatKbn) {
    //             case config('const.productCsvHeader.format_kbn.product.val'):
    //                 switch ($key) {
    //                     case config('const.productCsvHeader.productHeader.product_name'):
    //                         if (empty($row[$key]) && $isWood) {
    //                             if (!empty($treeSpecObj)){
    //                                 // 樹種名
    //                                 $pName .= $treeSpecObj['value_text_1'];
    //                             }
    //                             if (!empty($gradeObj)){
    //                                 //　等級名
    //                                 $pName .= ' '. $gradeObj['value_text_1'];
    //                             }
    //                             $pName .= ' '. $leng. '×'. $thickness. '×'. $width;
    //                             $row[$key] = $pName;
    //                         } 
    //                         break;
    //                     case config('const.productCsvHeader.productHeader.product_code'):
    //                         if (empty($row[$key]) && $isWood) {
    //                             if (!empty($treeSpecObj)){
    //                                 $pCode .= $treeSpecObj['value_code'];
    //                             }
    //                             if (!empty($gradeObj)){
    //                                 $pCode .= $gradeObj['value_code'];
    //                             }
    //                             if (((int)$leng / 1000) >= 1) {
    //                                 // 長さのみ1000で割った数値にする。元の数値が1000以上なら小数点を除去
    //                                 $pCode .= str_replace('.', '', strval((int)$leng/1000));
    //                             } else {
    //                                 $pCode .= strval((int)$leng/1000);
    //                             }
    //                             if (strval($thickness) == strval($width)) {
    //                                 // 厚み＝幅の場合、片方のみ使う
    //                                 $pCode .= $thickness;
    //                             } else {
    //                                 $pCode .= $thickness. $width;
    //                             }
    //                             $row[$key] = $pCode;
    //                         } 
    //                         break;
    //                     case config('const.productCsvHeader.productHeader.open_price_flg'):
    //                         if ($row[config('const.productCsvHeader.productHeader.price')] != 0 || empty($row[$key])) {
    //                             $row[$key] = config('const.flg.off');
    //                         } else {
    //                             $row[$key] = config('const.flg.on');
    //                         }
    //                         break;
    //                     case config('const.productCsvHeader.productHeader.set_kbn'):
    //                         if (empty($row[$key])) {
    //                             $row[$key] = 'S';
    //                         } 
    //                         break;
    //                 }
    //                 break;
    //             case config('const.productCsvHeader.format_kbn.beeConnect.val'):
    //                 switch ($key) {
    //                     case config('const.productCsvHeader.beeConnectHeader.maker_id'):
    //                         if (!empty($row[config('const.productCsvHeader.beeConnectHeader.maker_id')])) {
    //                             $makerName = $row[config('const.productCsvHeader.beeConnectHeader.maker_id')];
    //                             // メーカーリスト取得
    //                             $kbn = [];
    //                             $kbn[] = config('const.supplierMakerKbn.direct');
    //                             $kbn[] = config('const.supplierMakerKbn.maker');
    //                             $makerList = $Supplier->getBySupplierKbn($kbn);

    //                             $makerData = $makerList->where('supplier_name', $makerName)->first();
    //                             if (!empty($makerData)) {
    //                                 $row[config('const.productCsvHeader.beeConnectHeader.maker_id')] = $makerData['id'];
    //                             }
    //                         }
    //                         break;
    //                     case config('const.productCsvHeader.beeConnectHeader.class_middle_id'):
    //                         if (!empty($row[config('const.productCsvHeader.beeConnectHeader.class_middle_id')])) {
    //                             $middleName = $row[config('const.productCsvHeader.beeConnectHeader.class_middle_id')];
    //                             $isExist = DB::table('m_class_conv')->where('middle_name', $middleName)->exists();

    //                             if ($isExist) {
    //                                 $row[config('const.productCsvHeader.beeConnectHeader.class_middle_id')] = $row[config('const.productCsvHeader.beeConnectHeader.class_middle_id')];
    //                             } else {
    //                                 $row[config('const.productCsvHeader.beeConnectHeader.class_middle_id')] = '';
    //                             }
    //                         }
    //                         break;
    //                     case config('const.productCsvHeader.beeConnectHeader.price'):
    //                         if ($row[config('const.productCsvHeader.beeConnectHeader.price')] != 0) {
    //                             $row['open_price_flg'] = config('const.flg.off');
    //                         } else {
    //                             $row['open_price_flg'] = config('const.flg.on');
    //                         }
    //                         break;
    //                     case config('const.productCsvHeader.beeConnectHeader.set_kbn'):
    //                         if (empty($row[$key])) {
    //                             $row[$key] = 'S';
    //                         } 
    //                         break;
    //                 }
    //                 break;
    //         }
    //     }

    //     return $row;
    // }


    /**
     * CSVファイル内のバリデーション
     *
     * @param $file readしたCSVファイル
     *
     * @return $csv_errors エラーメッセージの配列
     */
    public function validateCsvData($file, $formatKbn)
    {
        $Common = new Common();
        $rtnList = [];
        $rtnList['result'] = true;
        $rtnList['err'] = [];

        $ClassBig = new ClassBig();
        $ClassMiddle = new ClassMiddle();
        $ClassSmall = new ClassSmall();
        $Construction = new Construction();

        $cBigList = $ClassBig->getComboList();
        $cMidList = $ClassMiddle->getComboList();
        $cSmallList = $ClassSmall->getComboList();
        $constList = $Construction->getComboList();
        // メーカーリスト取得
        $kbn = [];
        $kbn[] = config('const.supplierMakerKbn.direct');
        $kbn[] = config('const.supplierMakerKbn.maker');
        $makerList = (new Supplier())->getBySupplierKbn($kbn);        
        // バリデーションルール生成
        $csv_errors = [];
        $csv_success_list = [];
        // CSVファイル内のバリデーション
        foreach ($file as $line_num => $line) {
            $errList = [];
            $errList['result'] = true;
            $errList['err'] = [];
            if (0 === $line_num || 1 === count($line)) {
                // 最初の行または空行など余分な空白がCSVの途中に混ざっている場合は無視
                continue;
            }
            $rules = $this->makeCsvValidationRules($line, $formatKbn);
            // 入力値バリデーション
            $validator = Validator::make($line, $rules, $this->makeCsvValidationMessages($file, $line_num, $formatKbn));

            $pCode = $line['product_code'];
            // $rtnResult = $this->isExistIDs($line, $line_num);
            // 分類チェック
            if (count($cBigList->where('id', $Common->nullorBlankToZero($line['class_big_id']))) == 0) {
                $errList['result'] = false;
                $errList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_big_id'). 'が'. config('message.error.product_csv_import.exist_error');
            };

            if (count($cMidList->where('id', $Common->nullorBlankToZero($line['class_middle_id']))) == 0) {
                $errList['result'] = false;
                $errList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_middle_id'). 'が'. config('message.error.product_csv_import.exist_error');
            }

            if (!empty($line['class_small_id_1'])) {
                if (count($cSmallList->where('id', $Common->nullorBlankToZero($line['class_small_id_1']))) == 0) {
                    $errList['result'] = false;
                    $errList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_small_id_1'). 'が'. config('message.error.product_csv_import.exist_error');
                }
            }

            if (!empty($line['class_small_id_2'])) {
                if (count($cSmallList->where('id', $Common->nullorBlankToZero($line['class_small_id_2']))) == 0) {
                    $errList['result'] = false;
                    $errList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_small_id_2'). 'が'. config('message.error.product_csv_import.exist_error');    
                }
            }
            if (!empty($line['class_small_id_3'])) {
                if (count($cSmallList->where('id', $Common->nullorBlankToZero($line['class_small_id_3']))) == 0) {
                    $errList['result'] = false;
                    $errList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_small_id_3'). 'が'. config('message.error.product_csv_import.exist_error');    
                }
            }

            if (count($constList->where('id', $Common->nullorBlankToZero($line['construction_id_1']))) == 0) {
                $errList['result'] = false;
                $errList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_1'). 'が'. config('message.error.product_csv_import.exist_error');    
            }
            if (!empty($line['construction_id_2'])) {
                if (count($constList->where('id', $Common->nullorBlankToZero($line['construction_id_2']))) == 0) {
                    $errList['result'] = false;
                    $errList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_2'). 'が'. config('message.error.product_csv_import.exist_error');        
                }
            }   
            if (!empty($line['construction_id_3'])) {
                if (count($constList->where('id', $Common->nullorBlankToZero($line['construction_id_3']))) == 0) {
                    $errList['result'] = false;
                    $errList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_3'). 'が'. config('message.error.product_csv_import.exist_error');        
                }
            }
            // 仕入先メーカー区分が「メーカー」「メーカー直接取引」で存在チェック
            // $makerList = $makerList->where('id', $Common->nullorBlankToZero($record['maker_id']));
            if (count($makerList->where('id', $Common->nullorBlankToZero($line['maker_id']))) == 0) {
                $errList['result'] = false;
                $errList['err'][] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.maker_id'). 'が'. config('message.error.product_csv_import.exist_error');        
            }


            if (count($errList['err']) > 0) {
                $csv_errors = array_merge($csv_errors, $errList['err']);
            }

            if ($validator->fails()) {
                $csv_errors = array_merge($csv_errors, $validator->errors()->all());
                continue;
            }
        }
        return $csv_errors;
    }

    /**
     * CSVのヘッダー項目チェック
     * 
     * @param  $headerRow CSVファイルのヘッダー項目 
     * @return $errHeader 不足項目 AND 重複項目
     */
    private function checkCsvHeader(array $headerRow, $formatKbn)
    {
        $errHeader = [];
        $tmpHeader = [];
        switch ($formatKbn) {
            case config('const.productCsvHeader.format_kbn.product.val'):
                // 商品マスタフォーマット
                $tmpHeader = config('const.productCsvHeader.productHeader');
                break;
            case config('const.productCsvHeader.format_kbn.beeConnect.val'):
                // 共通見積フォーマット
                $tmpHeader = config('const.productCsvHeader.beeConnectHeader');
                break;
        }        
        // 存在チェック
        foreach ($tmpHeader as $col) {
            $isExist = in_array($col, $headerRow);
            if (!$isExist) {
                $errHeader[] = $col;
            }
        }

        // 各値の出現回数抽出
        $array_value = array_count_values($headerRow);
        $num = count($headerRow);

        for($i=0; $i < $num; ++$i){
            $key = $headerRow[$i];
            
            // 出現回数を格納
            $count = $array_value[$key];

            if($count > 1){
                // 重複カラム格納
                if (!in_array($key, $errHeader)) {
                    $errHeader[] = $key;
                }
            }
        }

        return $errHeader;
    }

    /**
     * CSVファイル内のバリデーションルール作成
     *
     * @return $rules バリデーションルールの配列
     */
    private function makeCsvValidationRules($line, $formatKbn)
    {
        $rules = [];
        $Common = new Common();
        // $today = date("Y/m/d");
        
        switch ($formatKbn) {
            case config('const.productCsvHeader.format_kbn.product.val'):
                $isWood = $this->isWoodFormat($Common->nullorBlankToZero($line['class_big_id']));

                $rules['product_code'] = 'required|max:255';
                $rules['product_name'] = 'required';
                $rules['product_short_name'] = 'max:30';
                $rules['class_big_id'] = 'required|integer';
                $rules['class_middle_id'] = 'nullable|integer';
                $rules['construction_id_1'] = 'required|integer';
                $rules['construction_id_2'] = 'nullable|integer';
                $rules['construction_id_3'] = 'nullable|integer';
                $rules['class_small_id_1'] = 'nullable|integer';
                $rules['class_small_id_2'] = 'nullable|integer';
                $rules['class_small_id_3'] = 'nullable|integer';
                $rules['weight'] = 'nullable|numeric';
                $rules['maker_id'] = 'required|integer';
                $rules['price'] = 'required|integer';
                $rules['open_price_flg'] = 'nullable|integer|between:0,1';
                $rules['min_quantity'] = 'required|numeric|between:0.00,9999999.99';
                $rules['stock_unit'] = 'max:30';
                $rules['quantity_per_case'] = 'numeric|nullable|between:0.00,9999999.99';
                $rules['lead_time'] = 'integer|nullable';
                $rules['order_lot_quantity'] = 'nullable|numeric|between:0.00,9999999.99';
                $rules['purchase_makeup_rate'] = 'numeric|required|between:0.00,100.00';
                $rules['normal_purchase_price'] = 'numeric|required|between:0,999999999';
                $rules['unit'] = 'required|max:30';
                $rules['sales_makeup_rate'] = 'numeric|nullable|between:0.00,100.00';
                $rules['normal_sales_price'] = 'numeric|nullable|between:0,999999999';
                $rules['normal_gross_profit_rate'] = 'numeric|nullable|between:0.00,100.00';
                $rules['start_date'] = 'nullable|date';
                $rules['end_date'] = 'nullable|date|after:'.config('const.productCsvHeader.productHeader.start_date');
                $rules['warranty_term'] = 'nullable|numeric';
                $rules['housing_history_transfer_kbn'] = 'nullable|integer|between:0,1';
                $rules['intangible_flg'] = 'required|integer|between:0,1';
                if ($isWood) {
                    $rules['tree_species'] = 'required';
                    $rules['grade'] = 'required';
                    $rules['length'] = 'required|integer|digits_between:0,10|between:0,2147483647';
                    $rules['thickness'] = 'required|integer|digits_between:0,10|between:0,2147483647';
                    $rules['width'] = 'required|integer|digits_between:0,10|between:0,2147483647';
                }
                // foreach (config('const.productCsvHeader.productHeader') as $col) {                    
                //     switch ($col) {
                //         case config('const.productCsvHeader.productHeader.product_code'):
                //             $rules['product_code'] = 'required|max:255';
                //             break;
                //         case config('const.productCsvHeader.productHeader.product_name'):
                //             $rules['product_name'] = 'required';
                //             break;
                //         case config('const.productCsvHeader.productHeader.product_short_name'):
                //             $rules['product_short_name'] = 'max:30';
                //             break;
                //         case config('const.productCsvHeader.productHeader.class_big_id'):
                //             $rules['class_big_id'] = 'required|integer';
                //             break;
                //         case config('const.productCsvHeader.productHeader.class_middle_id'):
                //             $rules['class_middle_id'] = 'nullable|integer';
                //             break;
                //         case config('const.productCsvHeader.productHeader.construction_id_1'):
                //             $rules['construction_id_1'] = 'required|integer';
                //             break;
                //         case config('const.productCsvHeader.productHeader.construction_id_2'):
                //             $rules['construction_id_2'] = 'nullable|integer';
                //             break;
                //         case config('const.productCsvHeader.productHeader.construction_id_3'):
                //             $rules['construction_id_3'] = 'nullable|integer';
                //             break;
                //         case config('const.productCsvHeader.productHeader.class_small_id_1'):
                //             $rules['class_small_id_1'] = 'nullable|integer';
                //             break;
                //         case config('const.productCsvHeader.productHeader.class_small_id_2'):
                //             $rules['class_small_id_2'] = 'nullable|integer';
                //             break;
                //         case config('const.productCsvHeader.productHeader.class_small_id_3'):
                //             $rules['class_small_id_3'] = 'nullable|integer';
                //             break;
                //         // case config('const.productCsvHeader.productHeader.set_kbn'):
                //         //     $rules[$col] = 'required';
                //         //     break;
                //         case config('const.productCsvHeader.productHeader.weight'):
                //             $rules['weight'] = 'nullable|numeric';
                //             break;
                //         case config('const.productCsvHeader.productHeader.maker_id'):
                //             $rules['maker_id'] = 'required|integer';
                //             break;
                //         case config('const.productCsvHeader.productHeader.price'):
                //             $rules['price'] = 'required|integer';
                //             break;
                //         case config('const.productCsvHeader.productHeader.open_price_flg'):
                //             $rules['open_price_flg'] = 'nullable|integer|between:0,1';
                //             break;
                //         case config('const.productCsvHeader.productHeader.min_quantity'):
                //             $rules['min_quantity'] = 'required|numeric|between:0.00,9999999.99';
                //             break;
                //         case config('const.productCsvHeader.productHeader.stock_unit'):
                //             $rules['stock_unit'] = 'max:30';
                //             break;
                //         case config('const.productCsvHeader.productHeader.quantity_per_case'):
                //             $rules['quantity_per_case'] = 'numeric|nullable|between:0.00,9999999.99';
                //             break;
                //         case config('const.productCsvHeader.productHeader.lead_time'):
                //             $rules['lead_time'] = 'integer|nullable';
                //             break;
                //         case config('const.productCsvHeader.productHeader.order_lot_quantity'):
                //             $rules['order_lot_quantity'] = 'nullable|numeric|between:0.00,9999999.99';
                //             break;
                //         case config('const.productCsvHeader.productHeader.purchase_makeup_rate'):
                //             $rules['purchase_makeup_rate'] = 'numeric|required|between:0.00,100.00';
                //             break;
                //         case config('const.productCsvHeader.productHeader.normal_purchase_price'):
                //             $rules['normal_purchase_price'] = 'numeric|required|between:0,999999999';
                //             break;
                //         case config('const.productCsvHeader.productHeader.unit'):
                //             $rules['unit'] = 'required|max:30';
                //             break;
                //         case config('const.productCsvHeader.productHeader.sales_makeup_rate'):
                //             $rules['sales_makeup_rate'] = 'numeric|nullable|between:0.00,100.00';
                //             break;
                //         case config('const.productCsvHeader.productHeader.normal_sales_price'):
                //             $rules['normal_sales_price'] = 'numeric|nullable|between:0,999999999';
                //             break;
                //         case config('const.productCsvHeader.productHeader.normal_gross_profit_rate'):
                //             $rules['normal_gross_profit_rate'] = 'numeric|nullable|between:0.00,100.00';
                //             break;
                //         case config('const.productCsvHeader.productHeader.start_date'):
                //             $rules['start_date'] = 'nullable|date';
                //             break;
                //         case config('const.productCsvHeader.productHeader.end_date'):
                //             $rules['end_date'] = 'nullable|date|after:'.config('const.productCsvHeader.productHeader.start_date');
                //             break;
                //         case config('const.productCsvHeader.productHeader.warranty_term'):
                //             $rules['warranty_term'] = 'nullable|numeric';
                //             break;
                //         case config('const.productCsvHeader.productHeader.housing_history_transfer_kbn'):
                //             $rules['housing_history_transfer_kbn'] = 'nullable|integer|between:0,1';
                //             break;
                //         case config('const.productCsvHeader.productHeader.intangible_flg'):
                //             $rules['intangible_flg'] = 'required|integer|between:0,1';
                //             break;
                //         // 木材      
                //         case config('const.productCsvHeader.productHeader.tree_species'):
                //             if ($isWood){
                //                 $rules['tree_species'] = 'required';
                //             }
                //             break;
                //         case config('const.productCsvHeader.productHeader.grade'):
                //             if ($isWood){
                //                 $rules['grade'] = 'required';
                //             }
                //             break;
                //         case config('const.productCsvHeader.productHeader.length'):
                //             if ($isWood){
                //                 $rules['length'] = 'required|integer|digits_between:0,10|between:0,2147483647';
                //             }
                //             break;
                //         case config('const.productCsvHeader.productHeader.thickness'):
                //             if ($isWood){
                //                 $rules['thickness'] = 'required|integer|digits_between:0,10|between:0,2147483647';
                //             }    
                //         break;
                //         case config('const.productCsvHeader.productHeader.width'):
                //             if ($isWood){
                //                 $rules['width'] = 'required|integer|digits_between:0,10|between:0,2147483647';
                //             }
                //             break;
                //         default:
                //             break;
                //     }
                // }
                // break;
            case config('const.productCsvHeader.format_kbn.beeConnect.val'):
                $rules['class_big_id'] = 'required';
                $rules['product_code'] = 'required|max:255';
                $rules['product_name'] = 'required';
                $rules['class_middle_id'] = 'required';
                $rules['set_kbn'] = 'required';
                $rules['model'] = 'max:255';
                $rules['maker_id'] = 'required';
                $rules['price'] = 'required|integer';
                $rules['quantity_per_case'] = 'numeric|nullable|between:0.00,9999999.99';
                $rules['unit'] = 'max:30';
                
                // foreach (config('const.productCsvHeader.beeConnectHeader') as $col) {        
                //     switch ($col) {
                //         case config('const.productCsvHeader.beeConnectHeader.product_code'):
                //             $rules['product_code'] = 'required|max:255';
                //             break;
                //         case config('const.productCsvHeader.beeConnectHeader.product_name'):
                //             $rules['product_name'] = 'required';
                //             break;
                //         case config('const.productCsvHeader.beeConnectHeader.class_middle_id'):
                //             $rules['class_middle_id'] = 'required';
                //             break;
                //         case config('const.productCsvHeader.beeConnectHeader.set_kbn'):
                //             $rules['set_kbn'] = 'required';
                //             break;
                //         case config('const.productCsvHeader.beeConnectHeader.model'):
                //             $rules['model'] = 'max:255';
                //             break;
                //         case config('const.productCsvHeader.beeConnectHeader.maker_id'):
                //             $rules['maker_id'] = 'required';
                //             break;
                //         case config('const.productCsvHeader.beeConnectHeader.price'):
                //             $rules['price'] = 'required|integer';
                //             break;
                //         case config('const.productCsvHeader.beeConnectHeader.quantity_per_case'):
                //             $rules['quantity_per_case'] = 'numeric|nullable|between:0.00,9999999.99';
                //             break;
                //         case config('const.productCsvHeader.beeConnectHeader.unit'):
                //             $rules['unit'] = 'max:30';
                //             break;
                //         default:
                //             break;
                //     }   
                
                // }
                break;
        }
        return $rules;
    }

    /**
     * CSVファイル内のバリデーションメッセージ作成
     *
     * @param $csv CSVファイル
     * @param $line_num CSVファイルの該当行
     *
     * @return $messages バリデーションメッセージの配列
     */
    protected function makeCsvValidationMessages($csv, $line_num, $formatKbn)
    {
        $messages = [];
        switch ($formatKbn) {
            case config('const.productCsvHeader.format_kbn.product.val'):
                $pCode = $csv[$line_num]['product_code'];

                $messages['product_code.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.product_code').'は必須です。';
                $messages['product_code.max'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.product_code').'は:max文字以内で入力してください';
                $messages['product_name.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.product_name').'は必須です。';
                $messages['product_short_name.max'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.product_name').'は:max文字以内で入力してください';
                $messages['class_big_id.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_big_id').'は必須です。';
                $messages['class_big_id.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_big_id').'は整数を指定してください。';
                $messages['class_middle_id.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_big_id').'は整数を指定してください。';
                $messages['construction_id_1.required'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_1').'は必須です。';
                $messages['construction_id_1.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_1').'は整数を指定してください。';
                $messages['construction_id_2.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_2').'は整数を指定してください。';
                $messages['construction_id_3.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_3').'は整数を指定してください。';
                $messages['class_small_id_1.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_small_id_1').'は整数を指定してください。';
                $messages['class_small_id_2.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_small_id_2').'は整数を指定してください。';
                $messages['class_small_id_3.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_small_id_3').'は整数を指定してください。';
                $messages['weight.numeric'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.weight').'は数値を指定してください。';
                $messages['maker_id.required'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.maker_id').'は必須です。';
                $messages['maker_id.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.maker_id').'は整数を指定してください。';
                $messages['price.required'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.price').'は必須です。';
                $messages['price.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.price').'は整数を指定してください。';
                $messages['open_price_flg.between'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.open_price_flg').'は:min〜:maxまでの数値を指定してください。';
                $messages['open_price_flg.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.open_price_flg').'は整数を指定してください。';
                $messages['min_quantity.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.min_quantity').'は数値を指定してください。';
                $messages['min_quantity.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.min_quantity').'は:min〜:maxまでの数値を指定してください。';
                $messages['min_quantity.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.min_quantity').'は必須です。';
                $messages['stock_unit.max'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.stock_unit').':max文字以内で入力してください。';
                $messages['quantity_per_case.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.quantity_per_case').'は数値を指定してください。';
                $messages['quantity_per_case.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.quantity_per_case').'は:min〜:maxまでの数値を指定してください。';
                $messages['lead_time.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.lead_time').'は整数を指定してください。';
                $messages['order_lot_quantity.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.order_lot_quantity').'は数値を指定してください。';
                $messages['order_lot_quantity.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.order_lot_quantity').'は:min〜:maxまでの数値を指定してください。';
                $messages['purchase_makeup_rate.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.purchase_makeup_rate').'は必須です。';
                $messages['purchase_makeup_rate.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.purchase_makeup_rate').'は数値を指定してください。';
                $messages['purchase_makeup_rate.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.purchase_makeup_rate').'は:min〜:maxまでの数値を指定してください。';
                $messages['normal_purchase_price.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.normal_purchase_price').'は数値を指定してください。';
                $messages['normal_purchase_price.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.normal_purchase_price').'は:min〜:maxまでの数値を指定してください。';
                $messages['unit.max'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.unit').':max文字以内で入力してください。';
                $messages['unit.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.unit').'は必須です。';
                $messages['sales_makeup_rate.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.sales_makeup_rate').'は数値を指定してください。';
                $messages['sales_makeup_rate.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.sales_makeup_rate').'は:min〜:maxまでの数値を指定してください。';
                $messages['normal_sales_price.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.normal_sales_price').'は:min〜:maxまでの数値を指定してください。';
                $messages['normal_sales_price.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.normal_sales_price').'は数値を指定してください。';
                $messages['normal_gross_profit_rate.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.normal_gross_profit_rate').'は:min〜:maxまでの数値を指定してください。';
                $messages['normal_gross_profit_rate.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.normal_gross_profit_rate').'は数値を指定してください。';
                $messages['start_date.date'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.start_date').'は正しい形式の日付を指定してください。';
                $messages['start_date.after_or_equal'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.start_date').'は今日以降の日付を指定してください。';
                $messages['end_date.date'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.end_date').'は正しい形式の日付を指定してください。';
                $messages['end_date.after'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.end_date').'は:date以降の日付を指定してください。';
                $messages['warranty_term.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.warranty_term').'は数値を指定してください。';
                $messages['housing_history_transfer_kbn.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.housing_history_transfer_kbn').'は整数を指定してください。';
                $messages['housing_history_transfer_kbn.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.housing_history_transfer_kbn').'は:min〜:maxまでの数値を指定してください。';
                $messages['intangible_flg.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.intangible_flg').'は整数を指定してください。';
                $messages['intangible_flg.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.intangible_flg').'は:min〜:maxまでの数値を指定してください。';
                $messages['intangible_flg.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.intangible_flg').'は必須です。';
                $messages['tree_species.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.tree_species').'は必須です。';
                $messages['grade.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.grade').'は必須です。';
                $messages['length.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.length').'は必須です。';
                $messages['length.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.length').'は:min〜:maxまでの数値を指定してください。';
                $messages['thickness.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.thickness').'は必須です。';
                $messages['thickness.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.thickness').'は:min〜:maxまでの数値を指定してください。';
                $messages['width.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.width').'は必須です。';
                $messages['width.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.width').'は:min〜:maxまでの数値を指定してください。';


                // foreach (config('const.productCsvHeader.productHeader') as $col) {
                //     switch ($col) {
                //         case config('const.productCsvHeader.productHeader.product_code'):
                //             $messages['product_code.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.product_code').'は必須です。';
                //             $messages['product_code.max'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.product_code').'は:max文字以内で入力してください';
                //             break;
                //         case config('const.productCsvHeader.productHeader.product_name'):
                //             $messages['product_name.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.product_name').'は必須です。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.product_short_name'):
                //             $messages['product_short_name.max'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.product_name').'は:max文字以内で入力してください';
                //             break;
                //         case config('const.productCsvHeader.productHeader.class_big_id'):
                //             $messages['class_big_id.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_big_id').'は必須です。';
                //             $messages['class_big_id.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_big_id').'は整数を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.class_middle_id'):
                //             $messages['class_middle_id.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_big_id').'は整数を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.construction_id_1'):
                //             $messages['construction_id_1.required'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_1').'は必須です。';
                //             $messages['construction_id_1.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_1').'は整数を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.construction_id_2'):
                //             $messages['construction_id_2.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_2').'は整数を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.construction_id_3'):
                //             $messages['construction_id_3.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.construction_id_3').'は整数を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.class_small_id_1'):
                //             $messages['class_small_id_1.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_small_id_1').'は整数を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.class_small_id_2'):
                //             $messages['class_small_id_2.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_small_id_2').'は整数を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.class_small_id_3'):
                //             $messages['class_small_id_3.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.class_small_id_3').'は整数を指定してください。';
                //             break;
                //         // case config('const.productCsvHeader.productHeader.set_kbn'):
                //         //     $messages[$col.'.required'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.set_kbn').'は必須です。';
                //         //     break;
                //         case config('const.productCsvHeader.productHeader.weight'):
                //             $messages['weight.numeric'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.weight').'は数値を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.maker_id'):
                //             $messages['maker_id.required'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.maker_id').'は必須です。';
                //             $messages['maker_id.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.maker_id').'は整数を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.price'):
                //             $messages['price.required'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.price').'は必須です。';
                //             $messages['price.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.price').'は整数を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.open_price_flg'):
                //             $messages['open_price_flg.between'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.open_price_flg').'は:min〜:maxまでの数値を指定してください。';
                //             $messages['open_price_flg.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.open_price_flg').'は整数を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.min_quantity'):
                //             $messages['min_quantity.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.min_quantity').'は数値を指定してください。';
                //             $messages['min_quantity.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.min_quantity').'は:min〜:maxまでの数値を指定してください。';
                //             $messages['min_quantity.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.min_quantity').'は必須です。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.stock_unit'):
                //             $messages['stock_unit.max'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.stock_unit').':max文字以内で入力してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.quantity_per_case'):
                //             $messages['quantity_per_case.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.quantity_per_case').'は数値を指定してください。';
                //             $messages['quantity_per_case.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.quantity_per_case').'は:min〜:maxまでの数値を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.lead_time'):
                //             $messages['lead_time.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.lead_time').'は整数を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.order_lot_quantity'):
                //             $messages['order_lot_quantity.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.order_lot_quantity').'は数値を指定してください。';
                //             $messages['order_lot_quantity.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.order_lot_quantity').'は:min〜:maxまでの数値を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.purchase_makeup_rate'):
                //             $messages['purchase_makeup_rate.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.purchase_makeup_rate').'は必須です。';
                //             $messages['purchase_makeup_rate.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.purchase_makeup_rate').'は数値を指定してください。';
                //             $messages['purchase_makeup_rate.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.purchase_makeup_rate').'は:min〜:maxまでの数値を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.normal_purchase_price'):
                //             $messages['normal_purchase_price.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.normal_purchase_price').'は数値を指定してください。';
                //             $messages['normal_purchase_price.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.normal_purchase_price').'は:min〜:maxまでの数値を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.unit'):
                //             $messages['unit.max'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.unit').':max文字以内で入力してください。';
                //             $messages['unit.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.unit').'は必須です。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.sales_makeup_rate'):
                //             $messages['sales_makeup_rate.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.sales_makeup_rate').'は数値を指定してください。';
                //             $messages['sales_makeup_rate.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.sales_makeup_rate').'は:min〜:maxまでの数値を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.normal_sales_price'):
                //             $messages['normal_sales_price.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.normal_sales_price').'は:min〜:maxまでの数値を指定してください。';
                //             $messages['normal_sales_price.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.normal_sales_price').'は数値を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.normal_gross_profit_rate'):
                //             $messages['normal_gross_profit_rate.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.normal_gross_profit_rate').'は:min〜:maxまでの数値を指定してください。';
                //             $messages['normal_gross_profit_rate.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.normal_gross_profit_rate').'は数値を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.start_date'):
                //             $messages['start_date.date'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.start_date').'は正しい形式の日付を指定してください。';
                //             $messages['start_date.after_or_equal'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.start_date').'は今日以降の日付を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.end_date'):
                //             $messages['end_date.date'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.end_date').'は正しい形式の日付を指定してください。';
                //             $messages['end_date.after'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.end_date').'は:date以降の日付を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.warranty_term'):
                //             $messages['warranty_term.numeric'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.warranty_term').'は数値を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.housing_history_transfer_kbn'):
                //             $messages['housing_history_transfer_kbn.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.housing_history_transfer_kbn').'は整数を指定してください。';
                //             $messages['housing_history_transfer_kbn.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.housing_history_transfer_kbn').'は:min〜:maxまでの数値を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.intangible_flg'):
                //             $messages['intangible_flg.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.intangible_flg').'は整数を指定してください。';
                //             $messages['intangible_flg.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.intangible_flg').'は:min〜:maxまでの数値を指定してください。';
                //             $messages['intangible_flg.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.intangible_flg').'は必須です。';
                //             break;
                //         // 木材      
                //         case config('const.productCsvHeader.productHeader.tree_species'):
                //             $messages['tree_species.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.tree_species').'は必須です。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.grade'):
                //             $messages['grade.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.grade').'は必須です。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.length'):
                //             $messages['length.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.length').'は必須です。';
                //             $messages['length.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.length').'は:min〜:maxまでの数値を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.thickness'):
                //             $messages['thickness.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.thickness').'は必須です。';
                //             $messages['thickness.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.thickness').'は:min〜:maxまでの数値を指定してください。';
                //             break;
                //         case config('const.productCsvHeader.productHeader.width'):
                //             $messages['width.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.width').'は必須です。';
                //             $messages['width.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.width').'は:min〜:maxまでの数値を指定してください。';
                //             break;
                //         default:
                //             break;
                //     }    
                // }
                break;
            case config('const.productCsvHeader.format_kbn.beeConnect.val'):
                $pCode = $csv[$line_num]['product_code'];
                
                $messages['class_big_id.required'] = $line_num.'行目：'. $pCode.'：'. config('message.error.product_csv_import.class_conversion');
                $messages['product_code.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.product_code').'は必須です。';
                $messages['product_code.max'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.product_code').'は:max文字以内で入力してください';
                $messages['product_name.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.product_name').'は必須です。';
                $messages['class_middle_id.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.class_middle_id').'が未入力または存在しません。';
                $messages['set_kbn.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.set_kbn').'は必須です。';
                $messages['model.max'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.model').'は:max文字以内で入力してください。';
                $messages['maker_id.required'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.maker_id').'は必須です。';
                $messages['price.required'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.price').'は必須です。';
                $messages['price.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.price').'は整数を指定してください。';
                $messages['quantity_per_case.numeric'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.quantity_per_case').'は整数を指定してください。';
                $messages['quantity_per_case.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.quantity_per_case').'は:min〜:maxまでの数値を指定してください。';
                $messages['unit.max'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.unit').'は:max文字以内で入力してください。';

            //     foreach (config('const.productCsvHeader.beeConnectHeader') as $col) {
            //         switch ($col) {
            //             case config('const.productCsvHeader.beeConnectHeader.product_code'):
            //                 $messages['product_code.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.product_code').'は必須です。';
            //                 $messages['product_code.max'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.product_code').'は:max文字以内で入力してください';
            //                 break;
            //             case config('const.productCsvHeader.beeConnectHeader.product_name'):
            //                 $messages['product_name.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.product_name').'は必須です。';
            //                 break;
            //             case config('const.productCsvHeader.beeConnectHeader.class_middle_id'):
            //                 $messages['class_middle_id.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.class_middle_id').'が未入力または存在しません。';
            //                 break;
            //             case config('const.productCsvHeader.beeConnectHeader.set_kbn'):
            //                 $messages['set_kbn.required'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.set_kbn').'は必須です。';
            //                 break;
            //             case config('const.productCsvHeader.beeConnectHeader.model'):
            //                 $messages['model.max'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.model').'は:max文字以内で入力してください。';
            //                 break;
            //             case config('const.productCsvHeader.beeConnectHeader.maker_id'):
            //                 $messages['maker_id.required'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.maker_id').'は必須です。';
            //                 break;
            //             case config('const.productCsvHeader.beeConnectHeader.price'):
            //                 $messages['price.required'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.price').'は必須です。';
            //                 $messages['price.integer'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.price').'は整数を指定してください。';
            //                 break;
            //             case config('const.productCsvHeader.beeConnectHeader.quantity_per_case'):
            //                 $messages['quantity_per_case.numeric'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.quantity_per_case').'は整数を指定してください。';
            //                 $messages['quantity_per_case.between'] =  $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.productHeader.quantity_per_case').'は:min〜:maxまでの数値を指定してください。';
            //                 break;
            //             case config('const.productCsvHeader.beeConnectHeader.unit'):
            //                 $messages['unit.max'] = $line_num.'行目：'. $pCode.'：'. config('const.productCsvHeader.beeConnectHeader.unit').'は:max文字以内で入力してください。';
            //                 break;
            //             default:
            //                 break;
            //     }
            // }
            break;
        }
        return $messages;
    }

    /**
     * 形式チェック
     *
     * @param Request $request
     * @return void
     */
    protected function fileValid(Request $request) 
    {
        $this->validate($request, [
            'file' => 'required|file|mimes:csv,txt|mimetypes:text/plain',
        ]);
    }
}