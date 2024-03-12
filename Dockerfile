# PHPの公式イメージをベースにする
FROM php:7.4-apache

# 必要なPHP拡張をインストール
RUN docker-php-ext-install mysqli pdo pdo_mysql

# アプリケーションのコードをコンテナにコピー
COPY . /var/www/html

# デフォルトのWebサーバーのドキュメントルートを設定
WORKDIR /var/www/html

# 必要なポートを開放
EXPOSE 80