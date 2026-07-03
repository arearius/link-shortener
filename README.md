# Link Shortener

Веб-приложение для создания коротких ссылок с личным кабинетом, статистикой
переходов и аутентификацией. Построено на **Laravel 11** и **Filament v3**,
работает в Docker на **FrankenPHP** (Caddy) + **PostgreSQL**.

## Возможности

- Регистрация и вход (Filament).
- Создание короткой ссылки из оригинального URL (`https://example.com/page` → `http://localhost:8080/abc123`).
- Публичный редирект по короткой ссылке с фиксацией перехода.
- Личный кабинет: список **своих** ссылок, удаление, статистика.
- Статистика по каждой ссылке: IP-адрес, дата/время перехода, общее число кликов.

## Технологический стек

| Слой | Технология |
|------|-----------|
| Язык | PHP 8.4 |
| Фреймворк | Laravel 11 |
| Личный кабинет / админка | Filament v3 |
| БД | PostgreSQL 16 |
| Сервер приложений | FrankenPHP (Caddy-based), без отдельного nginx + php-fpm |
| Тесты | PHPUnit (Unit + Feature) |
| Контейнеризация | Docker + docker compose |

Подробный план и архитектура — в [docs/PLAN.md](docs/PLAN.md).

## Требования

- Docker и Docker Compose (Docker Desktop на Windows/macOS подходит).
- Локально PHP/Composer **не нужны** — всё выполняется в контейнерах.

## Быстрый старт

```bash
git clone https://github.com/arearius/link-shortener.git
cd link-shortener

# Одна команда: собрать образ, поднять сервисы и полностью подготовить приложение
docker compose up -d --build
```

Всё остальное делает entrypoint контейнера при первом старте (идемпотентно):
создаёт `.env` из `.env.example`, ставит зависимости (`composer install`),
генерирует `APP_KEY` и применяет миграции — затем запускает FrankenPHP.
Ручные шаги не нужны.

> Первый запуск дольше: внутри контейнера выполняется `composer install`.
> Прогресс виден в `docker compose logs -f app` (строки `[entrypoint] ...`).

Приложение: <http://localhost:8080> — корень сразу редиректит на личный кабинет.
Личный кабинет (Filament): <http://localhost:8080/app>

### Демо-данные (необязательно)

Чтобы получить готового пользователя с примерами ссылок и статистикой, включите
сидинг переменной `APP_SEED=true` (одноразово при старте):

```bash
APP_SEED=true docker compose up -d --build
# либо вручную в уже запущенном контейнере:
docker compose exec app php artisan db:seed
```

**Демо-доступ:** `demo@example.com` / `password`. Сидер идемпотентен — повторный запуск не дублирует данные.

> Локально сервер работает по **чистому HTTP** (`SERVER_NAME=:8080` в
> `docker-compose.yml`), поэтому самоподписанных сертификатов и предупреждений
> браузера нет. Для боевого деплоя достаточно указать в `SERVER_NAME` реальный
> домен — FrankenPHP/Caddy сам получит TLS-сертификат Let's Encrypt.

## Использование

1. Откройте <http://localhost:8080/app/register> и зарегистрируйтесь
   (или войдите под демо-аккаунтом, если запускали `db:seed`).
2. В разделе **«Мои ссылки»** нажмите «Создать ссылку», укажите оригинальный URL —
   короткий код сгенерируется автоматически. Принимаются только внешние
   http/https-адреса (адрес самого приложения и loopback отклоняются).
3. Перейдите по короткой ссылке (`http://localhost:8080/{код}`) — произойдёт
   редирект, а переход попадёт в статистику.
4. Откройте ссылку («Просмотр»/«Изменить») — под формой виден список переходов
   (IP, дата/время) и общее число кликов.

## Тесты

Юнит- и feature-тесты выполняются против отдельной БД `link_shortener_test`
(создаётся автоматически при первом старте PostgreSQL, см. `docker/postgres/init.sql`):

```bash
docker compose exec app php artisan test
```

Покрытие:

- **Unit** — `ShortCodeGenerator` (длина, алфавит base62, уникальность),
  модель `Link` (accessor `short_url`, запись клика + инкремент счётчика),
  правило `ExternalUrl` (принимает внешние http/https, отклоняет иные схемы,
  loopback и хост приложения).
- **Feature** — редирект (302 + запись клика, 404 на неизвестный код),
  управление ссылками в Filament (видны только свои, создание, отклонение
  внутренних URL, удаление, запрет доступа к чужим), страницы регистрации/входа.

## Структура

```
app/
├── Filament/Resources/LinkResource.php         # личный кабинет: CRUD ссылок
│   └── LinkResource/RelationManagers/          # статистика переходов
├── Http/Controllers/RedirectController.php      # публичный редирект + учёт кликов
├── Models/{Link,Click,User}.php
├── Providers/Filament/AppPanelProvider.php      # панель /app с регистрацией
├── Rules/ExternalUrl.php                         # валидация внешнего http/https URL
└── Services/ShortCodeGenerator.php              # генерация base62-кода
database/migrations/                             # links, clicks
database/seeders/DatabaseSeeder.php              # демо-пользователь + ссылки
docker/                                          # Dockerfile (FrankenPHP), Caddyfile,
                                                 # entrypoint.sh (авто-bootstrap), init.sql
docker-compose.yml
tests/{Unit,Feature}/
docs/PLAN.md
```

## Развёртывание

### Локальная разработка

Достаточно одной команды (см. [Быстрый старт](#быстрый-старт)):

```bash
docker compose up -d --build
```

Исходный код монтируется в контейнер томом (`./:/app`), поэтому изменения в файлах
сразу видны без пересборки образа. Entrypoint при первом старте сам подготавливает
приложение (`.env`, зависимости, ключ, миграции).

Частые команды:

```bash
docker compose logs -f app                      # логи приложения (в т.ч. [entrypoint])
docker compose exec app php artisan migrate     # применить новые миграции
docker compose exec app php artisan test        # тесты
docker compose exec app php artisan tinker      # REPL
docker compose exec app composer install        # доустановить зависимости
```

### Production

В проде код **запекается в образ** (без bind-mount), зависимости ставятся без dev,
включается кэширование конфигурации и реальный HTTPS.

**1. Требования на сервере**
- Docker + Docker Compose (или образ, собранный в CI и загруженный в реестр).
- Открытые порты `80` и `443` (для авто-TLS от Let's Encrypt) и доступный домен,
  указывающий A/AAAA-записью на сервер.

**2. Production-образ.** В [docker/Dockerfile](docker/Dockerfile) уже есть закомментированный
блок для standalone-сборки — раскомментируйте его, чтобы код и зависимости попали в образ:

```dockerfile
COPY . /app
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
```

**3. Файл окружения `.env`** (создайте на сервере, не коммитьте):

```dotenv
APP_NAME="Link Shortener"
APP_ENV=production
APP_DEBUG=false
APP_KEY=            # сгенерируйте: php artisan key:generate --show
APP_URL=https://links.example.com

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=link_shortener
DB_USERNAME=laravel
DB_PASSWORD=<надёжный-пароль>
```

**4. Реальный HTTPS.** Укажите домен вместо `:8080` — FrankenPHP/Caddy сам получит
и будет обновлять сертификат Let's Encrypt. Пример `docker-compose.prod.yml` (override):

```yaml
services:
  app:
    environment:
      SERVER_NAME: "links.example.com"   # реальный домен -> авто-HTTPS
    ports:
      - "80:80"
      - "443:443"
    volumes: []                          # без bind-mount: код уже в образе
  postgres:
    ports: []                            # БД наружу не публикуем
```

Запуск с override:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

**5. Миграции при деплое.** Entrypoint при каждом старте выполняет `php artisan app:migrate` —
это `migrate --force` под **advisory-lock PostgreSQL**, поэтому при нескольких репликах
приложения одновременно мигрирует только одна, остальные ждут (в отличие от
`migrate --isolated`, лок не требует таблиц и работает на пустой БД). Применяются
только новые миграции, данные не трогаются.

Если хотите прогонять миграции **отдельным управляемым шагом** CI/CD (zero-downtime),
отключите авто-миграцию флагом `APP_MIGRATE=false` и запускайте вручную:

```bash
APP_MIGRATE=false docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
docker compose exec app php artisan app:migrate
```

**6. Оптимизация после деплоя** (кэши прогрейте вручную или добавьте в entrypoint
для прод-профиля):

```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan filament:optimize
```

**7. Проверка.** Health-check Laravel доступен на `GET /up`; главная и панель — на
`https://<домен>/` и `https://<домен>/app`.

> **Опционально — максимум производительности.** FrankenPHP умеет worker-режим через
> Laravel Octane (`composer require laravel/octane` + запуск
> `frankenphp run` в worker mode). Это держит приложение в памяти между запросами
> (в бенчмарках ~кратный прирост RPS), но требует аккуратности со стейтом. Для базового
> прод-развёртывания не обязателен.

## Остановка

```bash
docker compose down          # остановить сервисы
docker compose down -v       # + удалить том с данными PostgreSQL
```
