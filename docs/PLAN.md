# Link Shortener — План реализации

Сервис коротких ссылок на Laravel с личным кабинетом на базе Filament v3,
статистикой переходов и полностью контейнеризированным окружением (Docker).

---

## 1. Стек технологий

| Слой | Технология | Обоснование |
|------|-----------|-------------|
| Язык | **PHP 8.4** | Актуальная стабильная версия, поддерживается Laravel 11 и Filament v3 |
| Фреймворк | **Laravel 11** | Соответствует требованию «Laravel 10+», актуальная LTS-линейка |
| Админ-панель / Личный кабинет | **Filament v3** | «Большой плюс» из ТЗ; закрывает CRUD ссылок, статистику, auth |
| Аутентификация | **Filament Auth** (`login` + `registration`) | Регистрация и вход из коробки Filament |
| БД | **PostgreSQL 16** | Требование ТЗ |
| Кэш / очереди (по необходимости) | database driver | Без внешних зависимостей, проще для теста |
| Генерация кода ссылки | собственный сервис + `base62` | Короткий, URL-safe, детерминированная длина |
| Тесты | **PHPUnit** (Feature + Unit) | Юнит-тесты обязательны по ТЗ |
| Тестовая БД | PostgreSQL (отдельная БД в том же контейнере) | Тесты на том же движке, что и прод |
| Контейнеризация | **Docker + docker-compose** | Требование ТЗ; сервисы: `app` (FrankenPHP), `postgres` |
| Веб-сервер / сервер приложений | **FrankenPHP** (Caddy-based) | Один бинарник вместо `nginx + php-fpm`; авто-HTTPS/HTTP2/3 для деплоя, чистый HTTP локально |

### Почему FrankenPHP вместо nginx + php-fpm

- **Один сервис** вместо связки `nginx + php-fpm`: проще `docker-compose.yml`,
  нет FPM-сокета и upstream-конфига nginx.
- Движок **Caddy** даёт авто-HTTPS, HTTP/2 и HTTP/3 из коробки — «бесплатно» для реального деплоя.
- Официально поддерживается Laravel (`octane:install --server=frankenphp`, интеграция в Laravel Sail);
  та же связка, что продвигает и Symfony.
- Запускаем в **классическом режиме (без Octane worker mode)**: каждый запрос изолирован,
  как в php-fpm → никакого риска с общим стейтом, тесты и Filament ведут себя стандартно.
  Worker-режим Octane можно включить одним флагом позже как опциональную оптимизацию.

### Локальный HTTP без сертификатов

FrankenPHP управляет TLS через переменную `SERVER_NAME`:
- локально ставим **`SERVER_NAME=:8080`** → сервер слушает только HTTP,
  авто-TLS выключен, **никаких самоподписанных сертификатов и предупреждений браузера**;
- для реального деплоя достаточно указать настоящий домен в `SERVER_NAME` —
  Caddy сам получит сертификат Let's Encrypt.

### Docker-сервисы

- `app` — образ на базе **`dunglas/frankenphp`** (PHP 8.4) + Composer + расширения
  (`pdo_pgsql`, `intl`, `zip`, и т.д.). Слушает `:8080` по HTTP. Отдаёт `public/`.
- `postgres` — PostgreSQL 16, том для данных; отдельная БД `link_shortener_test` для тестов.

Запуск: `docker compose up -d`, приложение доступно на `http://localhost:8080`,
Filament-панель — на `http://localhost:8080/app`.

---

## 2. Модель данных

### `users` (стандартная Laravel + Filament)
- `id`, `name`, `email`, `password`, timestamps.

### `links`
| Поле | Тип | Описание |
|------|-----|----------|
| `id` | bigint PK | |
| `user_id` | FK → users | Владелец ссылки |
| `original_url` | text | Оригинальный URL |
| `code` | string, unique | Короткий код (напр. `abc123`) |
| `clicks_count` | unsigned int, default 0 | Денормализованный счётчик (для быстрой выдачи) |
| `created_at` / `updated_at` | timestamps | |

Индексы: `unique(code)`, `index(user_id)`.

### `clicks`
| Поле | Тип | Описание |
|------|-----|----------|
| `id` | bigint PK | |
| `link_id` | FK → links (cascade delete) | |
| `ip_address` | string(45) | IPv4/IPv6 |
| `user_agent` | text, nullable | Доп. данные (не обязательно по ТЗ, но полезно) |
| `created_at` | timestamp | Дата и время перехода |

Индексы: `index(link_id)`, `index(created_at)`.
При удалении ссылки — каскадное удаление кликов.

---

## 3. Основные компоненты

### Сервисный слой
- `App\Services\ShortCodeGenerator` — генерирует уникальный короткий код
  (base62, проверка коллизий по БД). Легко покрывается **юнит-тестами**.
- `App\Services\LinkRedirector` (или action) — находит ссылку по коду,
  фиксирует клик (IP + timestamp), инкрементит счётчик, возвращает URL.

### Модели
- `Link` — relations: `belongsTo(User)`, `hasMany(Click)`;
  accessor `short_url`, метод `registerClick(Request)`.
- `Click` — `belongsTo(Link)`.
- `User` — реализует `FilamentUser` (доступ в панель).

### Маршруты (public)
- `GET /{code}` — `RedirectController`: редирект + регистрация клика.
  Вынесен из Filament, работает без авторизации.

### Filament (личный кабинет, панель `app`)
- **LinkResource** — CRUD своих ссылок:
  - List: код, короткий URL, оригинальный URL, кол-во кликов, дата.
  - Create: ввод `original_url` (валидация URL), код генерируется автоматически.
  - Delete: удаление ссылки (+ каскад кликов).
  - Scope: пользователь видит **только свои** ссылки (`user_id = auth()->id()`).
  - Action «Статистика» / Relation Manager `ClicksRelationManager` —
    список переходов (IP, дата/время) и общее число кликов.
- Регистрация включена (`->registration()`), вход — стандартный Filament login.

---

## 4. Логика ключевых сценариев

1. **Регистрация/вход** — стандартные страницы Filament (`/app/login`, `/app/register`).
2. **Создание ссылки** — форма Filament принимает `original_url`,
   `ShortCodeGenerator` создаёт уникальный `code`, ссылка привязывается к `auth()->id()`.
3. **Редирект** — `GET /{code}`:
   - ищем `Link` по `code` (404, если нет);
   - пишем `Click` (IP из `request()->ip()`, `created_at` = now);
   - инкремент `clicks_count`;
   - `redirect($link->original_url)`.
4. **Статистика** — в LinkResource: total = `clicks_count`,
   детально — список из таблицы `clicks` по ссылке.

---

## 5. Тестирование

Требование ТЗ: **обязательны юнит-тесты**. Покрытие:

### Unit
- `ShortCodeGeneratorTest` — длина, алфавит (base62), уникальность/обработка коллизий.
- `LinkTest` — accessor `short_url`, `registerClick()` создаёт запись и увеличивает счётчик.

### Feature
- `RedirectTest` — валидный код → 302 на оригинальный URL + создаётся `Click`;
  несуществующий код → 404.
- `LinkManagementTest` — пользователь видит только свои ссылки; удаление работает;
  чужую ссылку удалить нельзя.
- `AuthTest` — регистрация и вход.

Тесты гоняются в Docker против отдельной тестовой БД PostgreSQL
(`php artisan test` внутри контейнера `app`). `RefreshDatabase` для изоляции.

---

## 6. Структура репозитория (после реализации)

```
├── app/
│   ├── Filament/Resources/LinkResource/        # кабинет
│   ├── Http/Controllers/RedirectController.php  # публичный редирект
│   ├── Models/{Link,Click,User}.php
│   └── Services/ShortCodeGenerator.php
├── database/migrations/                         # links, clicks
├── database/factories/                          # Link, Click factories
├── docker/
│   ├── Dockerfile              # на базе dunglas/frankenphp, PHP 8.4
│   └── Caddyfile               # конфиг FrankenPHP (опционально; по умолчанию хватает SERVER_NAME)
├── docker-compose.yml         # сервисы: app (frankenphp), postgres
├── tests/{Unit,Feature}/
├── docs/PLAN.md                                 # этот файл
└── README.md                                    # инструкция запуска
```

---

## 7. Порядок работ

1. Инициализация Laravel 11 в репозитории (composer).
2. Docker-окружение (`docker-compose.yml`, Dockerfile, nginx, postgres) + `.env`.
3. Установка и настройка Filament v3, панель `app`, регистрация.
4. Миграции + модели + фабрики (`links`, `clicks`).
5. `ShortCodeGenerator` + сервис редиректа.
6. `RedirectController` + маршрут `GET /{code}`.
7. `LinkResource` (CRUD, scope по пользователю) + `ClicksRelationManager` (статистика).
8. Юнит- и feature-тесты, прогон в Docker.
9. README с инструкцией запуска и тестирования.

---

## 8. Чеклист по реализации

### 0. Подготовка репозитория
- [x] Инициализировать Laravel 11 внутри существующего репозитория (сохранить `.git`, `README.md`, `docs/`)
- [x] Проверить/дополнить `.gitignore` под Laravel (уже есть подходящий)
- [x] Зафиксировать версии в `composer.json`: PHP `^8.2` (рантайм PHP 8.4), Laravel `^11`

### 1. Docker-окружение (FrankenPHP + PostgreSQL)
- [x] `docker/Dockerfile` на базе `dunglas/frankenphp` (PHP 8.4)
- [x] Установить расширения: `pdo_pgsql`, `intl`, `zip`, `gd` (при необходимости), `opcache`
- [x] Установить Composer в образ
- [x] `docker-compose.yml`: сервис `app` (FrankenPHP, порт `8080`, `SERVER_NAME=:8080`)
- [x] `docker-compose.yml`: сервис `postgres` (16, volume, env для БД)
- [x] Настроить том исходников и `public/` как корень FrankenPHP
- [x] `.env` / `.env.example`: `DB_CONNECTION=pgsql`, host `postgres`, креды
- [ ] Проверить: `docker compose up -d` поднимается, `http://localhost:8080` открывается по HTTP без сертификата
- [ ] `docker compose exec app composer install`, `php artisan key:generate`

### 2. База данных: миграции, модели, фабрики
- [x] Миграция `links` (`user_id` FK, `original_url`, `code` unique, `clicks_count` default 0, timestamps)
- [x] Миграция `clicks` (`link_id` FK cascade, `ip_address`, `user_agent` nullable, `created_at`)
- [x] Модель `Link`: `belongsTo(User)`, `hasMany(Click)`, `$fillable`, accessor `short_url`, метод `registerClick(Request)`
- [x] Модель `Click`: `belongsTo(Link)`, `$fillable`, `created_at` без `updated_at`
- [x] Модель `User`: реализует `FilamentUser` (`canAccessPanel`)
- [x] Фабрики `LinkFactory`, `ClickFactory`
- [ ] Прогнать `php artisan migrate` в контейнере — таблицы создаются

### 3. Генерация короткого кода
- [x] `App\Services\ShortCodeGenerator`: base62, заданная длина, проверка уникальности по БД
- [x] Юнит-тест: длина кода, только символы алфавита base62, повтор при коллизии

### 4. Публичный редирект
- [x] `App\Http\Controllers\RedirectController` (`__invoke`)
- [x] Маршрут `GET /{code}` в `routes/web.php` (вне Filament, без auth)
- [x] Поиск `Link` по `code`, `404` если не найден
- [x] Регистрация клика: `ip_address` из `request()->ip()`, `created_at` = now, `user_agent`
- [x] Инкремент `clicks_count`
- [x] Убедиться, что маршрут `{code}` не перехватывает `/app` (Filament) и служебные пути (constraint в маршруте)

### 5. Filament v3 — панель и аутентификация
- [ ] Установить `filament/filament:^3`, `php artisan filament:install --panels`
- [ ] Панель `app` (`/app`), включить `->registration()` и `->login()`
- [ ] Настроить доступ пользователя в панель (`canAccessPanel`)
- [ ] Проверить регистрацию и вход через UI

### 6. Filament — LinkResource (личный кабинет)
- [ ] `LinkResource`: форма (поле `original_url` с валидацией URL; `code` генерируется автоматически при создании)
- [ ] Таблица: `code` / `short_url` (копируемый), `original_url`, `clicks_count`, `created_at`
- [ ] Scope: `getEloquentQuery()` → только `where('user_id', auth()->id())`
- [ ] При создании подставлять `user_id = auth()->id()` и сгенерированный `code`
- [ ] Действие удаления ссылки (каскадно удаляет клики)
- [ ] Запрет доступа к чужим ссылкам (edit/delete/view)

### 7. Filament — статистика переходов
- [ ] `ClicksRelationManager` у `LinkResource`: таблица кликов (IP, дата/время), только чтение
- [ ] Общее число кликов на ссылку (колонка `clicks_count` и/или в заголовке)
- [ ] (Опционально) виджет/бейдж с суммарной статистикой

### 8. Тесты
- [ ] Настроить `phpunit.xml` на тестовую БД PostgreSQL (`link_shortener_test`)
- [ ] Unit: `ShortCodeGeneratorTest` (длина, алфавит, уникальность)
- [ ] Unit: `LinkTest` (accessor `short_url`, `registerClick()` пишет клик и растит счётчик)
- [ ] Feature: `RedirectTest` (валидный код → 302 + создан `Click`; несуществующий → 404)
- [ ] Feature: `LinkManagementTest` (видны только свои ссылки; удаление своей; чужую нельзя)
- [ ] Feature: `AuthTest` (регистрация и вход)
- [ ] Прогон `docker compose exec app php artisan test` — всё зелёное

### 9. Финализация
- [ ] README: описание, стек, `docker compose up -d`, миграции, запуск тестов, URL панели
- [ ] Указать команды создания тестового пользователя / сидер (опционально)
- [ ] Финальный прогон с нуля: `git clone` → `docker compose up` → миграции → тесты → ручная проверка сценариев
- [ ] Коммит(ы) с осмысленными сообщениями

---

### Definition of Done
- [ ] Регистрация и вход работают (Filament)
- [ ] Создание короткой ссылки из кабинета
- [ ] Переход по короткой ссылке редиректит и фиксирует клик (IP + дата/время)
- [ ] В кабинете: список своих ссылок, удаление, статистика (список переходов + общее количество)
- [ ] Пользователь видит только свои ссылки
- [ ] Юнит-тесты присутствуют и проходят
- [ ] Всё поднимается в Docker, БД — PostgreSQL, локально работает по HTTP без сертификатов
