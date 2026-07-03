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

# 1. Файл окружения
cp .env.example .env

# 2. Собрать образ и поднять сервисы (app: FrankenPHP, db: PostgreSQL)
docker compose up -d --build

# 3. Установить зависимости и сгенерировать ключ приложения
docker compose exec app composer install
docker compose exec app php artisan key:generate

# 4. Миграции
docker compose exec app php artisan migrate
```

Приложение: <http://localhost:8080>
Личный кабинет (Filament): <http://localhost:8080/app>

> Локально сервер работает по **чистому HTTP** (`SERVER_NAME=:8080` в
> `docker-compose.yml`), поэтому самоподписанных сертификатов и предупреждений
> браузера нет. Для боевого деплоя достаточно указать в `SERVER_NAME` реальный
> домен — FrankenPHP/Caddy сам получит TLS-сертификат Let's Encrypt.

## Использование

1. Откройте <http://localhost:8080/app/register> и зарегистрируйтесь.
2. В разделе **«Мои ссылки»** нажмите «Создать ссылку», укажите оригинальный URL —
   короткий код сгенерируется автоматически.
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
  модель `Link` (accessor `short_url`, запись клика + инкремент счётчика).
- **Feature** — редирект (302 + запись клика, 404 на неизвестный код),
  управление ссылками в Filament (видны только свои, создание, удаление,
  запрет доступа к чужим), страницы регистрации/входа.

## Структура

```
app/
├── Filament/Resources/LinkResource.php         # личный кабинет: CRUD ссылок
│   └── LinkResource/RelationManagers/          # статистика переходов
├── Http/Controllers/RedirectController.php      # публичный редирект + учёт кликов
├── Models/{Link,Click,User}.php
├── Providers/Filament/AppPanelProvider.php      # панель /app с регистрацией
└── Services/ShortCodeGenerator.php              # генерация base62-кода
database/migrations/                             # links, clicks
docker/                                          # Dockerfile (FrankenPHP), Caddyfile, init.sql
docker-compose.yml
tests/{Unit,Feature}/
docs/PLAN.md
```

## Остановка

```bash
docker compose down          # остановить сервисы
docker compose down -v       # + удалить том с данными PostgreSQL
```
