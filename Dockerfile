FROM php:8.2-apache

# ติดตั้ง extension สำหรับต่อ MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# เปิดใช้งาน mod_rewrite สำหรับทำ URL สวยๆ
RUN a2enmod rewrite