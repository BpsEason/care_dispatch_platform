FROM php:8.3-fpm-alpine # 使用 PHP 8.3，符合 Laravel 11 建議

WORKDIR /var/www/html

RUN apk add --no-cache     nginx     supervisor     php83-opcache     php83-pdo_mysql     php83-dom     php83-xml     php83-mbstring     php83-tokenizer     php83-fileinfo     php83-session     php83-ctype     php83-json     php83-gd     php83-exif     php83-zip     php83-bcmath     php83-pcntl     php83-mysqli     php83-curl     php83-intl     php83-iconv     php83-phar     php83-openssl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 這裡不複製整個專案，只準備 Composer 環境
# 專案程式碼將通過 volume 掛載

EXPOSE 9000
CMD ["php-fpm"]
