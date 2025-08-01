# WooCommerce Multiple Customer Addresses CSV インポートツール 詳細ガイド

## 目次

1. [概要](#概要)
2. [セットアップ](#セットアップ)
3. [CSVファイルの準備](#csvファイルの準備)
4. [インポート手順](#インポート手順)
5. [削除手順](#削除手順)
6. [高度な使い方](#高度な使い方)
7. [フィールドマッピング詳細](#フィールドマッピング詳細)
8. [エラー対処法](#エラー対処法)

## 概要

このツールは、WooCommerce Multiple Customer Addressesプラグインに大量の配送先住所を効率的に登録・管理するためのコマンドラインツールです。

### 主な特徴

- **柔軟なCSV形式**: 列の順序は自由、必須フィールドのみチェック
- **スマートな重複管理**: 同一企業の複数店舗も問題なく登録
- **安全な実行**: ドライランモードで事前確認可能
- **日本仕様対応**: 都道府県の自動変換機能搭載

## セットアップ

### 1. ファイルの配置

```bash
# WordPressのルートディレクトリにスクリプトを配置
cd /path/to/wordpress
wget https://raw.githubusercontent.com/[your-repo]/wcmca-import-tool/main/wcmca-import-script-flexible.php

# 実行権限を付与
chmod +x wcmca-import-script-flexible.php
```

### 2. 動作確認

```bash
# ヘルプを表示
php wcmca-import-script-flexible.php
```

## CSVファイルの準備

### 基本構造

CSVファイルは以下の形式で準備します：

```csv
user_email,company,country,postcode,state,city,address_1,address_2,phone
```

### フィールド説明

#### ユーザー識別フィールド（いずれか1つ必須）

- **user_email**: ユーザーのメールアドレス
- **user_id**: WordPressユーザーID
- **user_login**: ユーザーログイン名

#### 住所フィールド

- **type**: 住所タイプ（shipping/billing）※省略時はshipping
- **company**: 会社名・店舗名（配送先の場合はAddress Site IDとして使用）
- **first_name**: 名（配送先では省略可）
- **last_name**: 姓（配送先では省略可）
- **address_1**: 住所1（必須）
- **address_2**: 住所2（建物名など）
- **city**: 市区町村（必須）
- **state**: 都道府県
- **postcode**: 郵便番号
- **country**: 国コード（必須、日本は"JP"）
- **phone**: 電話番号
- **email**: 連絡先メールアドレス

### B2B配送先住所の例

```csv
user_email,company,country,postcode,state,city,address_1,address_2,phone
tanaka@company.com,株式会社ABC商事 本社,JP,100-0001,東京都,千代田区,丸の内1-1-1,ABCビル10F,03-1234-5678
tanaka@company.com,株式会社ABC商事 横浜支店,JP,220-0001,神奈川県,横浜市西区,みなとみらい2-2-2,横浜ビル5F,045-234-5678
tanaka@company.com,株式会社ABC商事 大阪支店,JP,530-0001,大阪府,大阪市北区,梅田3-3-3,大阪ビル8F,06-345-6789
```

## インポート手順

### 1. ドライランで確認

まず、ドライランモードで実行内容を確認します：

```bash
php wcmca-import-script-flexible.php import_addresses.csv --dry-run
```

出力例：
```
インポート開始...
*** ドライラン mode ***

=== インポート結果 ===
成功: 3 件
スキップ: 0 件
合計処理: 3 件

=== スキップされた行 ===
  行 2: [DRY-RUN] インポート予定 - ユーザー tanaka@company.com の 配送先 住所 '株式会社ABC商事 本社'
  行 3: [DRY-RUN] インポート予定 - ユーザー tanaka@company.com の 配送先 住所 '株式会社ABC商事 横浜支店'
  行 4: [DRY-RUN] インポート予定 - ユーザー tanaka@company.com の 配送先 住所 '株式会社ABC商事 大阪支店'
```

### 2. 本番実行

問題がなければ本番実行します：

```bash
php wcmca-import-script-flexible.php import_addresses.csv
```

### 3. 結果確認

```
インポート開始...

=== インポート結果 ===
成功: 3 件
スキップ: 0 件
合計処理: 3 件
```

## 削除手順

### 1. 削除用CSVの準備

削除には最小限の情報のみ必要です：

```csv
user_email,company,country
tanaka@company.com,株式会社ABC商事 横浜支店,JP
```

### 2. 削除実行

```bash
# ドライラン
php wcmca-import-script-flexible.php delete_list.csv --delete-mode --dry-run

# 本番実行
php wcmca-import-script-flexible.php delete_list.csv --delete-mode
```

## 高度な使い方

### SJIS形式のCSVを扱う

```bash
php wcmca-import-script-flexible.php sjis_data.csv --encoding=SJIS
```

### タブ区切りファイルを扱う

```bash
php wcmca-import-script-flexible.php data.tsv --delimiter=$'\t'
```

### エラー時に処理を停止

```bash
php wcmca-import-script-flexible.php data.csv --stop-on-error
```

## フィールドマッピング詳細

このツールは様々な列名を自動認識します：

| 認識される列名 | マッピング先 |
|--------------|------------|
| user_email, email, ユーザーメール | user_email |
| company, company_name, 会社名 | company |
| address_1, address1, street, 住所1 | address_1 |
| state, province, 都道府県 | state |
| postcode, zip, postal_code, 郵便番号 | postcode |

## エラー対処法

### "ユーザーが見つかりません"

**原因**: 指定されたメールアドレスのユーザーが存在しない

**対処法**:
1. CSVのメールアドレスを確認
2. WordPressにユーザーが登録されているか確認
3. user_idやuser_loginを使用してみる

### "必須フィールドが空です"

**原因**: address_1, city, countryのいずれかが空

**対処法**: CSVファイルの該当行を確認し、必須フィールドを入力

### 文字化け

**原因**: CSVファイルのエンコーディングが異なる

**対処法**:
```bash
# エンコーディングを指定
php wcmca-import-script-flexible.php data.csv --encoding=SJIS
```

### 都道府県が登録されない

**原因**: 都道府県名の形式が認識されない

**対処法**: 
- 正式名称を使用（例：東京都、大阪府、北海道）
- または都道府県コードを直接使用（例：JP13、JP27、JP01）

## ベストプラクティス

1. **少量でテスト**: まず数件のデータでテスト実行
2. **ドライラン活用**: 必ずドライランで確認してから本番実行
3. **バックアップ**: 大量データ処理前はデータベースのバックアップを推奨
4. **段階的実行**: 大量データは分割して段階的に処理
5. **ログ保存**: 実行結果をファイルに保存

```bash
# 実行結果をログファイルに保存
php wcmca-import-script-flexible.php data.csv > import_log_$(date +%Y%m%d_%H%M%S).txt 2>&1
```

## サポート

問題が発生した場合は、以下の情報と共に報告してください：

1. エラーメッセージの全文
2. CSVファイルのサンプル（個人情報は除く）
3. WordPressとWCMCAプラグインのバージョン
4. PHPバージョン