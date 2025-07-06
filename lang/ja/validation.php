<?php

return [

    /*
    |--------------------------------------------------------------------------
    | バリデーション言語行
    |--------------------------------------------------------------------------
    |
    | 以下の言語行はバリデータクラスによって使用されるデフォルトのエラーメッセージです。
    | サイズルールのように、いくつかのルールは複数のバージョンを持っています。
    | ここで、それぞれのメッセージを調整してください。
    |
    */

    'accepted' => ':attributeフィールドは受け入れられる必要があります。',
    'accepted_if' => ':otherが:valueの場合、:attributeフィールドは受け入れられる必要があります。',
    'active_url' => ':attributeフィールドは有効なURLである必要があります。',
    'after' => ':attributeフィールドは:date以降の日付である必要があります。',
    'after_or_equal' => ':attributeフィールドは:date以降または同じ日付である必要があります。',
    'alpha' => ':attributeフィールドは文字のみ含むことができます。',
    'alpha_dash' => ':attributeフィールドは文字、数字、ダッシュ、アンダースコアのみ含むことができます。',
    'alpha_num' => ':attributeフィールドは文字と数字のみ含むことができます。',
    'any_of' => ':attributeフィールドは無効です。',
    'array' => ':attributeフィールドは配列である必要があります。',
    'ascii' => ':attributeフィールドは1バイトの英数字記号のみ含むことができます。',
    'before' => ':attributeフィールドは:date以前の日付である必要があります。',
    'before_or_equal' => ':attributeフィールドは:date以前または同じ日付である必要があります。',
    'between' => [
        'array' => ':attributeフィールドは:minから:maxの項目である必要があります。',
        'file' => ':attributeフィールドは:minから:maxキロバイトである必要があります。',
        'numeric' => ':attributeフィールドは:minから:maxである必要があります。',
        'string' => ':attributeフィールドは:minから:max文字である必要があります。',
    ],
    'boolean' => ':attributeフィールドはtrueまたはfalseである必要があります。',
    'can' => ':attributeフィールドには許可されていない値が含まれています。',
    'confirmed' => ':attributeフィールドの確認が一致しません。',
    'contains' => ':attributeフィールドに必要な値がありません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeフィールドは有効な日付である必要があります。',
    'date_equals' => ':attributeフィールドは:dateと同じ日付である必要があります。',
    'date_format' => ':attributeフィールドは:format形式と一致する必要があります。',
    'decimal' => ':attributeフィールドは:decimal小数点以下の桁数である必要があります。',
    'declined' => ':attributeフィールドは拒否される必要があります。',
    'declined_if' => ':otherが:valueの場合、:attributeフィールドは拒否される必要があります。',
    'different' => ':attributeフィールドと:otherは異なる必要があります。',
    'digits' => ':attributeフィールドは:digits桁である必要があります。',
    'digits_between' => ':attributeフィールドは:minから:max桁である必要があります。',
    'dimensions' => ':attributeフィールドの画像サイズが無効です。',
    'distinct' => ':attributeフィールドに重複した値があります。',
    'doesnt_end_with' => ':attributeフィールドは以下のいずれかで終わってはいけません: :values。',
    'doesnt_start_with' => ':attributeフィールドは以下のいずれかで始まってはいけません: :values。',
    'email' => ':attributeフィールドは有効なメールアドレスである必要があります。',
    'ends_with' => ':attributeフィールドは以下のいずれかで終わる必要があります: :values。',
    'enum' => '選択された:attributeは無効です。',
    'exists' => '選択された:attributeは無効です。',
    'extensions' => ':attributeフィールドは以下のいずれかの拡張子である必要があります: :values。',
    'file' => ':attributeフィールドはファイルである必要があります。',
    'filled' => ':attributeフィールドは値を持つ必要があります。',
    'gt' => [
        'array' => ':attributeフィールドは:value個より多い項目である必要があります。',
        'file' => ':attributeフィールドは:valueキロバイトより大きい必要があります。',
        'numeric' => ':attributeフィールドは:valueより大きい必要があります。',
        'string' => ':attributeフィールドは:value文字より多い必要があります。',
    ],
    'gte' => [
        'array' => ':attributeフィールドは:value個以上の項目である必要があります。',
        'file' => ':attributeフィールドは:valueキロバイト以上である必要があります。',
        'numeric' => ':attributeフィールドは:value以上である必要があります。',
        'string' => ':attributeフィールドは:value文字以上である必要があります。',
    ],
    'hex_color' => ':attributeフィールドは有効な16進カラーである必要があります。',
    'image' => ':attributeフィールドは画像である必要があります。',
    'in' => '選択された:attributeは無効です。',
    'in_array' => ':attributeフィールドは:otherに存在する必要があります。',
    'integer' => ':attributeフィールドは整数である必要があります。',
    'ip' => ':attributeフィールドは有効なIPアドレスである必要があります。',
    'ipv4' => ':attributeフィールドは有効なIPv4アドレスである必要があります。',
    'ipv6' => ':attributeフィールドは有効なIPv6アドレスである必要があります。',
    'json' => ':attributeフィールドは有効なJSON文字列である必要があります。',
    'list' => ':attributeフィールドはリストである必要があります。',
    'lowercase' => ':attributeフィールドは小文字である必要があります。',
    'lt' => [
        'array' => ':attributeフィールドは:value個未満の項目である必要があります。',
        'file' => ':attributeフィールドは:valueキロバイト未満である必要があります。',
        'numeric' => ':attributeフィールドは:value未満である必要があります。',
        'string' => ':attributeフィールドは:value文字未満である必要があります。',
    ],
    'lte' => [
        'array' => ':attributeフィールドは:value個以下の項目である必要があります。',
        'file' => ':attributeフィールドは:valueキロバイト以下である必要があります。',
        'numeric' => ':attributeフィールドは:value以下である必要があります。',
        'string' => ':attributeフィールドは:value文字以下である必要があります。',
    ],
    'mac_address' => ':attributeフィールドは有効なMACアドレスである必要があります。',
    'max' => [
        'array' => ':attributeフィールドは:max個以下の項目である必要があります。',
        'file' => ':attributeフィールドは:maxキロバイト以下である必要があります。',
        'numeric' => ':attributeフィールドは:max以下である必要があります。',
        'string' => ':attributeフィールドは:max文字以下である必要があります。',
    ],
    'max_digits' => ':attributeフィールドは:max桁以下である必要があります。',
    'mimes' => ':attributeフィールドは以下のタイプのファイルである必要があります: :values。',
    'mimetypes' => ':attributeフィールドは以下のタイプのファイルである必要があります: :values。',
    'min' => [
        'array' => ':attributeフィールドは:min個以上の項目である必要があります。',
        'file' => ':attributeフィールドは:minキロバイト以上である必要があります。',
        'numeric' => ':attributeフィールドは:min以上である必要があります。',
        'string' => ':attributeフィールドは:min文字以上である必要があります。',
    ],
    'min_digits' => ':attributeフィールドは:min桁以上である必要があります。',
    'missing' => ':attributeフィールドは存在してはいけません。',
    'missing_if' => ':otherが:valueの場合、:attributeフィールドは存在してはいけません。',
    'missing_unless' => ':otherが:valueでない限り、:attributeフィールドは存在してはいけません。',
    'missing_with' => ':valuesが存在する場合、:attributeフィールドは存在してはいけません。',
    'missing_with_all' => ':valuesが存在する場合、:attributeフィールドは存在してはいけません。',
    'multiple_of' => ':attributeフィールドは:valueの倍数である必要があります。',
    'not_in' => '選択された:attributeは無効です。',
    'not_regex' => ':attributeフィールドの形式が無効です。',
    'numeric' => ':attributeフィールドは数値である必要があります。',
    'password' => 'パスワードが正しくありません。',
    'present' => ':attributeフィールドは存在する必要があります。',
    'present_if' => ':otherが:valueの場合、:attributeフィールドは存在する必要があります。',
    'present_unless' => ':otherが:valueでない限り、:attributeフィールドは存在する必要があります。',
    'present_with' => ':valuesが存在する場合、:attributeフィールドは存在する必要があります。',
    'present_with_all' => ':valuesが存在する場合、:attributeフィールドは存在する必要があります。',
    'prohibited' => ':attributeフィールドは禁止されています。',
    'prohibited_if' => ':otherが:valueの場合、:attributeフィールドは禁止されています。',
    'prohibited_unless' => ':otherが:valuesにない限り、:attributeフィールドは禁止されています。',
    'prohibits' => ':attributeフィールドは:otherの存在を禁止しています。',
    'regex' => ':attributeフィールドの形式が無効です。',
    'required' => ':attributeフィールドは必須です。',
    'required_array_keys' => ':attributeフィールドには以下のエントリが含まれている必要があります: :values。',
    'required_if' => ':otherが:valueの場合、:attributeフィールドは必須です。',
    'required_if_accepted' => ':otherが受け入れられた場合、:attributeフィールドは必須です。',
    'required_if_declined' => ':otherが拒否された場合、:attributeフィールドは必須です。',
    'required_unless' => ':otherが:valuesにない限り、:attributeフィールドは必須です。',
    'required_with' => ':valuesが存在する場合、:attributeフィールドは必須です。',
    'required_with_all' => ':valuesが存在する場合、:attributeフィールドは必須です。',
    'required_without' => ':valuesが存在しない場合、:attributeフィールドは必須です。',
    'required_without_all' => ':valuesのいずれも存在しない場合、:attributeフィールドは必須です。',
    'same' => ':attributeフィールドと:otherは一致する必要があります。',
    'size' => [
        'array' => ':attributeフィールドは:size個の項目である必要があります。',
        'file' => ':attributeフィールドは:sizeキロバイトである必要があります。',
        'numeric' => ':attributeフィールドは:sizeである必要があります。',
        'string' => ':attributeフィールドは:size文字である必要があります。',
    ],
    'starts_with' => ':attributeフィールドは以下のいずれかで始まる必要があります: :values。',
    'string' => ':attributeフィールドは文字列である必要があります。',
    'timezone' => ':attributeフィールドは有効なタイムゾーンである必要があります。',
    'unique' => ':attributeは既に使用されています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',
    'uppercase' => ':attributeフィールドは大文字である必要があります。',
    'url' => ':attributeフィールドは有効なURLである必要があります。',
    'ulid' => ':attributeフィールドは有効なULIDである必要があります。',
    'uuid' => ':attributeフィールドは有効なUUIDである必要があります。',

    /*
    |--------------------------------------------------------------------------
    | カスタムバリデーション言語行
    |--------------------------------------------------------------------------
    |
    | ここでは、属性ルールの組み合わせに対するカスタムバリデーションメッセージを
    | 指定できます。これにより、特定の属性ルールに対して特定のカスタム言語行を
    | 迅速に指定できます。
    |
    */

    'custom' => [
        'period' => [
            'in' => '期間は以下のいずれかである必要があります: 1w, 1m, 3m, 6m, 1y, 3y, all',
        ],
        'limit' => [
            'integer' => '制限値は整数である必要があります。',
            'min' => '制限値は1以上である必要があります。',
            'max' => '制限値は100以下である必要があります。',
        ],
        'company_id' => [
            'integer' => '企業IDは整数である必要があります。',
            'exists' => '指定された企業が見つかりません。',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | カスタムバリデーション属性
    |--------------------------------------------------------------------------
    |
    | 以下の言語行は、属性プレースホルダーをより読みやすいものに置き換えるために
    | 使用されます（例：「email」の代わりに「E-Mailアドレス」）。これにより、
    | メッセージがより表現豊かになります。
    |
    */

    'attributes' => [
        'period' => '期間',
        'limit' => '制限値',
        'company_id' => '企業ID',
        'page' => 'ページ',
        'per_page' => '1ページあたりの件数',
        'sort_by' => 'ソート項目',
        'sort_order' => 'ソート順',
        'include_history' => '履歴を含める',
        'history_days' => '履歴取得日数',
    ],

];