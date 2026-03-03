# Video Portal

PHP-портал для видео-контента с аутентификацией, подписками и стримингом через Cloudflare R2.

## Требования

- PHP 8.0+
- Расширения: `pdo_sqlite`, `curl`, `mbstring` (обычно включены по умолчанию)

### Установка PHP

**macOS:**

```bash
# Через Homebrew (рекомендуется)
brew install php

# Проверить
php -v
```

**Ubuntu / Debian:**

```bash
sudo apt update
sudo apt install php php-sqlite3 php-curl php-mbstring
```

**Windows:**

```bash
# Через Chocolatey
choco install php

# Или через Scoop
scoop install php
```

Или скачать с https://windows.php.net/download — распаковать, добавить в PATH.

**Проверка что всё установлено:**

```bash
php -m | grep -E 'pdo_sqlite|curl|mbstring'
```

Должны отобразиться все три расширения.

## Быстрый старт

```bash
# 1. Клонировать репо
git clone <repo-url> video-portal
cd video-portal

# 2. Скачать Composer (если нет глобально)
curl -sS https://getcomposer.org/installer | php

# 3. Установить зависимости
php composer.phar install

# 4. Создать .env из шаблона и заполнить
cp .env.example .env

# 5. Запустить dev-сервер
php -S localhost:8080 -t public public/index.php
```

Открыть http://localhost:8080 — портал готов. SQLite-база создастся автоматически при первом запросе.

## Конфигурация (.env)

| Переменная | Описание |
|---|---|
| `SITE_TITLE` | Название портала |
| `SITE_URL` | Публичный URL (для ссылок в email) |
| `R2_ACCOUNT_ID` | Cloudflare Account ID |
| `R2_ACCESS_KEY` | R2 API Token — Access Key |
| `R2_SECRET_KEY` | R2 API Token — Secret Key |
| `R2_BUCKET` | Имя R2 bucket |
| `SMTP_HOST` | SMTP-сервер |
| `SMTP_PORT` | SMTP-порт (587 для TLS) |
| `SMTP_USER` | SMTP логин |
| `SMTP_PASS` | SMTP пароль |
| `SMTP_FROM_EMAIL` | Email отправителя |
| `API_ADMIN_TOKEN` | Токен для REST API (используется Cursor Skill) |

Без R2/SMTP портал работает — регистрация и каталог доступны, просто видео не стримятся и email не отправляются.

## Структура проекта

```
video-portal/
├── public/            # webroot (nginx/dev-server root)
│   ├── index.php      # единый роутер
│   └── assets/        # CSS, JS
├── api/               # REST API (videos, users, stats)
├── includes/          # ядро (config, db, auth, r2, mailer)
├── templates/         # HTML-шаблоны страниц
├── emails/            # HTML-шаблоны писем
├── data/              # SQLite база (создаётся автоматически)
├── .env               # креды (не в репо)
└── .env.example       # шаблон кредов
```

## REST API

Все запросы требуют заголовок `Authorization: Bearer <API_ADMIN_TOKEN>`.

| Метод | Endpoint | Описание |
|---|---|---|
| GET | `/api/videos` | Список видео |
| POST | `/api/videos` | Создать видео |
| PUT | `/api/videos/{id}` | Обновить видео |
| DELETE | `/api/videos/{id}` | Удалить видео |
| GET | `/api/videos/{id}/url` | Presigned URL для скачивания |
| POST | `/api/videos/upload-url` | Presigned URL для загрузки в R2 |
| GET | `/api/users` | Список пользователей |
| POST | `/api/users` | Создать пользователя |
| PUT | `/api/users/{id}` | Обновить / продлить подписку |
| DELETE | `/api/users/{id}` | Удалить пользователя |
| GET | `/api/stats` | Общая статистика |
| GET | `/api/stats/videos` | Статистика по видео |
| GET | `/api/stats/users` | Активность пользователей |

## Nginx (production)

```nginx
server {
    root /path/to/video-portal/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ ^/(data|includes|api|templates|emails) {
        deny all;
    }
}
```

## Cursor Skill

Портал управляется через Cursor Skill `video-portal`. Skill вызывает REST API для CRUD видео, управления пользователями и просмотра статистики.
