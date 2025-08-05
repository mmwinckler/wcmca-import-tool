<?php
/**
 * WooCommerce Multiple Customer Addresses - 柔軟なCSVインポートスクリプト
 * 
 * CSVの列順序は自由で、必須フィールドのみチェックします
 */

require_once 'wp-load.php';

class WCMCA_CSV_Importer {
    
    // 必須フィールド
    private $required_fields = [
        'user_identifier', // user_email, user_id, user_login のいずれか
        'address_1',
        'city',
        'country'
    ];
    
    // フィールドマッピング（CSV列名 => プラグインフィールド名）
    private $field_mapping = [
        // ユーザー識別子（いずれか1つ必須）
        'user_email' => 'user_email',
        'user_id' => 'user_id',
        'user_login' => 'user_login',
        'email' => 'user_email', // エイリアス
        'ユーザーメール' => 'user_email',
        'ユーザーID' => 'user_id',
        
        // 住所タイプ
        'type' => 'type',
        'address_type' => 'type',
        'タイプ' => 'type',
        
        // 住所名
        'address_name' => 'address_name',
        'address_internal_name' => 'address_name',
        'name' => 'address_name',
        '住所名' => 'address_name',
        
        // 住所ID
        'address_id' => 'address_id',
        'address_site_id' => 'address_id',
        'site_id' => 'address_id',
        '住所ID' => 'address_id',
        
        // 基本情報
        'first_name' => 'first_name',
        'firstname' => 'first_name',
        '名' => 'first_name',
        
        'last_name' => 'last_name',
        'lastname' => 'last_name',
        '姓' => 'last_name',
        
        'company' => 'company',
        'company_name' => 'company',
        '会社名' => 'company',
        
        // 住所
        'address_1' => 'address_1',
        'address1' => 'address_1',
        'street' => 'address_1',
        '住所1' => 'address_1',
        
        'address_2' => 'address_2',
        'address2' => 'address_2',
        '住所2' => 'address_2',
        
        'city' => 'city',
        '市区町村' => 'city',
        
        'state' => 'state',
        'province' => 'state',
        '都道府県' => 'state',
        
        'postcode' => 'postcode',
        'zip' => 'postcode',
        'postal_code' => 'postcode',
        '郵便番号' => 'postcode',
        
        'country' => 'country',
        'country_code' => 'country',
        '国' => 'country',
        
        // 連絡先
        'phone' => 'phone',
        'tel' => 'phone',
        '電話番号' => 'phone',
        
        'email' => 'email',
        'mail' => 'email',
        'メール' => 'email',
        
        // その他
        'is_default' => 'is_default',
        'default' => 'is_default',
        'デフォルト' => 'is_default',
        
        // カスタムフィールド用
        'vat_number' => 'vat_number',
        'vat' => 'vat_number',
        'VAT番号' => 'vat_number'
    ];
    
    private $errors = [];
    private $imported_count = 0;
    private $skipped_count = 0;
    private $skip_logs = [];
    
    // 日本の都道府県名からコードへのマッピング
    private $prefecture_mapping = [
        '北海道' => 'JP01',
        '青森県' => 'JP02', '青森' => 'JP02',
        '岩手県' => 'JP03', '岩手' => 'JP03',
        '宮城県' => 'JP04', '宮城' => 'JP04',
        '秋田県' => 'JP05', '秋田' => 'JP05',
        '山形県' => 'JP06', '山形' => 'JP06',
        '福島県' => 'JP07', '福島' => 'JP07',
        '茨城県' => 'JP08', '茨城' => 'JP08',
        '栃木県' => 'JP09', '栃木' => 'JP09',
        '群馬県' => 'JP10', '群馬' => 'JP10',
        '埼玉県' => 'JP11', '埼玉' => 'JP11',
        '千葉県' => 'JP12', '千葉' => 'JP12',
        '東京都' => 'JP13', '東京' => 'JP13',
        '神奈川県' => 'JP14', '神奈川' => 'JP14',
        '新潟県' => 'JP15', '新潟' => 'JP15',
        '富山県' => 'JP16', '富山' => 'JP16',
        '石川県' => 'JP17', '石川' => 'JP17',
        '福井県' => 'JP18', '福井' => 'JP18',
        '山梨県' => 'JP19', '山梨' => 'JP19',
        '長野県' => 'JP20', '長野' => 'JP20',
        '岐阜県' => 'JP21', '岐阜' => 'JP21',
        '静岡県' => 'JP22', '静岡' => 'JP22',
        '愛知県' => 'JP23', '愛知' => 'JP23',
        '三重県' => 'JP24', '三重' => 'JP24',
        '滋賀県' => 'JP25', '滋賀' => 'JP25',
        '京都府' => 'JP26', '京都' => 'JP26',
        '大阪府' => 'JP27', '大阪' => 'JP27',
        '兵庫県' => 'JP28', '兵庫' => 'JP28',
        '奈良県' => 'JP29', '奈良' => 'JP29',
        '和歌山県' => 'JP30', '和歌山' => 'JP30',
        '鳥取県' => 'JP31', '鳥取' => 'JP31',
        '島根県' => 'JP32', '島根' => 'JP32',
        '岡山県' => 'JP33', '岡山' => 'JP33',
        '広島県' => 'JP34', '広島' => 'JP34',
        '山口県' => 'JP35', '山口' => 'JP35',
        '徳島県' => 'JP36', '徳島' => 'JP36',
        '香川県' => 'JP37', '香川' => 'JP37',
        '愛媛県' => 'JP38', '愛媛' => 'JP38',
        '高知県' => 'JP39', '高知' => 'JP39',
        '福岡県' => 'JP40', '福岡' => 'JP40',
        '佐賀県' => 'JP41', '佐賀' => 'JP41',
        '長崎県' => 'JP42', '長崎' => 'JP42',
        '熊本県' => 'JP43', '熊本' => 'JP43',
        '大分県' => 'JP44', '大分' => 'JP44',
        '宮崎県' => 'JP45', '宮崎' => 'JP45',
        '鹿児島県' => 'JP46', '鹿児島' => 'JP46',
        '沖縄県' => 'JP47', '沖縄' => 'JP47'
    ];
    
    public function import($csv_file_path, $options = []) {
        // オプション
        $delimiter = $options['delimiter'] ?? ',';
        $encoding = $options['encoding'] ?? 'UTF-8';
        $skip_errors = $options['skip_errors'] ?? true;
        $dry_run = $options['dry_run'] ?? false;
        $delete_mode = $options['delete_mode'] ?? false;
        
        if (!file_exists($csv_file_path)) {
            $this->errors[] = "ファイルが見つかりません: $csv_file_path";
            return false;
        }
        
        // エンコーディング変換が必要な場合
        if ($encoding !== 'UTF-8') {
            $content = file_get_contents($csv_file_path);
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            $temp_file = tempnam(sys_get_temp_dir(), 'wcmca_csv_');
            file_put_contents($temp_file, $content);
            $csv_file_path = $temp_file;
        }
        
        if (($handle = fopen($csv_file_path, "r")) !== FALSE) {
            // ヘッダー行を読み込む
            $headers = fgetcsv($handle, 0, $delimiter);
            if (!$headers) {
                $this->errors[] = "ヘッダー行が読み込めません";
                fclose($handle);
                return false;
            }
            
            // ヘッダーを正規化（小文字化、空白除去）
            $headers = array_map(function($h) {
                return trim(mb_strtolower($h));
            }, $headers);
            
            // 必須フィールドチェック
            if (!$this->validateHeaders($headers)) {
                fclose($handle);
                return false;
            }
            
            $line_number = 1;
            while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                $line_number++;
                
                try {
                    $row = array_combine($headers, $data);
                    if (!$row) {
                        throw new Exception("行 $line_number: データの読み込みに失敗しました");
                    }
                    
                    // フィールドをマッピング
                    $mapped_data = $this->mapFields($row);
                    
                    // データ検証
                    $this->validateData($mapped_data, $line_number);
                    
                    if ($delete_mode) {
                        // 削除モード
                        $result = $dry_run ? $this->validateDeleteAddress($mapped_data, $line_number) : $this->deleteAddress($mapped_data, $line_number);
                        if ($result === true) {
                            $this->imported_count++; // 削除成功もカウント
                        } elseif ($result === 'skipped') {
                            $this->skipped_count++;
                        }
                    } else {
                        // インポート実行
                        $result = $dry_run ? $this->validateImportAddress($mapped_data, $line_number) : $this->importAddress($mapped_data, $line_number);
                        if ($result === true) {
                            $this->imported_count++;
                        } elseif ($result === 'skipped') {
                            $this->skipped_count++;
                        }
                    }
                    
                } catch (Exception $e) {
                    $this->errors[] = $e->getMessage();
                    if (!$skip_errors) {
                        fclose($handle);
                        return false;
                    }
                }
            }
            
            fclose($handle);
            
            // 一時ファイルを削除
            if (isset($temp_file) && file_exists($temp_file)) {
                unlink($temp_file);
            }
            
            return true;
        }
        
        return false;
    }
    
    private function validateHeaders($headers) {
        // ユーザー識別子のチェック
        $has_user_identifier = false;
        foreach (['user_email', 'user_id', 'user_login', 'email', 'ユーザーメール', 'ユーザーid'] as $field) {
            if (in_array($field, $headers)) {
                $has_user_identifier = true;
                break;
            }
        }
        
        if (!$has_user_identifier) {
            $this->errors[] = "必須フィールドが不足: user_email, user_id, user_login のいずれかが必要です";
            return false;
        }
        
        return true;
    }
    
    private function mapFields($row) {
        $mapped = [];
        
        foreach ($row as $key => $value) {
            $normalized_key = trim(mb_strtolower($key));
            
            // マッピングテーブルで変換
            if (isset($this->field_mapping[$normalized_key])) {
                $mapped_key = $this->field_mapping[$normalized_key];
                $mapped[$mapped_key] = trim($value);
            }
            // マッピングにない場合はそのまま使用（カスタムフィールド対応）
            else {
                $mapped[$key] = trim($value);
            }
        }
        
        return $mapped;
    }
    
    private function validateData(&$data, $line_number) {
        // ユーザー識別
        if (empty($data['user_email']) && empty($data['user_id']) && empty($data['user_login'])) {
            throw new Exception("行 $line_number: ユーザー識別子が指定されていません");
        }
        
        // 住所タイプ（指定がない場合はshippingをデフォルトに）
        if (empty($data['type'])) {
            $data['type'] = 'shipping';
        }
        
        if (!in_array($data['type'], ['billing', 'shipping'])) {
            throw new Exception("行 $line_number: 無効な住所タイプ: " . $data['type']);
        }
        
        // 都道府県名を都道府県コードに変換
        if (!empty($data['state']) && $data['country'] === 'JP') {
            $data['state'] = $this->convertPrefectureNameToCode($data['state']);
        }
        
        // shippingタイプの場合の処理
        if ($data['type'] === 'shipping') {
            // first_name, last_nameは空のままでOK
            if (empty($data['first_name'])) {
                $data['first_name'] = '';
            }
            if (empty($data['last_name'])) {
                $data['last_name'] = '';
            }
            
            // companyが必須
            if (empty($data['company'])) {
                throw new Exception("行 $line_number: shippingタイプではcompanyが必須です");
            }
        } else {
            // billingタイプの場合は従来通り
            $required = ['first_name', 'last_name'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("行 $line_number: 必須フィールドが空です: $field");
                }
            }
        }
        
        // 共通の必須フィールド
        $required = ['address_1', 'city', 'country'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("行 $line_number: 必須フィールドが空です: $field");
            }
        }
    }
    
    private function importAddress($data, $line_number) {
        // ユーザーを特定
        $user = null;
        if (!empty($data['user_id'])) {
            $user = get_user_by('id', $data['user_id']);
        } elseif (!empty($data['user_email'])) {
            $user = get_user_by('email', $data['user_email']);
        } elseif (!empty($data['user_login'])) {
            $user = get_user_by('login', $data['user_login']);
        }
        
        if (!$user) {
            throw new Exception("ユーザーが見つかりません: " . 
                ($data['user_email'] ?? $data['user_id'] ?? $data['user_login']));
        }
        
        $user_id = $user->ID;
        $user_identifier = $user->user_email;
        
        // 既存の住所を取得
        $existing_addresses = get_user_meta($user_id, '_wcmca_additional_addresses', true);
        if (!is_array($existing_addresses)) {
            $existing_addresses = array();
        }
        
        // 会社名の重複チェック
        if (!empty($data['company'])) {
            $type = $data['type'];
            $company_field = $type . '_company';
            
            foreach ($existing_addresses as $existing_address) {
                if (isset($existing_address['type']) && $existing_address['type'] === $type &&
                    isset($existing_address[$company_field]) && 
                    $existing_address[$company_field] === $data['company']) {
                    
                    // 重複ログを記録
                    $skip_message = sprintf(
                        "行 %d: スキップ - ユーザー %s の %s 住所に会社名 '%s' は既に登録されています",
                        $line_number,
                        $user_identifier,
                        $type === 'billing' ? '請求先' : '配送先',
                        $data['company']
                    );
                    $this->skip_logs[] = $skip_message;
                    
                    return 'skipped';
                }
            }
        }
        
        // 新しい住所IDを生成
        $max_id = 0;
        foreach ($existing_addresses as $address) {
            if (isset($address['address_id']) && $address['address_id'] > $max_id) {
                $max_id = $address['address_id'];
            }
        }
        $new_address_id = $max_id + 1;
        
        // 住所データを構築
        $type = $data['type'];
        $new_address = array(
            'address_id' => $new_address_id,
            'type' => $type,
            'address_internal_name' => $data['address_name'] ?? 
                ($type === 'shipping' && !empty($data['company']) ? $data['company'] : 
                sprintf('%s %s - %s', $data['first_name'], $data['last_name'], $data['address_1']))
        );
        
        // shippingタイプの場合、shipping_site_idを設定
        if ($type === 'shipping') {
            // address_idが指定されている場合はそれを使用、なければcompanyを使用
            if (!empty($data['address_id'])) {
                $new_address['shipping_site_id'] = $data['address_id'];
            } elseif (!empty($data['company'])) {
                $new_address['shipping_site_id'] = $data['company'];
            }
        }
        
        // 標準フィールド
        $fields = ['first_name', 'last_name', 'company', 'address_1', 'address_2', 
                  'city', 'state', 'postcode', 'country', 'phone', 'email'];
        
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $new_address[$type . '_' . $field] = $data[$field];
            }
        }
        
        // VAT番号（billing only）
        if ($type === 'billing' && !empty($data['vat_number'])) {
            $new_address['billing_vat_number'] = $data['vat_number'];
        }
        
        // デフォルト設定
        if (!empty($data['is_default']) && in_array($data['is_default'], ['1', 'true', 'yes', 'はい'])) {
            // 既存のデフォルトを解除
            foreach ($existing_addresses as &$addr) {
                if (isset($addr[$type . '_is_default_address'])) {
                    unset($addr[$type . '_is_default_address']);
                }
            }
            $new_address[$type . '_is_default_address'] = '1';
        }
        
        // カスタムフィールド（マッピングされていないフィールド）
        foreach ($data as $key => $value) {
            if (!in_array($key, array_merge($fields, ['user_email', 'user_id', 'user_login', 
                'type', 'address_name', 'is_default', 'vat_number']))) {
                // カスタムフィールドとして追加
                $new_address[$type . '_' . $key] = $value;
            }
        }
        
        // 住所を追加
        $existing_addresses[] = $new_address;
        
        // 保存
        update_user_meta($user_id, '_wcmca_additional_addresses', $existing_addresses);
        
        return true;
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getImportedCount() {
        return $this->imported_count;
    }
    
    public function getSkippedCount() {
        return $this->skipped_count;
    }
    
    public function getSkipLogs() {
        return $this->skip_logs;
    }
    
    private function deleteAddress($data, $line_number) {
        // ユーザーを特定
        $user = null;
        if (!empty($data['user_id'])) {
            $user = get_user_by('id', $data['user_id']);
        } elseif (!empty($data['user_email'])) {
            $user = get_user_by('email', $data['user_email']);
        } elseif (!empty($data['user_login'])) {
            $user = get_user_by('login', $data['user_login']);
        }
        
        if (!$user) {
            throw new Exception("ユーザーが見つかりません: " . 
                ($data['user_email'] ?? $data['user_id'] ?? $data['user_login']));
        }
        
        $user_id = $user->ID;
        $user_identifier = $user->user_email;
        
        // 既存の住所を取得
        $existing_addresses = get_user_meta($user_id, '_wcmca_additional_addresses', true);
        if (!is_array($existing_addresses)) {
            $existing_addresses = array();
        }
        
        // 削除対象を検索
        $type = $data['type'];
        $delete_id = !empty($data['address_id']) ? $data['address_id'] : ($data['company'] ?? '');
        $deleted = false;
        $new_addresses = array();
        
        foreach ($existing_addresses as $address) {
            // タイプとIDが一致する住所を削除
            $should_delete = false;
            
            if (isset($address['type']) && $address['type'] === $type) {
                // shipping_site_idフィールドで一致をチェック
                if ($type === 'shipping' && isset($address['shipping_site_id']) && 
                    $address['shipping_site_id'] === $delete_id) {
                    $should_delete = true;
                }
                // shipping_idフィールドで一致をチェック（後方互換性）
                elseif ($type === 'shipping' && isset($address['shipping_id']) && 
                    $address['shipping_id'] === $delete_id) {
                    $should_delete = true;
                }
                // billing_idフィールドで一致をチェック（billingの場合）
                elseif ($type === 'billing' && isset($address['billing_id']) && 
                    $address['billing_id'] === $delete_id) {
                    $should_delete = true;
                }
                // IDフィールドがない場合はcompanyフィールドで一致をチェック
                elseif (isset($address[$type . '_company']) && 
                    $address[$type . '_company'] === $delete_id) {
                    $should_delete = true;
                }
            }
            
            if ($should_delete) {
                // 削除ログを記録
                $skip_message = sprintf(
                    "行 %d: 削除 - ユーザー %s の %s 住所 ID:'%s' を削除しました",
                    $line_number,
                    $user_identifier,
                    $type === 'billing' ? '請求先' : '配送先',
                    $delete_id
                );
                $this->skip_logs[] = $skip_message;
                $deleted = true;
            } else {
                // 削除対象でない住所は保持
                $new_addresses[] = $address;
            }
        }
        
        if ($deleted) {
            // 更新された住所リストを保存
            update_user_meta($user_id, '_wcmca_additional_addresses', $new_addresses);
            return true;
        } else {
            // 削除対象が見つからなかった
            $skip_message = sprintf(
                "行 %d: スキップ - ユーザー %s の %s 住所 ID:'%s' は見つかりませんでした",
                $line_number,
                $user_identifier,
                $type === 'billing' ? '請求先' : '配送先',
                $delete_id
            );
            $this->skip_logs[] = $skip_message;
            return 'skipped';
        }
    }
    
    private function convertPrefectureNameToCode($prefecture_name) {
        // すでにコード形式（JP01など）の場合はそのまま返す
        if (preg_match('/^JP\d{2}$/', $prefecture_name)) {
            return $prefecture_name;
        }
        
        // 数字のみの場合（1, 2, 13, 47など）
        if (preg_match('/^\d{1,2}$/', $prefecture_name)) {
            $number = intval($prefecture_name);
            // 1〜47の範囲内であれば、JPとゼロパディングを追加
            if ($number >= 1 && $number <= 47) {
                return 'JP' . str_pad($number, 2, '0', STR_PAD_LEFT);
            }
        }
        
        // 数字文字列の場合（"01", "02", "13", "47"など）
        if (preg_match('/^\d{2}$/', $prefecture_name)) {
            $number = intval($prefecture_name);
            // 1〜47の範囲内であれば、JPを追加
            if ($number >= 1 && $number <= 47) {
                return 'JP' . $prefecture_name;
            }
        }
        
        // マッピングから変換
        if (isset($this->prefecture_mapping[$prefecture_name])) {
            return $this->prefecture_mapping[$prefecture_name];
        }
        
        // 見つからない場合はそのまま返す（エラーにはしない）
        return $prefecture_name;
    }
    
    private function validateImportAddress($data, $line_number) {
        // インポートのドライラン検証
        // ユーザーを特定
        $user = null;
        if (!empty($data['user_id'])) {
            $user = get_user_by('id', $data['user_id']);
        } elseif (!empty($data['user_email'])) {
            $user = get_user_by('email', $data['user_email']);
        } elseif (!empty($data['user_login'])) {
            $user = get_user_by('login', $data['user_login']);
        }
        
        if (!$user) {
            throw new Exception("ユーザーが見つかりません: " . 
                ($data['user_email'] ?? $data['user_id'] ?? $data['user_login']));
        }
        
        $user_id = $user->ID;
        $user_identifier = $user->user_email;
        
        // 既存の住所を取得
        $existing_addresses = get_user_meta($user_id, '_wcmca_additional_addresses', true);
        if (!is_array($existing_addresses)) {
            $existing_addresses = array();
        }
        
        // 会社名の重複チェック
        if (!empty($data['company'])) {
            $type = $data['type'];
            $company_field = $type . '_company';
            
            foreach ($existing_addresses as $existing_address) {
                if (isset($existing_address['type']) && $existing_address['type'] === $type &&
                    isset($existing_address[$company_field]) && 
                    $existing_address[$company_field] === $data['company']) {
                    
                    // 重複ログを記録
                    $skip_message = sprintf(
                        "行 %d: スキップ - ユーザー %s の %s 住所に会社名 '%s' は既に登録されています",
                        $line_number,
                        $user_identifier,
                        $type === 'billing' ? '請求先' : '配送先',
                        $data['company']
                    );
                    $this->skip_logs[] = $skip_message;
                    
                    return 'skipped';
                }
            }
        }
        
        // ドライランではデータベースに書き込まず、成功扱いする
        $this->skip_logs[] = sprintf(
            "行 %d: [DRY-RUN] インポート予定 - ユーザー %s の %s 住所 '%s'",
            $line_number,
            $user_identifier,
            $data['type'] === 'billing' ? '請求先' : '配送先',
            $data['company'] ?? $data['first_name'] . ' ' . $data['last_name']
        );
        
        return true;
    }
    
    private function validateDeleteAddress($data, $line_number) {
        // 削除のドライラン検証
        // ユーザーを特定
        $user = null;
        if (!empty($data['user_id'])) {
            $user = get_user_by('id', $data['user_id']);
        } elseif (!empty($data['user_email'])) {
            $user = get_user_by('email', $data['user_email']);
        } elseif (!empty($data['user_login'])) {
            $user = get_user_by('login', $data['user_login']);
        }
        
        if (!$user) {
            throw new Exception("ユーザーが見つかりません: " . 
                ($data['user_email'] ?? $data['user_id'] ?? $data['user_login']));
        }
        
        $user_id = $user->ID;
        $user_identifier = $user->user_email;
        
        // 既存の住所を取得
        $existing_addresses = get_user_meta($user_id, '_wcmca_additional_addresses', true);
        if (!is_array($existing_addresses)) {
            $existing_addresses = array();
        }
        
        // 削除対象を検索
        $type = $data['type'];
        $delete_id = !empty($data['address_id']) ? $data['address_id'] : ($data['company'] ?? '');
        $found = false;
        
        foreach ($existing_addresses as $address) {
            // タイプとIDが一致する住所を探す
            if (isset($address['type']) && $address['type'] === $type) {
                // shipping_site_idフィールドで一致をチェック
                if ($type === 'shipping' && isset($address['shipping_site_id']) && 
                    $address['shipping_site_id'] === $delete_id) {
                    $found = true;
                }
                // shipping_idフィールドで一致をチェック（後方互換性）
                elseif ($type === 'shipping' && isset($address['shipping_id']) && 
                    $address['shipping_id'] === $delete_id) {
                    $found = true;
                }
                // billing_idフィールドで一致をチェック（billingの場合）
                elseif ($type === 'billing' && isset($address['billing_id']) && 
                    $address['billing_id'] === $delete_id) {
                    $found = true;
                }
                // IDフィールドがない場合はcompanyフィールドで一致をチェック
                elseif (isset($address[$type . '_company']) && 
                    $address[$type . '_company'] === $delete_id) {
                    $found = true;
                }
                
                if ($found) {
                    break;
                }
            }
        }
        
        if ($found) {
            $this->skip_logs[] = sprintf(
                "行 %d: [DRY-RUN] 削除予定 - ユーザー %s の %s 住所 ID:'%s'",
                $line_number,
                $user_identifier,
                $type === 'billing' ? '請求先' : '配送先',
                $delete_id
            );
            return true;
        } else {
            $this->skip_logs[] = sprintf(
                "行 %d: [DRY-RUN] スキップ - ユーザー %s の %s 住所 ID:'%s' は見つかりませんでした",
                $line_number,
                $user_identifier,
                $type === 'billing' ? '請求先' : '配送先',
                $delete_id
            );
            return 'skipped';
        }
    }
}

// CLI実行
if (php_sapi_name() === 'cli') {
    $importer = new WCMCA_CSV_Importer();
    
    if ($argc < 2) {
        echo "使用方法:\n";
        echo "php wcmca-import-script-flexible.php <csv_file> [options]\n";
        echo "\nオプション:\n";
        echo "  --delimiter=,     区切り文字（デフォルト: ,）\n";
        echo "  --encoding=SJIS   文字エンコーディング（デフォルト: UTF-8）\n";
        echo "  --dry-run         実際にはインポートせず、検証のみ実行\n";
        echo "  --stop-on-error   エラー時に処理を中止（デフォルト: エラーをスキップ）\n";
        echo "  --delete-mode     削除モード（タイプとIDが一致する住所を削除）\n";
        echo "\n対応フィールド:\n";
        echo "  必須: user_email/user_id, type, first_name, last_name, address_1, city, country\n";
        echo "  オプション: company, address_2, state, postcode, phone, email, is_default, vat_number\n";
        echo "\nCSVサンプル:\n";
        echo "  user_email,type,first_name,last_name,address_1,city,country\n";
        echo "  test@example.com,shipping,太郎,山田,千代田区1-1,東京都,JP\n";
        exit(1);
    }
    
    $csv_file = $argv[1];
    $options = [
        'delimiter' => ',',
        'encoding' => 'UTF-8',
        'skip_errors' => true,
        'dry_run' => false,
        'delete_mode' => false
    ];
    
    // オプション解析
    for ($i = 2; $i < $argc; $i++) {
        if (preg_match('/^--delimiter=(.+)$/', $argv[$i], $matches)) {
            $options['delimiter'] = $matches[1];
        } elseif (preg_match('/^--encoding=(.+)$/', $argv[$i], $matches)) {
            $options['encoding'] = $matches[1];
        } elseif ($argv[$i] === '--dry-run') {
            $options['dry_run'] = true;
        } elseif ($argv[$i] === '--stop-on-error') {
            $options['skip_errors'] = false;
        } elseif ($argv[$i] === '--delete-mode') {
            $options['delete_mode'] = true;
        }
    }
    
    if ($options['delete_mode']) {
        echo "削除モード開始...\n";
    } else {
        echo "インポート開始...\n";
    }
    if ($options['dry_run']) {
        echo "*** ドライラン mode ***\n";
    }
    
    $result = $importer->import($csv_file, $options);
    
    if ($result) {
        if ($options['delete_mode']) {
            echo "\n=== 削除結果 ===\n";
            echo "削除成功: " . $importer->getImportedCount() . " 件\n";
            echo "スキップ: " . $importer->getSkippedCount() . " 件\n";
            echo "合計処理: " . ($importer->getImportedCount() + $importer->getSkippedCount()) . " 件\n";
        } else {
            echo "\n=== インポート結果 ===\n";
            echo "成功: " . $importer->getImportedCount() . " 件\n";
            echo "スキップ: " . $importer->getSkippedCount() . " 件\n";
            echo "合計処理: " . ($importer->getImportedCount() + $importer->getSkippedCount()) . " 件\n";
        }
    } else {
        echo "処理失敗\n";
    }
    
    // スキップログ表示
    $skip_logs = $importer->getSkipLogs();
    if (!empty($skip_logs)) {
        echo "\n=== スキップされた行 ===\n";
        foreach ($skip_logs as $log) {
            echo "  $log\n";
        }
    }
    
    // エラー表示
    $errors = $importer->getErrors();
    if (!empty($errors)) {
        echo "\n=== エラー ===\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
}