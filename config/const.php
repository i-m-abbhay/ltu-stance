<?php

return [
    //バッチユーザー
    'batchUser' => 0,
    // フラグ
    'flg' => [
        'off' => 0,
        'on' => 1
    ],
    // ラベル用
    'LBL_TEXT'   => [
        'OWN_STOCK_CUSTOMER_NAME' => '富建',
    ],
    // 権限
    'authority' => [
        'none' => 0,
        'has' => 1,
    ],

    // 権限(m_generals.category_code='AUTH'のvalue_codeに紐づく値)
    'auth' => [
        // マスタ系
        'master' => [
            'inquiry' => 1,
            'edit' => 2,
        ],
        // 締め処理
        'close' =>  [
            'closing' => 3
        ],
        // 顧客データ
        'customer' => [
            'edit' => 4
        ],
        // 案件データ
        'matter' =>  [
            'edit' => 5
        ],
        // 住所データ
        'address' =>  [
            'edit' => 6
        ],
        // 入金入力
        'deposit' =>  [
            'input' => 7
        ],
        // 請求書発行
        'invoice' =>  [
            'output' => 8
        ],
        // 会計データ出力
        'account' =>  [
            'output' => 9
        ],
        // 振込データ出力
        'transfer' =>  [
            'output' => 10
        ],
        // 権限設定
        'authority' =>  [
            'setting' => 11
        ],
        // 全承認
        'fullApproval' => '12',

        // 仕入
        'purchase' => [
            'staff' => 13,          // 仕入担当者
            'approval_staff_1' => 14,    // 第一承認者
            'approval_staff_2' => 15,    // 第二承認者
            'approval_staff_3' => 16,    // 第三承認者
        ],
    ],

    // 支払条件(m_generals.category_code='PAYCON'のvalue_codeに紐づく値)
    'paycon' => [
        'usual' => 1,
    ],

    // 画面からロック対象テーブルを判断するためのリスト
    'lockList' => [
        'base-edit' => ['m_base'],
        'department-edit' => ['m_department'],
        'warehouse-edit' => ['m_warehouse'],
        'staff-edit' => ['m_staff'],
        'product-edit' => ['m_product'],
        'matter-edit' => ['t_matter'],
        'matter-edit_update' => ['t_quote'],
        'matter-detail' => ['t_matter'],
        'quote-edit' => ['t_quote', 't_matter'],
        'shipping-instruction' => ['t_shipment'],
        'order-list' => ['t_quote'],
        'order-detail' => ['t_quote'],
        'order-edit' => ['t_quote'],
        'order-sudden' => ['t_matter'],
        'supplier-edit' => ['m_supplier'],
        'supplier-maker-contrast' => ['m_supplier'],
        'product-nickname' => ['m_product'],
        'new-customer-edit' => ['m_customer'],
        'shelf-number-edit' => ['m_warehouse'],
        'product-campaign-price' => ['m_product'],
        'supplier-file' => ['m_supplier'],
        'general-edit' => ['m_general'],
        'stock-allocation' => ['t_quote'],
        'product-check' => ['t_quote'],
        'product-check-self' => ['m_product'],
        'request-list' => ['m_customer'],
        'sales-detail' => ['m_customer'],
        'deposit-list' => ['t_credited'],
        'sales-list' => ['m_customer'],
        'calendar-data' => ['m_calendar'],
        'calendar-edit' => ['m_calendar'],
        'class-middle-edit' => ['m_class_middle'],
        'class-small-edit' => ['m_class_small'],
        'payment-list' => ['m_customer'],
        'purchase-detail' => ['m_customer'],
        'batch-save-sales' => ['m_customer'],
    ],
    // キー結合文字
    'joinKey' => '.',

    // ログ区分
    'logKbn' => [
        'update' => 1,
        'soft_delete' => 2,
        'delete' => 3
    ],

    // GETパラメータ
    'query' => [
        'mode' => 'mode'
    ],
    'mode' => [
        'show' => '1',
        'edit' => '2'
    ],

    // アップロード禁止拡張子（暫定、バリデーションチェックが一度に31個までしかできない？）
    'forbidden_extention' => 'apk,bat,cab,chm,cmd,com,cpl,dll,dmg,exe,hta,ins,isp,iso,jar,js,jse,lib,lnk,nsh,pif,ps1,shb,sys,vb,vbe,vbs,vxd,wsc,wsf,wsh',

    // ファイルアップロードパス
    'uploadPath' => [
        'company_stamp' => 'public/upload/company_stamp/',
        'staff_stamp' => 'public/upload/staff_stamp/',
        'person' => 'public/upload/person/customer/',
        'person_supplier' => 'public/upload/person/supplier/',
        'customer' => 'public/upload/customer/',
        'quote_request' => 'public/upload/quote_request/',
        'quote_request_default' => 'public/upload/quote_request_default/',
        'item_choice' => 'public/upload/item_choice/',
        'quote_version' => 'public/upload/quote_version/',
        'address' => 'public/upload/address/',
        'product' => 'public/upload/product/',
        'order' => 'public/upload/order/',
        'supplier_file' => 'public/upload/supplier_file/',
        'order' => 'public/upload/order/',
        'request' => 'public/upload/request/',
        'paymentRequest' => 'public/upload/payment_request/',
    ],
    // 画像エンコード
    'encode' => [
        'png' => 'data:image/png;base64,',
        'jpeg' => 'data:image/jpeg;base64,',
    ],
    // 画像エンコード判定
    'extEncode' => [
        'png' => ['png', 'PNG'],
        'jpeg' => ['jpeg', 'jpg', 'JPG'],
    ],

    // 汎用マスタ　分類コード
    'general' => [
        'pos' => 'POS',               // 役職
        'auth' => 'AUTH',             // 権限
        'arch' => 'ARCH',             // 建築種別
        'spec' => 'SPEC',             // 仕様区分
        'qreq' => 'QREQ',             // 見積依頼項目・工事種類
        'com' => 'COM',               // 企業業種
        'taxtype' => 'TAXTYPE',       // 税種別
        'taxkbn' => 'TAXKBN',         // 税区分
        'taxrounding' => 'TAXROUNDING', // 税端数処理区分
        'issue' => 'ISSUE',           // 納品希望時刻
        'price' => 'PRICE',           // 価格区分
        'alloc' => 'ALLOC',           // 引当区分
        'paycon' => 'PAYCON',         // 支払条件
        'fee' => 'FEE',               // 手数料区分_仕入先
        'account' => 'ACCOUNT',       // 口座種別
        'paysight' => 'PAYSIGHT',     // 支払サイト
        'itemtype' => 'ITEMTYPE',     // 項目種別
        'juridical' => 'JURIDICAL',   // 法人格
        'noproductcode' => 'NOPRODUCTCODE', // 商品コード無し
        'collection' => 'COLLECTION', // 回収区分
        'customerfee' => 'CUSTOMERFEE', // 手数料区分_得意先
        'taxcalc' => 'TAXCALC',       // 税計算区分
        'costsales' => 'COSTSALES',   // 仕入販売区分
        'rtnprocess' => 'RTNPROCESS', // 返品処置区分
        'wood' => 'WOOD',             // 樹種
        'woodconv' => 'WOODCONV',     // 樹種CAD変換
        'grade' => 'GRADE',           // 等級
        'gradeconv' => 'GRADECONV',   // 等級CAD変換
        'purchasetype' => 'PURCHASETYPE',// 仕入種別
        'safetyfeetype' => 'SAFETYFEETYPE', // 安全会費種別
        'cashtype' => 'CASHTYPE',       // 現金種別 
        'billstype' => 'BILLSTYPE'      // 手形種別
    ],

    // 項目種別
    'itemType' => [
        1 => 'text',
        2 => 'textarea',
        3 => 'select',
        4 => 'checkbox',
        5 => 'radio',
        6 => 'number',
        7 => 'date',
    ],
    // 見積提出期限 n日後
    'quoteLimitInit' => 5,

    // デフォルト値
    'default' => [
        'default' => 'default',
    ],
    // パーソンマスタ 種別
    'person_type' => [
        'customer' => 1,
        'supplier' => 2,
    ],

    // 採番系
    'number_manage' => [
        // 初期値
        'default_value' => 1,
        // 区分
        'kbn' => [
            'matter' => 'matter',
            'quote' => 'quote',
            'order' => 'order',
            'qr' => 'qr',
            'request' => 'request',
            'credited' => 'credited',
            'payment' => 'payment',
        ],
        // 結果
        'result' => [
            'fail' => -1
        ]
    ],

    // 通知種別
    'notice_type' => [
        'quote_request' => 1,   // 見積依頼
        'order_fixing' => 2, // 受注確定
        'order' => 3,        // 発注
        'arrival' => 4,      // 入荷
        'restocking' => 5,      // 在庫補充
        'sales_fixing' => 6, // 売上確定
        'payment' => 7,      // 入金
        'purchase' => 8,     // 仕入
        'sales_closing' => 9,// 売上締め実施
        'purchase_closing' => 10,// 仕入締め実施
    ],
    'notice_from_all' => 0,

    // 見積依頼状況
    'quoteRequestStatus' => [
        // 値
        'val' => [
            'editing' => 0,
            'requested' => 1
        ],
        // 表示名
        'list' => [
            0 => '作成中',
            1 => '依頼済'
        ]
    ],

    // 見積状況
    'quoteStatus' => [
        // 値
        'val' => [
            'incomplete' => 0,
            'complete' => 1
        ],
        // 表示名
        'list' => [
            0 => '未完了',
            1 => '完了'
        ]
    ],

    // 見積版状況
    'quoteVersionStatus' => [
        // 値
        'val' => [
            'editing' => 0,
            'applying' => 1,
            'approved' => 2,
            'sendback' => 3,
        ],
        // 表示名
        'list' => [
            0 => '作成中',
            1 => '申請中',
            2 => '承認済',
            3 => '差戻',
        ]
    ],

    // 発注状況
    'orderStatus' => [
        // 値
        'val' => [
            'not_ordering' => 0,
            'applying' => 1,
            'approving' => 2,
            'approved' => 3,
            'ordered' => 4,
            'sendback' => 5,
        ],
        // 表示名
        'list' => [
            0 => '未発注',
            1 => '申請中',
            2 => '承認中',
            3 => '承認済',
            4 => '発注済',
            5 => '差戻',
        ]
    ],
    // 届け先区分
    'deliveryAddressKbn' => [
        // 値
        'val' => [
            'site' => 1,        // 現場
            'company' => 2,     // 自社倉庫
            'supplier' => 3,    // 仕入先倉庫
        ],
        // 表示名
        'list' => [
            1 => '現場',
            2 => '自社倉庫',
            3 => '仕入先倉庫',
        ]
    ],
    // 直送
    'directDeliveryAddressName' => '直送',
    // 承認ヘッダステータス
    'approvalHeaderStatus' => [
        // 値
        'val' => [
            'not_approved' => 0,
            'approving' => 1,
            'approved' => 2,
            'sendback' => 3,
        ],
        // 表示名
        'list' => [
            0 => '未承認',
            1 => '承認中',
            2 => '承認済',
            3 => '差戻',
        ],
    ],
    // 承認明細ステータス
    'approvalDetailStatus' => [
        // 値
        'val' => [
            'not_approved' => 0,
            'approved' => 1,
            'sendback' => 2,
        ],
        // 表示名
        'list' => [
            0 => '未承認',
            1 => '承認済',
            2 => '差戻',
        ],
        'class' => [
            0 => 'not_approved',
            1 => 'approved',
            2 => 'sendback',
        ]
    ],
    'autoApprovalComment' => '自動承認',

    // 案件一覧 進捗状況
    'matterListProgress' => [
        // ステータス
        'status' => [
            'not_implemented' => 'not_implemented',
            'not_treated' => 'not_treated',
            'editing' => 'editing',
            'applying' => 'applying',
            'approving' => 'approving',
            'requested' => 'requested',
            'approved' => 'approved',
            'ordered' => 'ordered',
            'complete' => 'complete',
        ],
        // 見積依頼
        'quote_request' => [
            'not_implemented' => [  // 未実施
                'text' => '',
                'class' => 'not-implemented'
            ],
            'editing' => [             // 編集中
                'text' => '編集中',
                'class' => 'halfway'
            ],
            'requested' => [        // 依頼済
                'text' => '依頼済',
                'class' => 'complete'
            ],
        ],
        // 見積
        'quote' => [
            'not_implemented' => [  // 未実施
                'text' => '',
                'class' => 'not-implemented'
            ],
            'editing' => [             // 編集中
                'text' => '編集中',
                'class' => 'halfway'
            ],
            'applying' => [            // 申請中
                'text' => '申請中',
                'class' => 'halfway'
            ],
            'approved' => [         // 承認済
                'text' => '承認済',
                'class' => 'complete'
            ],
            'complete' => [         // 完了
                'text' => '完了',
                'class' => 'complete',
            ]
        ],
        // 発注
        'order' => [
            'not_implemented' => [   // 未実施
                'text' => '',
                'class' => 'not-implemented'
            ],
            'not_treated' => [       // 未処理
                'text' => '未処理',
                'class' => 'not-treated'
            ],
            'editing' => [             // 編集中
                'text' => '編集中',
                'class' => 'halfway'
            ],
            'applying' => [             // 申請中
                'text' => '申請中',
                'class' => 'halfway'
            ],
            'approving' => [         // 承認中
                'text' => '承認中',
                'class' => 'halfway'
            ],
            'approved' => [          // 承認済
                'text' => '承認済',
                'class' => 'halfway'
            ],
            'ordered' => [           // 発注済
                'text' => '発注済',
                'class' => 'complete'
            ],
        ],
    ],

    'AttachedFilesIcon' => [
        'icon' => [
            'excel' => 'excelIcon',
            'word' => 'wordIcon',
            'cad' => 'cadIcon',
            'pdf' => 'pdfIcon',
            'etcetera' => 'etceteraIcon',
        ],
        'extension' => [
            'excel' => ['xls', 'xlsx', 'xlsm'],
            'word'  => ['docx', 'docm', 'doc'],
            'cad'   => ['dwg', 'dxf', 'jwc', 'sxf'],
            'pdf'   => 'pdf',
            // 'other' => 'other'
        ]
    ],

    'quoteRequestListCreationStatus' => [
        // ステータス
        'status' => [
            'not_treated' => 'not_treated',
            'making' => 'making',
            'editing' => 'editing',
            'complete' => 'complete',
        ],
        'not_treated' => [
            'text' => '未処理',
            'class' => 'not-treated'
        ],
        'making' => [
            'text' => '作成中',
            'class' => 'making'
        ],
        'editing' => [
            'text' => '編集中',
            'class' => 'editing'
        ],
        'complete' => [
            'text' => '積算済',
            'class' => 'complete'
        ]
    ],

    'quoteListStatus' => [
        // ステータス
        'status' => [
            'editing' => 'editing',
            'applying' => 'applying',
            'approved' => 'approved',
            'sendback' => 'sendback',
        ],
        'editing' => [
            // 'text' => '作成中',
            'class' => 'editing'
        ],
        'applying' => [
            // 'text' => '申請中',
            'class' => 'applying'
        ],
        'approved' => [
            // 'text' => '承認済',
            'class' => 'approved'
        ],
        'sendback' => [
            // 'text' => '差戻',
            'class' => 'sendback'
        ]
    ],

    'quoteRequestListKbn' => [
        // ステータス
        'status' => [
            'not_treated' => 'not_treated',
            'making' => 'making',
            'complete' => 'complete',
        ],
        'not_treated' => ['class' => 'not-treated'],
        'making' => ['class' => 'making'],
        'complete' => ['class' => 'complete']
    ],

    // 見積0版
    'quoteCompVersion' => [
        'number' => 0,
        'caption' => '完成見積'
    ],
    // 見積階層分類種別
    'quoteLayerClassType' => [
        'construction' =>  1,
        'big' => 2,
        'middle' => 3,
        'small' => 4
    ],

    // 倉庫マスタ　所有者区分
    'ownerKbn' => [
        'company' => 1,     // 自社倉庫
        'supplier' => 2,    // 仕入先倉庫
    ],
    // 倉庫マスタ　トラックフラグ
    'truckFlg' => [
        'warehouse' => 0,   // 倉庫
        'truck' => 1,       // トラック（自社のみ）
    ],

    // 棚番マスタ　棚種別
    'shelfKind' => [
        'normal' => 0,      // 通常棚
        'temporary' => 1,   // 入荷一時置き場
        'keep' => 2,   // 預かり品
        'return' => 3,      // 返品棚
    ],

    // 仕入先マスタ メーカー区分
    'supplierMakerKbn' => [
        'direct' => 0,
        'supplier' => 1,
        'maker' => 2,

        'list' => [
            0 => 'メーカー直接取引',
            1 => '仕入先',
            2 => 'メーカー',
        ],
    ],

    // 商品単価マスタ 単価区分
    'unitPriceKbn' => [
        'normal' => 0,
        'campaign' => 1,
        'cutomer_normal' => 2,
        'cutomer_special' => 3,
    ],
    // 商品単価マスタ 仕入販売区分
    'costSalesKbn' => [
        'cost' => 1,
        'sales' => 2,
    ],

    // 無限ループ防止用
    'judgeUpperLimit' => [
        'parent_department' => 99,
    ],

    // 全承認権限者の承認順序は9999固定
    'fullApprovalOrder' => 9999,

    // 承認の処理区分
    'approvalProcessKbn' => [
        'quote' => 't_quote_version',
        'order' => 't_order',
    ],
    // 出荷納品一覧
    'shippingDeliveryStatus' => [
        // ステータス
        'status' => [
            'not_arrival' => 'not_arrival',
            'part_arrival' => 'part_arrival',
            'done_arrival' => 'done_arrival',
            'stock_reserve' => 'stock_reserve',

            'not_shipping' => 'not_shipping',
            'part_shipping' => 'part_shipping',
            'done_shipping' => 'done_shipping',
            'take_off' => 'take_off',

            'not_delivery' => 'not_delivery',
            'part_delivery' => 'part_delivery',
            'done_delivery' => 'done_delivery',
            'rtn_delivery' => 'rtn_delivery',
        ],
        // 入荷状況
        'arrival' => [
            'not_arrival' => [
                'text' => '未入荷',
                'class' => 'not-arrival',
            ],
            'part_arrival' => [
                'text' => '一部入荷',
                'class' => 'part-arrival',
            ],
            'done_arrival' => [
                'text' => '入荷済',
                'class' => 'done-arrival',
            ],
            'stock_reserve' => [
                'text' => '引当/預',
                'class' => 'stock-reserve',
            ],
        ],
        // 出荷状況
        'shipping' => [
            'not_shipping' => [
                'text' => '未処理',
                'class' => 'not-shipping',
            ],
            'part_shipping' => [
                'text' => '一部出荷',
                'class' => 'part-shipping',
            ],
            'done_shipping' => [
                'text' => '出荷済',
                'class' => 'done-shipping',
            ],
            'take_off' => [
                'text' => '引取',
                'class' => 'take-off',
            ],
        ],
        // 納品状況
        'delivery' => [
            'not_delivery' => [
                'text' => '未処理',
                'class' => 'not-delivery',
            ],
            'part_delivery' => [
                'text' => '一部納品',
                'class' => 'part-delivery',
            ],
            'done_delivery' => [
                'text' => '完納',
                'class' => 'done-delivery',
            ],
            'rtn_delivery' => [
                'text' => '返品',
                'class' => 'rtn-delivery',
            ],
        ],
    ],
    // 入荷
    'arrivalStatus' => [
        'val' => [
            'not_arrival' => 0,
            'complete' => 1,
            'done_arrival' => 1,
            'part_arrival' => 2,
        ],
        'list' => [
            0 => '未入荷',
            1 => '入荷済',
            2 => '一部入荷',
        ],
        // 入荷状況
        'status' => [
            'not_arrival' => [
                'text' => '未入荷',
                'class' => 'not-arrival',
            ],
            'part_arrival' => [
                'text' => '一部入荷',
                'class' => 'part-arrival',
            ],
            'done_arrival' => [
                'text' => '入荷済',
                'class' => 'done-arrival',
            ],
        ],
    ],
    // 出荷
    'shippingStatus' => [
        'val' => [
            'not_shipping' => 0,
            'complete' => 1,
            'part_shipping' => 2,
        ],
        'list' => [
            0 => '未出荷',
            1 => '出荷済',
            2 => '一部出荷',
        ],
    ],
    // 納品
    'deliveryStatus' => [
        'val' => [
            'not_delivery' => 0,
            'complete' => 1,
            'part_delivery' => 2,
        ],
        'list' => [
            0 => '未納品',
            1 => '納品済',
            2 => '一部納品',
        ],
    ],
    // 入荷一覧　ステータス
    'arrivalListStatus' => [
        // ステータス
        'status' => [
            'not_arrival' => 'not_arrival',
            'part_arrival' => 'part_arrival',
            'done_arrival' => 'done_arrival',
        ],
        // 入荷状況
        'not_arrival' => [
            'text' => '未入荷',
            'class' => 'not-arrival',
        ],
        'part_arrival' => [
            'text' => '一部入荷',
            'class' => 'part-arrival',
        ],
        'done_arrival' => [
            'text' => '入荷済',
            'class' => 'done-arrival',
        ],
    ],
    // 出荷制限情報アイコン
    'shippingLimit' => [
        'heavy' => [
            'text' => '重量物',
            'class' => 'heavy-icon',
        ],
        'rain' => [
            'text' => '雨延期',
            'class' => 'rain-icon',
        ],
        'unic' => [
            'text' => '要ユニック',
            'class' => 'unic-icon',
        ],
        'transport' => [
            'text' => '小運搬',
            'class' => 'transport-icon',
        ],
    ],

    // 出荷制限情報リスト
    'shippingLimitList' => [
        'heavy_flg' => [
            'text' => '重量物',
            'val' => '1',
        ],
        'unic_flg' => [
            'text' => '要ユニック',
            'val' => '2',
        ],
        'rain_flg' => [
            'text' => '雨延期',
            'val' => '3',
        ],
        'transport_flg' => [
            'text' => '小運搬有',
            'val' => '4',
        ],
    ],
    // 受発注一覧 進捗状況
    'orderListStatus' => [
        // ステータス
        'status' => [
            'not_treated' => 'not_treated',
            'editing' => 'editing',
            'applying' => 'applying',
            'approving' => 'approving',
            'approved' => 'approved',
            'ordered' => 'orderd',
            'sendback' => 'sendback',
            'reserved' => 'reserved',
        ],
        // 発注ステータスにあるものは同じ数字にする
        'statusVal' => [
            'not_treated' => -1,
            'editing' => 0,
            'applying' => 1,
            'approving' => 2,
            'approved' => 3,
            'ordered' => 4,
            'sendback' => 5,
            'reserved' => 6,
        ],
        'statusKey' => [
            -1 => [
                'text' => '未処理',
                'class' => 'not-treated'
            ],
            0 => [
                'text' => '編集中',
                'class' => 'editing'
            ],
            1 => [
                'text' => '',
                'class' => 'editing'
            ],
            2 => [
                'text' => '',
                'class' => 'editing'
            ],
            3 => [
                'text' => '未発注',
                'class' => 'not-ordering'
            ],
            4 => [
                'text' => '発注済',
                'class' => 'ordered'
            ],
            5 => [
                'text' => '',
                'class' => 'editing'
            ],
            6 => [
                'text' => '引当',
                'class' => 'reserved'
            ],
        ],
        'not_treated' => [       // 未処理
            'text' => '未処理',
            'class' => 'not-treated'
        ],
        'editing' => [             // 編集中
            'text' => '編集中',
            'class' => 'editing'
        ],
        'not_ordering' => [            // 未発注
            'text' => '未発注',
            'class' => 'not-ordering'
        ],
        'ordered' => [            // 発注済
            'text' => '発注済',
            'class' => 'ordered'
        ],
        'reserved' => [           // 引当済
            'text' => '引当',
            'class' => 'reserved'
        ],
    ],
    // 営業日区分
    'businessdayKbn' => [
        'val' => [
            'businessday'   => 1,
            'holiday'       => 2,
            'vacation'      => 3,
        ],
        'list' => [
            1 => '営業日',
            2 => '休業日',
            3 => '特別休暇',
        ],
    ],
    // カレンダー画面用の設定
    'calendarScreenSetting' => [
        'lock_manage'   => [
            'lock_key'  =>  1,
        ],
        // 必須項目
        'repeat_kbn_ctrl' => [
            'default' => [
                'year'  => 1,
                'month' => 1,
                'day'   => 1,
                'week'  => 0,
                'week_number'=> 0,
            ],
            'week' => [
                'year'  => 0,
                'month' => 0,
                'day'   => 0,
                'week'  => 1,
                'week_number'=> 0,
            ],
            'month' => [
                'year'  => 0,
                'month' => 0,
                'day'   => 1,
                'week'  => 0,
                'week_number'=> 0,
            ],
            'month_week_day' => [
                'year'  => 0,
                'month' => 0,
                'day'   => 0,
                'week'  => 1,
                'week_number'=> 1,
            ],
            'year' => [
                'year'  => 0,
                'month' => 1,
                'day'   => 1,
                'week'  => 0,
                'week_number'=> 0,
            ],
            'year_month_week_day' => [
                'year'  => 0,
                'month' => 1,
                'day'   => 0,
                'week'  => 1,
                'week_number'=> 1,
            ],
        ],
        'add_year_end'      => 1,
        'month_cnt'         => 12,
        'day_of_week_cnt'   => 7,
        'default_start_year'=> 2000,
    ],
    // カレンダー曜日
    'calendarWeek'  => [
        'val'   => [
            'sunday'    => 0,
            'monday'    => 1,
            'tuesday'   => 2,
            'wednesday' => 3,
            'thursday'  => 4,
            'friday'    => 5,
            'saturday'  => 6,
        ],
        'list'  => [
            0       => '日',
            1       => '月',
            2       => '火',
            3       => '水',
            4       => '木',
            5       => '金',
            6       => '土',
        ],
        'text' => [
            'sunday'    => '日',
            'monday'    => '月',
            'tuesday'   => '火',
            'wednesday' => '水',
            'thursday'  => '木',
            'friday'    => '金',
            'saturday'  => '土',
        ],
    ],
    // カレンダーの休日区分
    'calendarKbn'  => [
        'val'   => [
            'default'           => 0,
            'public_holiday'    => 1,
            'holiday'           => 2,
            'leave'             => 3,
        ],
        'list'  => [
            0       => '通常休み',
            1       => '祝日',
            2       => '祭日',
            3       => '特別休日',
        ],
    ],
    // カレンダーの繰り返し区分
    'calendarRepeatKbn'  => [
        'val'   => [
            'default'           => 0,
            'week'              => 1,
            'month'             => 2,
            'month_week_day'    => 3,
            'year'              => 4,
            'year_month_week_day'   => 5,
        ],
        'list'  => [
            0       => 'なし',
            1       => '毎週',
            2       => '毎月(日)',
            3       => '毎月(曜日)',
            4       => '毎年(月日)',
            5       => '毎年(月/曜日)',
        ],
    ],
    // エクセルテンプレートのパス
    'templatePath' => 'assets/template',
    // エクセルテンプレート名
    'excelTemplateName' => [
        'arrivalList' => 'arrival_list.xlsx',   // 入荷一覧
        'salesDetailList' => 'sales_detail_list.xlsx',   // 入荷一覧
        'shippingDeliveryList' => 'shipping_delivery_list.xlsx',   // 入荷一覧
    ],
    // 見積階層の工事区分の情報（初期値セット）
    'quoteConstructionInfo' => [
        'id' => 0,
        'construction_id' => 0,
        'layer_flg' => 1,
        'parent_quote_detail_id' => 0,
        'seq_no' => 1,
        'depth' => 0,
        'tree_path' => 0,
        'set_flg' => 0,
        'sales_use_flg' => 0,
        'product_name' => '',
        'quote_quantity' => 1,
        'min_quantity' => 1,
        'order_lot_quantity' => 1,
        'set_kbn' => 0,
        'row_print_flg' => 1,
        'price_print_flg' => 1,
        'add_flg' => 0,
        'top_flg' => 0,
        'header' => '',
        'filter_tree_path' => 0,
        'to_layer_flg' => 0,
        'items' => [],
    ],
    // 見積階層の区切り文字
    'treePathSeparator' => '_',
    // 見積階層のトップのデータ
    'topTreeData' => [
        'header'    => 'トップ',
        'top_flg'   => 1,
        'layer_flg' => 1,
        'items'     => [],
    ],
    // QRレイアウト
    'qrLabelPath' => 'template/format/',
    'printer' => [
        'port' => 8080,
        'printTemplateName' => 'qr_label.spfmtz',               // QRラベル
        'printDeliveryFileName' => 'delivery_label.spfmtz',     // 納品書ラベル
        'printCounterSalesName' => 'countersales_label.spfmtz', // 領収書ラベル
    ],
    // 税端数処理区分
    'taxRounding' => [
        'val' => [
            'round_down' => 0,
            'round_up' => 1,
            'round' => 2,
        ],
        'list' => [
            0 => '切り捨て',
            1 => '切り上げ',
            2 => '四捨五入',
        ]
    ],
    // 見積書
    'quoteReport' => [
        'file_prefix' => '【見積書】',
        'number_of_lines' => 18,
        'product_name' => [
            'subtotal' => '【小計】',
            'total' => '【合計】',
        ],
        'jpn_key' => [
            'stamp' => [
                'parentkey' => '印鑑',
                'company_stamp' => '社印',
                'stamp1' => '承認者1',
                'stamp2' => '承認者2',
                'stamp3' => '承認者3',
            ],
            'company' => [
                'parentkey' => '会社情報',
                'name' => '社名',
                'zipcode' => '郵便番号',
                'address1' => '住所1',
                'address2' => '住所2',
                'tel' => 'TEL',
                'fax' => 'FAX',
            ],
            'basic' => [
                'parentkey' => '基本情報',
                'quote_date' => '見積日',
                'quote_no' => '見積No',
                'customer_name' => '顧客名',
                'total_amount_tax' => '税込合計金額',
                'total_amount' => '税抜合計金額',
                'tax_amount' => '消費税額',
                'construction_subject' => '工事件名',
                'construction_period' => '工事期間',
                'address' => '工事場所',
                'construction_outline' => '工事概要',
                'payment_condition' => '支払条件',
                'limit_date' => '有効期限',
                'comment' => 'コメント',
            ],
            'detail' => [
                'parentkey' => '内訳',
                'product_info_r1' => '商品情報1',
                'product_info_r2' => '商品情報2',
                'product_name' => '商品名',
                'model' => '規格',
                'unit' => '単位',
                'quote_quantity' => '数量',
                'sales_unit_price' => '単価',
                'sales_total' => '金額',
                'regular_price' => '定価',
                'memo' => '備考',
            ]
        ],
    ],
    // 発注書
    'orderReport' => [
        'number_of_lines' => 13,
        'jpn_key' => [
            'basic' => [
                'parentkey' => '基本情報',
                'order_no' => '注文No', 'company' => '会社名', 'department' => '担当部門名',
                'supplier' => '仕入先名', 'maker' => 'メーカー名', 'order_date' => '発行年月日',
                'chief_staff' => '責任者', 'sales_staff' => '営業担当者', 'order_staff' => '発注担当者'
            ],
            'shipping' => [
                'parentkey' => '配送情報',
                'delivery_name' => '送り先名', 'tel' => '営業担当部門連絡先', 'delivery_address' => '送り先住所',
                'customer' => '得意先名', 'site' => '現場名', 'memo' => '備考',
            ],
            'detail' => [
                'parentkey' => '内訳',
                'no' => 'No', 'product_code' => '商品コード', 'product_name' => '商品名',
                'model' => '規格', 'order_quantity' => '数量', 'unit' => '単位', 'cost_unit_price' => '仕入単価',
                'desired_delivery_date' => '納期依頼', 'delivery_date' => '納期回答', 'memo' => '備考',
            ]
        ],
    ],
    // 倉庫移動種別
    'moveKind' => [
        'transfer' => 0,
        'redelivery' => 1,
        'return' => 2,
        'warehouse_transfer' => 3,

        'convert' => 5,
    ],
    // 入出庫T　移動種別
    'productMoveKind' => [
        'arrival' => 1,     // 入荷
        'shipment' => 2,    // 出荷
        'warehouseMove' => 3,   // 倉庫移動
        'return' => 4,      // 返品

        'discard' => 7,     // 廃棄
    ],
    // 倉庫移動種別
    'warehouseMoveKind' => [
        'val' => [
            'warehouse' => 0,   // 倉庫移動
            'redelivery' => 1,  // 再配送
            'return' => 2,      // 返品
        ],
        'text' => [
            0 => '倉庫移動受入',
            1 => '荷積受入',
            2 => '返品受入',
        ],
    ],
    'discardKind' => [
        'return' => 0,  // 返品
        'check' => 1,   // 棚卸
    ],
    // 倉庫移動 ステータス
    'warehouseMoveStatus' => [
        'val' => [
            'not_move' => 0,
            'moving' => 1,
            'moved' => 2,
        ],
        'list' => [
            0 => '未処理',
            1 => '移動中',
            2 => '移管済',
        ],
        'status' => [
            'not_move' => 'not_move',
            'moving' => 'moving',
            'moved' => 'moved',
        ],
        'not_move' => [
            'text' => '未処理',
            'class' => 'not-move',
        ],
        'moving' => [
            'text' => '移動中',
            'class' => 'moving',
        ],
        'moved' => [
            'text' => '移管済',
            'class' => 'moved',
        ],
    ],
    // 返品承認 ステータス
    'returnApprovalStatus' => [
        'val' => [
            'not_approval' => 0,
            'approvaled' => 1,
            'rejection' => 2,
        ],
        'list' => [
            0 => 'not_approval',
            1 => 'approvaled',
            2 => 'rejection',
        ],
        'status' => [
            'not_approval' => 'not_approval',
            'approvaled' => 'approvaled',
            'rejection' => 'rejection',
        ],
        'not_approval' => [
            'text' => '未承認',
            'class' => 'not-approval',
        ],
        'approvaled' => [
            'text' => '承認済',
            'class' => 'approvaled',
        ],
        'rejection' => [
            'text' => '却下',
            'class' => 'rejection',
        ],
    ],
    'returnKbn' => [
        'keep' => 1,    // 預かり品
        'stock' => 2,   // 自社在庫
        'maker' => 3,   // メーカー返品
        'discard' => 4, // 廃棄

        'status' => [
            'keep' => '預かり品',
            'stock' => '自社在庫化',
            'maker' => 'メーカー返品',
            'discard' => '廃棄',
        ],
    ],
    // 在庫種別
    'stockFlg' => [
        // 値
        'val' => [
            'order' => 0,
            'stock' => 1,
            'keep'  => 2,
        ],
        // 表示名
        'list' => [
            0 => '発注引当',
            1 => '在庫引当',
            2 => '預かり品',
        ]
    ],
    // 納品完了フラグ
    'deliveryFinishFlg' => [
        // 値
        'val' => [
            'unfinished' => 0,
            'complete' => 1,
            'redelivery'  => 2,
        ],
        // 表示名
        'list' => [
            0 => '未完',
            1 => '納品完了',
            2 => '再配送',
        ]

    ],
    // 変更履歴
    'updateHistoryKbn' => [
        'val' => [
            'quote_detail' => 1,
            'order_detail' => 2,
        ],
        'list' => [
            1 => 'quote_detail',
            2 => 'order_detail',
        ],
    ],
    // 発注明細-追加区分
    'orderDetailAddKbn' => [
        'val' => [
            'not_add' => 0,
            'order' => 1,
            'order_detail' => 2,
        ]
    ],
    // フォーマット区分（大分類マスタ）
    'classBigFormatKbn' => [
        'val' => [
            'none' => 0,
            'wood' => 1,
        ],
        'list' => [
            0 => 'なし',
            1 => '木材',
        ],
    ],
    // 得意先担当者設定(承認ステータス)
    'customerStaffApprovalStatus' => [
        'val' => [
            'not_approval' => 0,
            'approvaled' => 1,
            'rejection' => 2,
        ],
        'status' => [
            'not_approval' => 'not_approval',
            'approvaled' => 'approvaled',
            'rejection' => 'rejection',
        ],
        'not_approval' => [
            'class' => 'not-approval',
            'text' => '未承認',
        ],
        'approvaled' => [
            'class' => 'approvaled',
            'text' => '承認済',
        ],
        'rejection' => [
            'class' => 'rejection',
            'text' => '否認',
        ],
        'list' => [
            0 => '未承認',
            1 => '承認済',
            2 => '否認',
        ],
    ],
    // 商品マスタフォーマット
    'productCsvHeader' => [
        'format_kbn' => [
            'product' => [
                'val' => 0,
                'txt' => 'product',
            ],
            'beeConnect' => [
                'val' => 1,
                'txt' => 'beeConnect',
            ],
        ],
        // 商品マスタフォーマット
        'productHeader' => [
            'product_code' => '商品コード',
            'product_name' => '商品名',
            'product_short_name' => '商品名略称',
            'class_big_id' => '大分類ID',
            'class_middle_id' => '中分類ID',
            'construction_id_1' => '工事区分ID1',
            'construction_id_2' => '工事区分ID2',
            'construction_id_3' => '工事区分ID3',
            'class_small_id_1' => '工程ID1',
            'class_small_id_2' => '工程ID2',
            'class_small_id_3' => '工程ID3',
            'set_kbn' => 'セット区分',
            'model' => '型式/規格',
            'tree_species' => '樹種',
            'grade' => '等級',
            'length' => '長さ',
            'thickness' => '厚み',
            'width' => '幅',
            'weight' => '重量',
            'maker_id' => 'メーカーID',
            'price' => '定価',
            'open_price_flg' => 'オープンフラグ',
            'min_quantity' => '最小単位数量',
            'stock_unit' => '管理数単位',
            'quantity_per_case' => '入り数',
            'lead_time' => 'リードタイム(日)',
            'order_lot_quantity' => '発注ロット数',
            'purchase_makeup_rate' => '仕入掛率',
            'normal_purchase_price' => '標準仕入単価',
            'unit' => '単位',
            'sales_makeup_rate' => '販売掛率',
            'normal_sales_price' => '標準販売単価',
            'normal_gross_profit_rate' => '標準粗利率',
            'start_date' => '適用開始日',
            'end_date' => '適用終了日',
            'warranty_term' => '保障期間',
            'housing_history_transfer_kbn' => '住宅履歴転送区分',
            'new_product_id' => '後継商品コード',
            'memo' => '備考',
            'intangible_flg' => '無形品フラグ',
        ],
        // Bee-Connect共通フォーマット
        'beeConnectHeader' => [
            'product_code' => '品番',
            'product_name' => '品名',
            // 'class_big_id' => '大分類ID',
            'class_middle_id' => '中分類',
            // 'construction_id_1' => '工事区分ID1',
            // 'class_small_id_1' => '工程ID1',
            'set_kbn' => 'データ区分',
            'model' => '規格',
            'maker_id' => 'メーカー名',
            'price' => '単価',
            'quantity_per_case' => '入数',
            'unit' => '単位',
            'memo' => '摘要／備考',
        ],



    ],

    // 売上明細区分(TODO:sales_flg)
    'salesDetailFlg' => [
        // 値
        'val' => [
            'quote'     => 0,
            'nebiki'    => 1,
            'sales'     => 2,
            'cost_adjust'   => 3,
        ],
        // 表示名
        'list' => [
            0 => '見積明細',
            1 => '値引',
            2 => '新規売上',
            3 => '仕入調整',
        ],
    ],
    // 売上種別
    'salesFlg' => [
        // 値
        'val' => [
            'delivery'      => 0,
            'not_delivery'  => 1,
            'not_sales'     => 2,
            'return'        => 3,
            'discount'      => 4,
            'production'    => 5,
            'cost_adjust'   => 7,
        ],
        // 表示名
        'list' => [
            0 => '納品',
            1 => '未納',
            2 => '未売',
            3 => '返品',
            4 => '値引き',
            5 => '出来高',
            7 => '調整',
        ],
        'data' => [
            [
                'val' => 0,
                'text' => '納品'
            ],
            [
                'val' => 1,
                'text' => '未納'
            ],
            [
                'val' => 2,
                'text' => '未売'
            ],
            [
                'val' => 3,
                'text' => '返品'
            ],
            [
                'val' => 4,
                'text' => '値引き'
            ],
            [
                'val' => 5,
                'text' => '出来高'
            ],
            [
                'val' => 7,
                'text' => '調整'
            ],
        ],
        'quote_data' => [
            [
                'val' => 1,
                'text' => '未納'
            ],
            [
                'val' => 2,
                'text' => '未売'
            ],
        ]
    ],
    // 売上状況
    'salesStatus' => [
        // 値
        'val' => [
            'not_applying'  => 0,
            'applying'  => 1,
            'approved'  => 2,
            //'sendback'  => 3,
        ],
        // 表示名
        'list' => [
            0 => '未申請',
            1 => '申請中',
            2 => '承認済',
            //3 => '否認',
        ],
    ],
    // 売上用のその他案件情報
    'salesMatterInfo' =>[
        'val' => [
            'owner_name' => 'その他',
            'architecture_type' => 0
        ]
    ],

    // 形品区分
    'intangible_kbn' => [
        'val' => [
            'tangible' => 0,    // 有形品
            'intangible' => 1,  // 無形品
        ],

        'list' => [
            0 => '有形品',
            1 => '無形品',
        ]
    ],
    // 請求状況
    'requestStatus' => [
        // 値
        'val' => [
            'unprocessed'   => 0,
            'complete'      => 1,
            'request_complete'  => 2,
            'close'         => 3,
            'release'       => 4,
            'temporary_delete' => 5,
        ],
        // 表示名
        'list' => [
            0 => '未処理',
            1 => '売上確定',
            2 => '請求書作成済',
            3 => '締め済',
            4 => '締め解除',
            5 => '一時削除'
        ],
    ],

    // 得意先の締め日
    'customerClosingDay' => [
        // 値
        'val' => [
            'any_time'  => 0,
            'month_end' => 99,
        ],
        // 表示名
        'list' => [
            0           => '随時',
            99          => '月末',
        ],
    ],
    
    // 得意先回収日
    'customerCollectionDay' => [
        // 値
        'val' => [
            'any_time'  => 0,
            'month_end' => 99,
        ],
        // 表示名
        'list' => [
            0           => '随時',
            99          => '月末',
        ],
    ],

    // 請求発送予定日を算出するときの加算日数
    'shipmentAtAddDay'  => 3,

    // 売上区分
    'sales_category' => [
        // 値
        'val' => [
            'sales'         => 0,
            'counter_sales' => 1,
        ],
        // 表示名
        'list' => [
            0 => '売上',
            1 => '現金売上',
        ]
    ],

    // 売上種別
    'sales_flg' => [
        // 表示名
        'list' => [
            0 => '納品',
            1 => '未納',
            2 => '未売',
            3 => '返品',
            4 => '値引き',
            5 => '出来高',
        ]
    ],
    
    // 未納調整フラグ
    'notdeliveryFlg' => [
        // 値
        'val' => [
            'default'   => 0,
            'create'    => 1,
            'invalid'   => 2,
        ],
        // 表示名
        'list' => [
            0 =>        '通常',
            1 =>        '相殺用データ',
            2 =>        '無効',
        ],
    ],
    // 売上明細画面の絞り込み
    'salesDetailFilterInfo' =>[
        'PRE' =>[
            'key'=>[
                'delivery'      => 0,
                'not_delivery'  => 1,
                'not_sales'     => 2,
                'return'        => 3,
                'discount'      => 4,
                'production'    => 5,
                'cost_adjust'   => 7,
            ],
            'val'=>[
                0   => '納品',
                1   => '未納',
                2   => '未売',
                3   => '返品',
                4   => '値引き',
                5   => '出来高',
                7   => '仕入調整',
            ]
        ],
        'ACTIVE' =>[
            'key'=>[
                'delivery'      => 0,
                'not_delivery'  => 1,
                'not_sales'     => 2,
                'return'        => 3,
                'discount'      => 4,
                'production'    => 5,
                'cost_adjust'   => 7,
            ],
            'val'=>[
                0 =>    '納品',
                1 =>    '未納',
                2 =>    '未売',
                3 =>    '返品',
                4 =>    '値引き',
                5 =>    '出来高',
                7 =>    '仕入調整',
            ]
        ],
        'OTHER' =>[
            'key'=>[
                'sales_undecided'   => 100,
                'application'       => 200,
                'zero_sales'        => 300,
                'cost_edit'         => 400,
            ],
            'val'=>[
                100 =>  '売上未確定',
                200 =>  '申請中',
                300 =>  '売価ゼロ',
                400 =>  '仕入調整',
            ]
        ],
    ],

    // 仕入種別
    'purchaseType' => [
        // 表示名
        'list' => [
            0 => '商品外値引',
            1 => 'リベート',
            2 => 'SR協賛金',
            3 => '差入保証金',
            4 => '雑収入',
            5 => '仕入外請求',
        ],
        // 値
        'val' => [
            'outside_product' => 0,
            'rebate' => 1,
            'sponsor' => 2,
            'warranty' => 3,
            'income' => 4,
            'request' => 5,
        ],
    ],  

    // 支払予定[ステータス]
    'paymentStatus' => [
        // 表示名
        'list' => [
            0 => '未確定',
            1 => '確定済',
            2 => '申請中',
            3 => '承認済',
            4 => '支払済',
            5 => '締め済',
        ],
        // 値
        'val' => [
            'unsettled'     => 0,   // 未確定
            'confirm'       => 1,   // 確定済
            'applying'      => 2,   // 申請中
            'approvaled'    => 3,   // 承認済
            'paid'          => 4,   // 支払済
            'closing'       => 5,   // 締め済
        ],
    ],

    // 支払申請[ステータス]
    'paymentRequestStatus' => [
        // 表示名
        'list' => [
            0 => '第一承認待ち',
            1 => '第二承認待ち',
            2 => '第三承認待ち',
            3 => '承認済',
            4 => '差戻',
        ],
        // 値
        'val' => [
            'not_approval' => 0,
            'approval1' => 1,
            'approval2' => 2,
            'approval3' => 3,
            'sendBack' => 4,
        ],
    ],

    // 商品オートコンプリートの設定
    'productAutoCompleteSetting' => [
        'max_list_count'=>1000,
    ],

    'productCodeRegex' => '/^[0-9a-zA-Z\\/\\.#&\\*,\\$\\+\\-=\\(\\)\\[\\]_@\\? ]*$/',

    'accountType' => [
        'list' => [
            1 => '普通',
            2 => '当座',
        ],
    ],

    'safetyFeeType' => [
        'val' => [
            'roof' => 0,    // ルーフ
            'other' => 1,   // その他(仕入)
        ],

        'list' => [
            0 => 'ルーフ',
            1 => 'その他(仕入)',
        ]
    ],

    'creditedStatus' => [
        // 値
        'val' => [
            'unsettled' => 0,       // 未確定
            'miscalculation' => 1,  // 違算有
            'transferred' => 2,     // 繰越済
            'payment' => 3,         // 入金済
            'discount' => 4,        // 値引き申請    
        ],
    ],

    'returnFinish' => [
        'val' => [
            'unfinished' => 0,  // 未完
            'completed' => 1,   // 完了
            'cancel' => 2,      // 取消
        ],  

    ],
    // 基準日種別
    'classSmallBaseDateType' => [
        'val' => [
            'construction_date' => 1,       // 着工日 
            'ridgepole_raising_date' => 2,    // 上棟日
        ],
        'list' => [
            1 => '着工日',
            2 => '上棟日',
        ]
    ],
    // タスク種別（t_matter_detail.type ≠ DHTMLX.GANTTのgantt.config.types）
    'matterTaskType' => [
        'val' => [
            'milestone' => 1,       // マイルストーン 
            'construction' => 2,    // 工事区分
            'process' => 3,         // 工程
            'detail' => 4,          // 明細
            'order_timing' => 11,   // 発注タイミング
            'hope_arrival_plan_date' => 12,    // 希望出荷予定日
        ],
        // 'list' => []
    ],
    // リンク種別（t_matter_detail_link.type = DHTMLX.Ganttのgantt.config.links）
    'ganttLinkType' => [
        'val' => [
            'finish_to_start' => 1,   // 終点から始点 
            'start_to_start' => 2,    // 始点から始点
            'finish_to_finish' => 3,  // 終点から終点
            'start_to_finish' => 4,   // 始点から終点
        ],
        // 'list' => []
    ],

    // 木材立米 掛率
    'unitPriceRate' => [
        'purchase' => 0.94,
        'sales' => 0.7,
    ],

    // 売上に反映する納品/返品の有効化日　指定日から有効になる
    'salesDeliveryValidDate'    => '2021/06/01',            // 納品日
    'salesReturnValidDate'      => '2021/06/01 00:00:00',   // 倉庫移動.入庫処理日時

    // 仕入に表示する入荷/返品の有効日
    'purchaseArrivalValidDate'   => '2022/07/01',           // 入荷日
    'purchaseReturnValidDate'    => '2022/07/01 00:00:00',  // 返品日


    'shipmentKind' => [
        'val' => [
            'matter' => 0,
            'customer' => 1,
            'customer_branch' => 2,
            'maker' => 3,
            'takeoff' => 4,
        ],
        'list' => [
            0 => '案件現場',
            1 => '得意先',
            2 => '作業場',
            3 => 'メーカー倉庫',
            4 => '引取',
        ],
    ],

    'warehouseMoveFinishFlg' => [
        'val' => [
            'unfinished' => 0,  // 未完
            'issued' => 1,   // 出庫済
            'completed' => 2,   // 完了
        ],
    ],
];
