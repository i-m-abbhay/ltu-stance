<?php

return [
    'success' => [
        'save' => '保存しました',
        'delete' =>  '削除しました',
        'cancel' => '取消しました',
        'apply' => '申請しました',
        'activate' => '有効化しました',
        'complete' => '完了しました',
        'release' => '解除しました',
    ],

    'error' => [
        'error' => 'エラーが発生しました',
        'save' => '保存に失敗しました',
        'delete' =>  '削除に失敗しました',
        'activate' => '有効化に失敗しました',
        'loginErr' => 'IDまたはパスワードが間違っています',
        'getlock' => 'ロック取得に失敗しました',
        'cancel' => '取消に失敗しました',
        'apply' => '申請に失敗しました',
        'exceeded_length'   => '表現できる桁数を超えています',

        'customer' =>[
            'received_order_not_input'=> '受注確定に必要な情報が得意先マスタに登録されていません',
        ],
        'matter' => [
            'duplicated' => '案件名が同一になる案件が存在します',
            'completed' => '案件完了しているため実行できません'
        ],
        'matter_detail' => [
            'delete_quote_request' => '見積依頼データが存在するため削除できません',
            'delete_quote' => '見積データが存在するため削除できません',
            'delete_order' => '発注データが存在するため削除できません',
            'delete_sales' => '売上データが存在するため削除できません',
            'delete_purchase_line_item' => '仕入データが存在するため削除できません',
            'complete_order' => '入荷済になっていない発注が存在するため、案件完了できません',
            'complete_stock' => '案件在庫が存在するため、案件完了できません',
            'complete_loading' => '配送中が存在するため、案件完了できません',
            'complete_sales' => '売上確定が終わっていないため、案件完了できません',
            'complete_requests' => '請求締めが終わっていないため、案件完了できません',
        ],
        'quote_request' => [
            'calendar_none' => 'カレンダーマスタが設定されていません。システム管理者へ連絡してください'
        ],
        'quote' => [
            'delete_order' => '発注データが存在するため削除できません',
            'delete_reserve' => '引当データが存在するため削除できません',
            'delete_sales_detail' => '売上データが存在するため削除できません',
            'delete_sales' => '階層内に売上データが存在するため階層を削除できません',
        ],
        'search' => [
            'none' => 'データがありませんでした。'
        ],
        'department' => [
            'parent_circulate' => '部門-親部門の関連が循環しています',
            'duplication_bank' => '異なる銀行を選択してください',
        ],
        'choice' => [
            'exist_keyword' => '既に存在しているキーワードです。',
        ],
        'spec_item' => [
            'used' => '見積依頼に使用されているため、更新できませんでした。',
        ],
        'approval' => [
            'already_has_approver' => '承認者が存在するため削除できません。',
        ],
        'order' => [
            'arrival_complete'  => '$product_code：入荷済みのため商品を変更できません。',
            'loading_finish'    => '$product_code：出荷積込が完了しているため商品を変更できません。',
            'delivery'          => '$product_code：納品しているため商品の情報を変更できません。',
            'sales_detail'      => '$product_name：売上が作成されているため商品の情報を変更できません。',
            'min_quantity'      => '$product_code：最小単位数が異なります。商品を選択し直してください。',
            'order_lot_quantity_by' => '$product_code：発注ロットの倍数で入力して下さい。',
            'intangible_flg_on' => '$product_code：無形品から有形品に変更できません。',
            'intangible_flg_off'=> '$product_code：有形品から無形品に変更できません。',
            'set_product'       => '$product_name：一式商品のため数量は1つまでです。',
            'start_date_1'      => '$product_code：適用開始日になっていないため、発注できません。',
            'start_date_2'      => '$product_code：既に発注しているため、適用開始日になっていない商品への変更はできません。',
            'order_sudden_exist_matter'  => '既に存在する案件のため作成できません。',
        ],
        'order_detail' => [
            'change_delivery_address_arrival_complete'  => '入荷済みの商品があるため入荷先を変更できません。',
            'change_delivery_address_loading_finish'    => '出荷積込が完了しているため入荷先を変更できません。',
            'delete_arrival_complete'                   => '$product_code：入荷済みのため発注取消できません。',
            'delete_loading_finish'                     => '$product_code：出荷積込が完了しているため発注取消できません。',
            'change_order_quantity_arrival_complete'    => '$product_code：入荷済みのため発注数を変更できません。',
            'change_order_quantity_loading_finish'      => '$product_code：出荷積込が完了しているため発注数を変更できません。',
            'change_arrival_plan_date'                  => '$product_code：入荷済みのため入荷予定日を変更できません。',
            'change_delivery_address_kbn_arrival_plan_date' => '入荷予定日に今日以前の日付が指定されている明細があるため入荷先を 直送/メーカー倉庫 へは変更はできません。',
            'save_shipment_detail'                      => '出荷指示が存在するため保存できません',
        ],
        'sales' =>[
            'approval_data_not_found'   => '承認するデータがありません',
            'required_request_e_day'    => '売上終了日は必須です。',
            'required_expecteddeposit_at'   => '入金予定日は必須です。',
            'expecteddeposit_at'        => '入金予定日は今日以降の日付で入力してください。',
            'release_request_status'    => '表示されている売上明細は、売上確定状態ではありません。確認してください。',
            'delete_request_status'     => '表示されている売上明細は、未処理状態ではありません。確認してください。',
            'complete_request_status'   => '表示されている売上明細は、未処理状態ではありません。確認してください。',
            'not_sales_date'            => '売上期間よりも前のデータがあります。売上明細画面で売上日を設定してください。',
            'outside_period_sales_date' => '未確定の未納データがあるため納品データの売上日を売上期間外には設定できません。\n売上明細画面で未納データを削除するか、相殺の対象でない納品データの場合は売上日を売上期間内の日付に設定してください。',
            'sales_date'                => '売上期間の開始日と終了日が不正です。請求締めを行ってください。',
            'exist_request_data'        => '既に請求データが作られています。再度画面を開きなおしてください。',
            'applying'                  => '承認が完了していない案件があります。確認してください。',
            'create_user_sales_matter'  => 'その他案件の作成に失敗しました。',
            'create_user_sales_matter_lock'  => 'ロック取得に失敗したため、その他案件が作成できませんでした。',
            'update_at_different'       => '既に更新されているため保存できません。',
            'not_closed_request' => '前月の請求の締めが完了していません。確認の上再度実行してください。',
        ],
        'shipment' => [
            'existLoadingData' => '積込データが存在するため変更できません。',
            'over_reserve' => '引当数以上の出荷指示はできません',
            'order_sudden_shipment' => '発注品が含まれているため、登録できません。',
            'over_stock' => '在庫が足りないため、登録できません。',
            'loaded_shipment' => '出荷済みです、削除できません。',
        ],
        'loading' => [
            'loaded_shipment' => '出荷済みのため、登録できません。',
            'location_different' => '商材が移動されました。出荷配送を再度行ってください。',
        ],
        'campaign_price' => [
            'existDate' => '適用期間が重複しているため、保存できません。',
            'error_date' => '適用期間が不正です。',
        ],
        'authority_edit' => [
            'no_authority_holder' => '権限設定の権限保持者が存在しないため、保存できません。',
        ],
        'product_check' => [
            'not_draft' => 'すでに処理されています。',
        ],
        'product_csv_import' => [
            'csv_not_match' => 'CSVフォーマットが一致しません：',
            'exist_error' => '存在しません。',
            'class_conversion' => '中分類が変換できません。',
        ],
        'approval' => [
            'status_changed' => '承認状況が変わっている為、更新できません。'
        ],
        'purchase_detail' => [
            'payment_status_confirm' => '既に支払予定が確定されているため、保存できません。',
            'payment_status_not_closing' => '締められていない支払予定が存在するため、保存できません',
            'customer_locked' => '対象の売上情報が更新中であるため、仕入確定を中止しました。',
        ],
        'payment_list' => [
            'updated_purchase_detail' => '仕入詳細が更新されました。内容を確認の上、再度「確定」ボタンをクリックしてください。',
            'request_approval_error' => '承認が実行できなかった申請があります。再度実行してください。',
        ],
        'allocation' => [
            'reserve_quantity_validity' => '出荷済み以下の引当数に変更はできません',
            'not_shipment_exist' => '未出荷の出荷指示があります。数量変更はできません。',
        ],
        'product' => [
            'exist_product_code' => '既に使用されている商品番号です。',
        ],
        'dashboard' => [
            'undefined_period' => '年度情報が存在しません。管理者に連絡してください。',
        ],
        'request' => [
            'not_closing_request' => '未処理の請求が存在します。',
            'excluded_print' => '印刷対象外の請求が存在しました。選択した請求を確認してください。\n・印刷可となっていない\n・前月請求が締められていない',
            'matter_closed' => '既に完了している案件が含まれているため請求解除できません。確認してください。',
        ],
        'deposit' => [
            'input_credited_date' => '入金処理日もしくは実入金日が設定されていません。',
            'confirm_credited_date' => '入金処理日・実入金日に請求書の売上開始日以前の日付が設定されています。確認してください。',
            'approval_credited_date' => '入金処理日・実入金日に請求書の売上開始日以前の日付が設定されています。承認しますか？',
        ],
        'arrival' => [
            'canceled_arrival' => '入荷は取り消し済みです。'
        ],
    ],

    'confirm' => [
        'sales' =>[
            'not_create_notdelivery_data'   => '階層販売金額を利用する明細に「未納」が作成されていません。よろしいですか？',
            'sales_complete'                => '売上確定処理を行います。よろしいですか？',
        ],
        'deposit' => [
            'miscalculation_approval' => '承認してもよろしいですか。',
        ],
    ],

    'notice' => [
        'quote_request_new' => '【見積依頼登録】',
        'quote_request_edit' => '【見積依頼更新】',
        'quote_approval_request_add' => '新しい見積承認依頼があります',
        'quote_approval' => '見積が承認されました',
        'quote_sendback' => '見積が差し戻されました',
        'sales_applying' => '＜$customer_name＞ $matter_name 売上明細で、販売金額の変更申請があります。確認してください。',
        'sales_approved' => '＜$customer_name＞ $matter_name 売上明細で、販売金額の変更申請が承認されました。確認してください。',
        'sales_sendback' => '＜$customer_name＞ $matter_name 売上明細で、販売金額の変更申請が否認されました。確認してください。',
    ],

];
