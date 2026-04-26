FROM trueasync/php-true-async:latest

RUN apt-get update -qq && apt-get install -y --no-install-recommends inotify-tools \
    && rm -rf /var/lib/apt/lists/*

RUN ln -s /usr/local/lib/php/extensions/no-debug-zts-20250926/xdebug.so \
          /usr/local/lib/php/extensions/no-debug-zts-20250926/php_xdebug.so

