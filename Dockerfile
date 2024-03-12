# PHPの公式イメージをベースにする
FROM php:7.4-apache

# 必要なPHP拡張をインストール
RUN docker-php-ext-install mysqli pdo pdo_mysql

# mod_rewriteを有効にする
RUN a2enmod rewrite

# アプリケーションのコードをコンテナにコピー
COPY . /var/www/html

# カスタムのApache設定ファイルをコピー
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# デフォルトのWebサーバーのドキュメントルートを設定
WORKDIR /var/www/html

# 必要なポートを開放
EXPOSE 80

# Apacheをフォアグラウンドで実行
CMD ["apache2-foreground"]