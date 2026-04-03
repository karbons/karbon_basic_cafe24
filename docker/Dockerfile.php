FROM php:8.4-fpm-bookworm

# 시스템 패키지 업데이트 및 필수 패키지 설치
RUN apt-get update && apt-get install -y --no-install-recommends \
  # 기본 빌드 도구
  build-essential \
  autoconf \
  libtool \
  pkg-config \
  # 이미지 처리
  libpng-dev \
  libjpeg-dev \
  libfreetype6-dev \
  libwebp-dev \
  # 압축
  libzip-dev \
  # 데이터베이스
  default-mysql-client \
  # 기타 라이브러리
  libxml2-dev \
  libxslt1-dev \
  libonig-dev \
  libicu-dev \
  libcurl4-openssl-dev \
  libssl-dev \
  # 그누보드5 필수 라이브러리
  gettext \
  # 추가 유틸리티
  git \
  wget \
  unzip \
  && rm -rf /var/lib/apt/lists/*

# PHP 확장 모듈 설치 (단계별로)
# 1. 기본 확장 모듈
RUN docker-php-ext-install -j$(nproc) \
  mysqli \
  pdo_mysql \
  mbstring \
  xml \
  opcache

# 2. GD 확장 모듈 (이미지 처리)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
  && docker-php-ext-install -j$(nproc) gd

# 3. ZIP 확장 모듈
RUN docker-php-ext-configure zip \
  && docker-php-ext-install -j$(nproc) zip

# 4. XSL 확장 모듈
RUN docker-php-ext-install -j$(nproc) xsl

# 5. Intl 확장 모듈
RUN docker-php-ext-install -j$(nproc) intl

# 6. Gettext 확장 모듈
RUN docker-php-ext-install -j$(nproc) gettext

# Redis 확장 설치
RUN pecl install redis && docker-php-ext-enable redis

# APCu 캐시 확장 설치 (선택사항)
RUN pecl install apcu && docker-php-ext-enable apcu

# Composer 설치
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP 설정 파일 복사
COPY php/php.ini /usr/local/etc/php/
COPY php/www.conf /usr/local/etc/php-fpm.d/

# 작업 디렉토리 설정
WORKDIR /var/www/html

# 권한 설정
RUN chown -R www-data:www-data /var/www/html

# 헬스체크 추가
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD php-fpm -t || exit 1

EXPOSE 9000

CMD ["php-fpm"]