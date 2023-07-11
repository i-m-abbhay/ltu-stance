<?php
namespace App\Libs;

use Illuminate\Support\Facades\Schema;

/**
 * 共通処理
 */
class Common
{
    /**
     * キーを結合
     * 例）$str = chainKeys('hoge', 1, 10, 'a');
     *     結果=>hoge.1.10.a
     * 
     * @param string 引数の数は可変
     * @return string
     */
    public static function chainKeys(...$keys) {
        $rtnKeys = '';
        foreach ($keys as $k) {
            if ($rtnKeys !== '') {
                $rtnKeys .= config('const.joinKey');
            }
            $rtnKeys .= (string)$k;
        }
        return $rtnKeys;
    }

    /**
     * モデルプロパティセット
     *
     * @param Model $model
     */
    public static function initModelProperty($model) {
        // テーブル名取得
        $tableName = $model->getTable();
        // カラム名取得
        $columnList = Schema::getColumnListing($tableName);
        // プロパティセット
        foreach ($columnList as $colName) {
            if ($colName === 'id') {
                continue;
            }
            $model->$colName = '';
        }
        // return $model;
    }

    /**
     * 切り上げ
     *
     * @param [type] $value
     * @param integer $precision
     * @return void
     */
    public static function ceil_plus($value, $precision = 0) {
        $result = $value;
        if(self::isDecimal($value)){
            if($value >= 0){
                // 正の数の場合は切り上げ
                $result = round($value + 0.5 * pow(0.1, $precision), $precision, PHP_ROUND_HALF_DOWN);
            }else{
                // 負の数の場合は切り捨て TODO：通常のround関数に合わせて暫定対応
                $result = round($value - 0.5 * pow(0.1, $precision), $precision, PHP_ROUND_HALF_UP);
            }
        }else{
            switch (gettype($result)){
                case 'string':
                    $result = floatval($result);
                    break;
            }
        }
        return $result;
    }

    /**
     * 切り捨て
     *
     * @param [type] $value
     * @param integer $precision
     * @return void
     */
    public static function floor_plus($value, $precision = 0) {
        $result = $value;
        if(self::isDecimal($value)){
            if($value >= 0){
                // 正の数の場合は切り捨て
                $result = round($value - 0.5 * pow(0.1, $precision), $precision, PHP_ROUND_HALF_UP);
            }else{
                // 負の数の場合は切り上げ TODO：通常のroundの関数に合わせて暫定対応
                $result = round($value + 0.5 * pow(0.1, $precision), $precision, PHP_ROUND_HALF_DOWN);
            }
        }else{
            switch (gettype($result)){
                case 'string':
                    $result = floatval($result);
                    break;
            }
        }
        return $result;
    }

    /**
     * 小数か
     * 200.00⇒false
     * 200.01⇒true
     * 
     * @param [type] $value
     * @return void
     */
    public static function isDecimal($value){
        $result = false;
        $list = explode('.', strval($value));
        if(count($list) >= 2){
            if(preg_match("/[^0]+$/", $list[1])){
                // 小数点以下が0以外
                $result = true;
            }
        }
        return $result;
    }

    /**
     * 仕入単価・定価など小数点以下を四捨五入する(10.3456⇒10)
     * @param [type] $value
     * @return void
     */
    public static function roundDecimalStandardPrice($value){
        return round($value, 0);
    }

    /**
     * 販売単価など小数点以下を切り上げる(10.3456⇒11 10.0001⇒11)
     * @param [type] $value
     * @return void
     */
    public static function roundDecimalSalesPrice($value){
        return self::ceil_plus($value);
    }
    
    /**
     * 掛率など小数点第3位を四捨五入して第2位まで返す(10.3456⇒10.35)
     * @param [type] $value
     * @return void
     */
    public static function roundDecimalRate($value){
        return round($value, 2);
    }

    /**
     * nullの場合空文字を返す
     * @param $data
     * @param $result
     */
    public static function nullToBlank($data){
        $result = '';
        if($data !== null){
            $result = $data;
        }
        return $result;
    }

    /**
     * nullか空白の場合に数値の0を返す
     * @param $data
     * @return $result
     */
    public static function nullorBlankToZero($data){
        $result = 0;
        if($data !== null && $data !== ''){
            $result = $data;
        }
        return $result;
    }

    /**
     * フラグが立っているか
     * @param type $flg
     * @return bool $result
     */
    public static function isFlgOn($flg){
        $res = false;
        switch (gettype($flg)){
            case 'string':
                if($flg === 'true'){
                    $res = true;
                }else if($flg === strval(config('const.flg.on'))){
                    $res = true;
                }
                break;
            case 'integer':
                if($flg === config('const.flg.on')){
                     $res = true;
                }
                break;
            case 'boolean':
                $res = $flg;
                break;
        }
        return $res;
    }

    /**
     * 倍数チェック（小数点以下2桁まで）
     *
     * @param float $quantity チェック対象の数
     * @param float $baseQuantity 元になる数
     * @return boolean true:倍数である false:倍数ではない
     */
    public static function isMultipleQunaity($quantity, $baseQuantity) {
        $modRes = ($quantity * 100) % ($baseQuantity * 100);
        return ($modRes === 0);
    }
}