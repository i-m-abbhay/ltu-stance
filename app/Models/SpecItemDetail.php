<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 仕様項目明細
 */
class SpecItemDetail extends Model
{
    // テーブル名
    protected $table = 't_spec_item_detail';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;


    /**
     * Header_IDから取得
     *
     * @param  $id
     * @return void
     */
    public function getByHeaderId($headerId) 
    {
        $where = [];
        $where[] = array('detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('detail.spec_item_header_id', '=', $headerId);

        $data = $this
                ->from('t_spec_item_detail as detail')
                ->leftJoin('m_item as item', 'item.id', '=', 'detail.item_id')
                ->leftJoin('m_general as gene', function($join) {
                    $join->on('gene.value_code', '=', 'item.item_type')
                         ->where('gene.category_code', '=', config('const.general.itemtype'))
                         ;
                })
                ->where($where)
                ->selectRaw('
                        detail.id as detail_id,
                        detail.item_id as id,
                        item.item_name,
                        detail.display_order,
                        detail.item_group,
                        item.item_front_label,
                        item.item_back_label,
                        gene.value_text_1
                ')
                ->orderBy('detail.display_order', 'asc')
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
                    'spec_item_header_id' => $params['spec_item_header_id'],
                    'display_order' => $params['display_order'],
                    'item_group' => $params['item_group'],
                    'item_id' => $params['item_id'],
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
                        'spec_item_header_id' => $params['spec_item_header_id'],
                        'display_order' => $params['display_order'],
                        'item_group' => $params['item_group'],
                        'item_id' => $params['item_id'],
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
     * 物理削除
     * @param int $id ID
     * @return boolean True:成功 False:失敗 
     */
    public function physicalDeleteById($id) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            $result = $this
                ->where('id', $id)
                ->delete()
                ;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * ヘッダIDから論理削除
     *
     * @param [type] $id
     * @return void
     */
    public function deleteByHeaderId ($id) 
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            $result = $this
                ->where('spec_item_header_id', $id)
                ->update([
                    'del_flg' => config('const.flg.on'),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now()
                ])
                ;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;

    }

    /**
     * 項目IDから取得
     *
     * @param $itemIDs
     * @return void
     */
    public function getByItemId($itemId) 
    {
        $where = [];
        $where[] = array('spec_detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('item.id', '=', $itemId);
        
        $data = $this
                ->from('t_spec_item_detail as spec_detail')
                ->leftJoin('m_item as item', 'spec_detail.item_id', '=', 'item.id')
                ->where($where)
                ->select(
                    'item.id as item_id',
                    'item.choice_keyword'
                )
                ->get()
                ;

        return $data;
    }

    public function getByKeyword($keyword) 
    {
        $where = [];
        $where[] = array('detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('choice.choice_keyword', '=', $keyword);

        $data = $this
                ->from('t_spec_item_detail as detail')
                ->leftJoin('m_item as item', 'detail.item_id', '=', 'item.id')
                ->leftJoin('m_choice as choice', 'item.choice_keyword', '=', 'choice.choice_keyword')
                ->where($where)
                ->select(
                    'choice.id as item_id',
                    'choice.choice_keyword'
                )
                ->get()
                ;

        return $data;
    }

}