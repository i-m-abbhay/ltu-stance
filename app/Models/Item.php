<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 項目マスタ
 */
class Item extends Model
{
    // テーブル名
    protected $table = 'm_item';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * コンボボックス用リスト取得
     *
     * @return void
     */
    public function getComboList() 
    {
        // Where句
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        
        $data = $this
                ->where($where)
                ->select(
                    'id',
                    'item_name',
                    'choice_keyword'
                )
                ->get()
                ;

        return $data;
    }


    /**
     * キーワードから取得
     *
     * @param $keyword
     * @return void
     */
    public function getByKeyword($keyword) 
    {
        $where = [];
        $where[] = array('item.del_flg', '=', config('const.flg.off'));
        $where[] = array('item.choice_keyword', '=', $keyword);

        $data = $this
                ->from('m_item as item')
                ->where($where)
                ->select(
                    'item.id',
                    'item.choice_keyword'
                )
                ->get()
                ;

        return $data;
    }

    /**
     * 項目名リスト取得
     *
     * @return void
     */
    public function getNameList() 
    {
        // Where句
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        
        $data = $this
                ->where($where)
                ->select(
                    'item_name'
                )
                ->groupBy('item_name')
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
                    'item_name' => $params['item_name'],
                    'item_front_label' => $params['item_front_label'],
                    'item_back_label' => $params['item_back_label'],
                    'item_type' => $params['item_type'],
                    'choice_keyword' => $params['choice_keyword'],
                    'required_flg' => $params['required_flg'],
                    'memo' => $params['memo'],
                    'del_flg' => config('const.flg.off'),
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
                        'item_name' => $params['item_name'],
                        'item_front_label' => $params['item_front_label'],
                        'item_back_label' => $params['item_back_label'],
                        'item_type' => $params['item_type'],
                        'choice_keyword' => $params['choice_keyword'],
                        'required_flg' => $params['required_flg'],
                        'memo' => $params['memo'],
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
     * IDで取得
     * @param int $id 選択肢ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->leftjoin('m_staff', 'm_staff.id', '=', 'm_item.update_user')
                ->select(
                    'm_item.*', 
                    'm_staff.staff_name AS update_user_name'
                )
                ->where(['m_item.id' => $id])
                ->first()
                ;

        return $data;
    }

    /**
     * 論理削除
     * @param int $id ID
     * @return boolean True:成功 False:失敗 
     */
    public function deleteById($id) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('id', $id)
                ->update([
                    'del_flg' => config('const.flg.on'),
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
     * 一覧取得
     *
     * @param $params
     * @return 
     */
    public function getList($params, $category) 
    {
        // Where句作成
        $where = [];
        $where[] = array('item.del_flg', '=', config('const.flg.off'));
        $hasType = false;

        if (!empty($params['item_name'])) {
            $where[] = array('item.item_name', 'LIKE', '%'.$params['item_name'].'%');
        }
        if (!empty($params['item_type'])) {
            $hasType = true;
        }

        $data = $this
                ->from('m_item as item')
                ->leftJoin('m_general as gene', function($join) use ($category) {
                    $join->on('gene.value_code', '=', 'item.item_type')
                         ->where('gene.category_code', '=', $category)
                          ;
                })
                ->where($where)
                ->where(function ($query) use ($params, $hasType) {
                    if ($hasType) {
                        if(count($params['item_type']) > 1)
                        {
                            // 種別が複数の場合
                            $query->where('item.item_type', '=', $params['item_type'][0]);
                            for ($i = 1; $i < count($params['item_type']); $i++)
                            {
                                $query->orWhere('item.item_type', '=', $params['item_type'][$i]);
                            }
                        }else if (count($params['item_type']) == 1)
                        {
                            // 種別が一つの場合
                            $query->where('item.item_type', '=', $params['item_type'][0]);
                        }
                    }
                })
                ->selectRaw('
                    item.*,
                    gene.value_text_1
                ')
                ->get()
                ;

        return $data;
    }


    /**
     * 一覧取得
     *
     * @param $params
     * @return 
     */
    public function getAllList() 
    {
        // Where句作成
        $where = [];
        $where[] = array('item.del_flg', '=', config('const.flg.off'));

        $data = $this
                ->from('m_item as item')
                ->leftJoin('m_general as gene', function($join) {
                    $join->on('gene.value_code', '=', 'item.item_type')
                         ->where('gene.category_code', '=', config('const.general.itemtype'))
                          ;
                })
                ->where($where)
                ->selectRaw('
                    item.id as id,
                    item.item_name,
                    item.item_front_label,
                    item.item_back_label,
                    gene.value_text_1
                ')
                ->get()
                ;

        return $data;
    }
}