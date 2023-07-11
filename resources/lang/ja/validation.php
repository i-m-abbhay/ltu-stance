<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => '承認してください。',
    'active_url'           => '有効なURLを指定してください。',
    'after'                => ':date以降の日付を指定してください。',
    'after_or_equal'       => ':dateかそれ以降の日付を指定してください。',
    'alpha'                => '英字のみからなる文字列を指定してください。',
    'alpha_dash'           => '英数字・ハイフン・アンダースコアのみからなる文字列を指定してください。',
    'alpha_num'            => '英数字のみからなる文字列を指定してください。',
    'array'                => '配列を指定してください。',
    'before'               => ':date以前の日付を指定してください。',
    'before_or_equal'      => ':dateかそれ以前の日付を指定してください。',
    'between'              => [
        'numeric' => ':min〜:maxまでの数値を指定してください。',
        'file'    => ':min〜:max KBのファイルを指定してください。',
        'string'  => ':min〜:max文字の文字列を指定してください。',
        'array'   => ':min〜:max個の要素を持つ配列を指定してください。',
    ],
    'boolean'              => '真偽値を指定してください。',
    'confirmed'            => ':attributeが確認用の値と一致しません。',
    'date'                 => '正しい形式の日付を指定してください。',
    'date_format'          => '":format"という形式の日付を指定してください。',
    'different'            => ':otherとは異なる値を指定してください。',
    'digits'               => ':digits桁の数値を指定してください。',
    'digits_between'       => ':min〜:max桁の数値を指定してください。',
    'dimensions'           => '画像サイズが不正です。',
    'distinct'             => '指定された値は既に存在しています。',
    'email'                => '正しい形式のメールアドレスを指定してください。',
    'exists'               => '指定された値は存在しません。',
    'file'                 => 'ファイルを指定してください。',
    'filled'               => '空でない値を指定してください。',
    'image'                => '画像ファイルを指定してください。',
    'in'                   => ':valuesのうちいずれかの値を指定してください。',
    'in_array'             => ':otherに含まれていません。',
    'integer'              => '整数を指定してください。',
    'ip'                   => '正しい形式のIPアドレスを指定してください。',
    'ipv4'                 => '正しい形式のIPv4アドレスを指定してください。',
    'ipv6'                 => '正しい形式のIPv6アドレスを指定してください。',
    'json'                 => '正しい形式のJSON文字列を指定してください。',
    'max'                  => [
        'numeric' => ':max以下の数値を指定してください。',
        'file'    => ':max KB以下のファイルを指定してください。',
        'string'  => ':max文字以下の文字列を指定してください。',
        'array'   => ':max個以下の要素を持つ配列を指定してください。',
    ],
    'mimes'                => ':valuesのうちいずれかの形式のファイルを指定してください。',
    'mimetypes'            => ':valuesのうちいずれかの形式のファイルを指定してください。',
    'min'                  => [
        'numeric' => ':min以上の数値を指定してください。',
        'file'    => ':min KB以上のファイルを指定してください。',
        'string'  => ':min文字以上の文字列を指定してください。',
        'array'   => ':min個以上の要素を持つ配列を指定してください。',
    ],
    'not_in'               => ':valuesのうちいずれとも異なる値を指定してください。',
    'numeric'              => '数値を指定してください。',
    'present'              => '現在時刻を指定してください。',
    'regex'                => '正しい形式を指定してください。',
    'required'             => '必須です。',
    'required_if'          => ':otherが:valueの時は必須です。',
    'required_unless'      => ':otherが:values以外の時は必須です。',
    'required_with'        => ':valuesのうちいずれかが指定された時は必須です。',
    'required_with_all'    => ':valuesのうちすべてが指定された時は必須です。',
    'required_without'     => ':valuesのうちいずれかがが指定されなかった時は必須です。',
    'required_without_all' => ':valuesのうちすべてが指定されなかった時は必須です。',
    'same'                 => ':otherと一致しません。',
    'size'                 => [
        'numeric' => ':sizeを指定してください。',
        'file'    => ':size KBのファイルを指定してください。',
        'string'  => ':size文字の文字列を指定してください。',
        'array'   => ':size個の要素を持つ配列を指定してください。',
    ],
    'string'               => '文字列を指定してください。',
    'timezone'             => '正しい形式のタイムゾーンを指定してください。',
    'unique'               => 'すでに使われています。',
    'uploaded'             => 'アップロードに失敗しました。',
    'url'                  => '正しい形式のURLを指定してください。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

    /*** 自作 ***/
    'mimes_except'         => ':valuesのファイル形式は無効です。',

];
