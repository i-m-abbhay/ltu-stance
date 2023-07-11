<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * 汎用マスタ
 */
class General extends Model
{
    // テーブル名
    protected $table = 'm_general';
    public $timestamps = false;

    protected $appends = ['open'];

    public function getOpenAttribute() {
        return true;
    }
    
    /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params) {
        // Where句
        $where = [];
        $where[] = array('m_general.del_flg', '=', config('const.flg.off'));
        if (!empty($params['id'])) {
            $where[] = array('id', '=', $params['id']);
        }
        if (!empty($params['value_text_1'])) {
            $where[] = array('value_text_1', 'LIKE', '%'.$params['value_text_1'].'%');
        }
        if (!empty($params['value_text_2'])) {
            $where[] = array('value_text_2', 'LIKE', '%'.$params['value_text_2'].'%');
        }
        if (!empty($params['value_text_3'])) {
            $where[] = array('value_text_3', 'LIKE', '%'.$params['value_text_3'].'%');
        }

        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('id', 'asc')
                ->get()
                ;
        
                return $data;
    }

    /**
     * 共通名称マスタの値を取得（category_code & value_code）
     *
     * @return object
     */
    public function getGeneralByKey($categoryCode, $valueCode) {
        $where = [];
        $where[] = array('m_general.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_general.category_code', '=', $categoryCode);
        $where[] = array('m_general.value_code', '=', $valueCode);

        $data = $this  
                ->where($where)
                ->first()
                ;
        
        return $data;
    }

    /**
     * カテゴリリスト取得
     *
     * @return void
     */
    public function getCategoryList($categoryCode) {

        $data = $this  
                ->where('m_general.del_flg', '=', config('const.flg.off'))
                ->where('m_general.category_code', '=', $categoryCode)
                ->orderBy('m_general.display_order', 'asc')
                ->get()
                ;
        
        return $data;
    }

    /**
     * コンボボックス取得
     *
     * @return void
     */
    public function getComboList() {

        $data = $this  
                ->where('m_general.del_flg', '=', config('const.flg.off'))
                ->where('m_general.edit_kbn', '<>', config('const.flg.off'))
                ->selectRaw('
                    MIN(m_general.id) AS id,
                    m_general.category_code,
                    m_general.category_name
                ')
                ->groupBy('m_general.category_code', 'm_general.category_name')
                ->orderBy('m_general.category_name', 'asc')
                ->get()
                ;
        
        return $data;
    }

    /**
     * カテゴリリスト取得 (共通名称マスタ編集)
     *
     * @return void
     */
    public function getListByCategoryCode($categoryCode) {

        $data = $this
                ->where('m_general.category_code', '=', $categoryCode)
                ->selectRaw('
                    m_general.id,
                    m_general.category_code,
                    m_general.value_code,
                    m_general.category_name,
                    m_general.value_text_1,
                    m_general.value_text_2,
                    m_general.value_text_3,
                    m_general.value_num_1,
                    m_general.value_num_2,
                    m_general.value_num_3,
                    m_general.display_order,
                    m_general.edit_kbn,
                    m_general.del_flg,
                    m_general.update_at,
                    m_general.update_user,
                    m_general.created_at,
                    m_general.created_user,
                    CASE 
                        WHEN m_general.del_flg ='.config('const.flg.off').' 
                            THEN \'使用する\' 
                        WHEN m_general.del_flg ='.config('const.flg.on').'  
                            THEN \'使用しない\' 
                    END AS status
                ')
                ->orderBy('m_general.display_order', 'asc')
                ->get()
                ;
        
        return $data;
    }


    /**
     * 一覧取得 (共通名称マスタ)
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getGeneralList($params) {
        // Where句
        $where = [];
        $where[] = array('m_general.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_general.edit_kbn', '<>', config('const.flg.off'));

        if (!empty($params['category_name'])) {
            $where[] = array('category_name', 'LIKE', '%'.$params['category_name'].'%');
        }

        // データ取得
        $data = $this
                ->where($where)
                ->selectRaw('
                    MIN(id) AS id,
                    category_code,
                    category_name
                ')
                ->orderBy('category_name', 'asc')
                ->groupBy('category_code', 'category_name')
                ->get()
                ;
        
        return $data;
    }

    /**
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add($params) {
        $result = false;
        try {
            $result = $this->insertGetId([
                    'category_code' => $params['category_code'],
                    'value_code' => $params['value_code'],
                    'category_name' => $params['category_name'],
                    'value_text_1' => $params['value_text_1'],
                    'value_text_2' => $params['value_text_2'],
                    'value_text_3' => $params['value_text_3'],
                    'value_num_1' => $params['value_num_1'],
                    'value_num_2' => $params['value_num_2'],
                    'value_num_3' => $params['value_num_3'],
                    'display_order' => $params['display_order'],
                    'edit_kbn' => $params['edit_kbn'],
                    'del_flg' => $params['del_flg'],
                    'created_user' => Auth::user()->id,
                    'created_at' => Carbon::now(),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);

        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateById($params) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        'category_code' => $params['category_code'],
                        'value_code' => $params['value_code'],
                        'category_name' => $params['category_name'],
                        'value_text_1' => $params['value_text_1'],
                        'value_text_2' => $params['value_text_2'],
                        'value_text_3' => $params['value_text_3'],
                        'value_num_1' => $params['value_num_1'],
                        'value_num_2' => $params['value_num_2'],
                        'value_num_3' => $params['value_num_3'],
                        'display_order' => $params['display_order'],
                        'edit_kbn' => $params['edit_kbn'],
                        'del_flg' => $params['del_flg'],
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 名称を取得する（主に樹種、等級を想定）
     *
     * @return Collection $data[x]=>['code' => 12, 'name' => '桧グリーン']  ※キーはCADコード
     */
    public function getConvCadItem($categoryCode, $convCategoryCode)
    {
        $cadConvSql = DB::table('m_general')
            ->where([
                ['del_flg', '=', config('const.flg.off')],
                ['category_code', '=', $convCategoryCode],
            ])
            ->select([
                'value_num_1 AS cad_code',
                'value_num_2 AS code',
            ])
            ;
        $itemSql = DB::table('m_general')
            ->where([
                ['del_flg', '=', config('const.flg.off')],
                ['category_code', '=', $categoryCode],
            ])
            ->select([
                'value_code AS code',
                'value_text_1 AS name',
            ])
            ;
        $data = $this
                ->from(DB::raw('('. $cadConvSql->toSql(). ') AS cad_conv'))
                ->mergeBindings($cadConvSql)
                ->leftJoin(DB::raw('('. $itemSql->toSql(). ') AS item'), 'cad_conv.code', '=', 'item.code')
                ->mergeBindings($itemSql)
                ->select([
                    'cad_conv.cad_code',
                    'item.code',
                    'item.name',
                ])
                ->get();

        $data = $data->where('cad_code', '<>', null)->where('cad_code', '<>', '');
        $data = $data->mapWithKeys(function($item){
            return [$item['cad_code'] => ['code' => $item->code, 'name' => $item->name]];
        });
        return $data;

    }

}