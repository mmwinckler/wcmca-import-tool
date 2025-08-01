# WooCommerce Multiple Customer Addresses - CSV Import Tool

WordPressのWooCommerce Multiple Customer Addresses (WCMCA)プラグイン用のCSVインポート/削除ツールです。

## 機能

- 🚀 **一括インポート**: CSVファイルから複数の配送先住所を一括登録
- 🗑️ **一括削除**: タイプとIDで住所を一括削除
- 🔄 **重複チェック**: 同一ユーザー・同一会社名の重複を自動スキップ
- 🏷️ **都道府県コード自動変換**: 日本の都道府県名を自動的にWooCommerceコードに変換
- 📝 **ドライランモード**: 実際の処理前に結果を確認
- 🌍 **柔軟なフィールドマッピング**: CSV列名の自動認識

## 必要環境

- WordPress 5.0以上
- WooCommerce 3.0以上
- WooCommerce Multiple Customer Addresses プラグイン
- PHP 7.2以上

## インストール

1. `wcmca-import-script-flexible.php`をWordPressルートディレクトリに配置
2. 実行権限を設定：
```bash
chmod +x wcmca-import-script-flexible.php
```

## 使用方法

### 基本的な使い方

```bash
# インポート
php wcmca-import-script-flexible.php [CSVファイル] [オプション]

# 削除
php wcmca-import-script-flexible.php [CSVファイル] --delete-mode
```

### オプション

- `--dry-run` : 実際にはインポート/削除せず、検証のみ実行
- `--delete-mode` : 削除モード（タイプとIDが一致する住所を削除）
- `--delimiter=,` : 区切り文字（デフォルト: ,）
- `--encoding=SJIS` : 文字エンコーディング（デフォルト: UTF-8）
- `--stop-on-error` : エラー時に処理を中止（デフォルト: エラーをスキップ）

### 使用例

```bash
# ドライランで確認
php wcmca-import-script-flexible.php addresses.csv --dry-run

# 本番実行
php wcmca-import-script-flexible.php addresses.csv

# 削除モードでドライラン
php wcmca-import-script-flexible.php delete_list.csv --delete-mode --dry-run

# SJIS形式のCSVをインポート
php wcmca-import-script-flexible.php sjis_data.csv --encoding=SJIS
```

## CSVフォーマット

### 必須フィールド

インポート時の必須フィールド：
- `user_email` / `user_id` / `user_login` のいずれか（ユーザー識別子）
- `address_1` : 住所1
- `city` : 市区町村
- `country` : 国コード（例：JP）

### 対応フィールド一覧

| フィールド名 | 説明 | 例 |
|------------|------|-----|
| user_email | ユーザーメールアドレス | user@example.com |
| user_id | ユーザーID | 123 |
| user_login | ユーザーログイン名 | username |
| type | 住所タイプ | shipping / billing |
| company | 会社名（配送先ID） | 株式会社サンプル |
| first_name | 名 | 太郎 |
| last_name | 姓 | 山田 |
| address_1 | 住所1 | 千代田区千代田1-1 |
| address_2 | 住所2（建物名等） | サンプルビル1F |
| city | 市区町村 | 千代田区 |
| state | 都道府県 | 東京都 |
| postcode | 郵便番号 | 100-0001 |
| country | 国コード | JP |
| phone | 電話番号 | 03-1234-5678 |
| email | メールアドレス | contact@example.com |

### 配送先住所（shipping）の特殊仕様

配送先住所の場合：
- `first_name`と`last_name`は空でもOK
- `company`フィールドが`Address Site ID`として使用される
- `company`が必須

### サンプルCSV

**インポート用** (`sample_import.csv`):
```csv
user_email,company,country,postcode,state,city,address_1,address_2,phone
user@example.com,株式会社サンプル東京本社,JP,100-0001,東京都,千代田区,千代田1-1-1,サンプルビル1F,03-1234-5678
user@example.com,株式会社サンプル大阪支社,JP,530-0001,大阪府,大阪市北区,梅田2-2-2,サンプルビル2F,06-1234-5678
```

**削除用** (`sample_delete.csv`):
```csv
user_email,company,country,postcode,state,city,address_1,address_2,phone
user@example.com,株式会社サンプル東京本社,JP,,,,,
user@example.com,株式会社サンプル大阪支社,JP,,,,,
```

## 都道府県の自動変換

日本の都道府県は自動的にWooCommerceコードに変換されます：
- `北海道` → `JP01`
- `東京都` → `JP13`
- `大阪府` → `JP27`
- など

## 重複チェック

同一ユーザーで同じ会社名の住所が既に登録されている場合、自動的にスキップされます。

## エラーハンドリング

- ユーザーが見つからない場合はエラーとして記録
- 必須フィールドが不足している場合はエラー
- デフォルトではエラーをスキップして処理を継続（`--stop-on-error`で変更可能）

## トラブルシューティング

### よくある問題

1. **文字化けする場合**
   - CSVファイルのエンコーディングを確認し、`--encoding`オプションを使用

2. **都道府県が登録されない**
   - 都道府県名が正しいか確認（「県」「都」「府」「道」を含む）

3. **Address Site IDが空欄になる**
   - `company`フィールドが正しく設定されているか確認

## ライセンス

このツールはMITライセンスで提供されています。

## 作者

Claude Assistant により作成されました。