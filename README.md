# Learning Progress API

Учебный REST API для управления курсами на PHP 8.4, Symfony 6.4,
Doctrine ORM и PostgreSQL 16.

## Возможности

- создание курса;
- получение списка курсов;
- получение курса по идентификатору;
- изменение названия курса;
- удаление курса;
- валидация названия средствами Symfony Validator.

## Запуск

```powershell
composer install
docker compose up -d
php bin/console doctrine:migrations:migrate --no-interaction
symfony server:start
```

API будет доступен по адресу `http://127.0.0.1:8000`.

## Маршруты

| Метод | Маршрут | Назначение |
|---|---|---|
| `GET` | `/api/health` | Проверка состояния API |
| `GET` | `/api/courses` | Получить все курсы |
| `GET` | `/api/courses/{id}` | Получить курс |
| `POST` | `/api/courses` | Создать курс |
| `PATCH` | `/api/courses/{id}` | Изменить курс |
| `DELETE` | `/api/courses/{id}` | Удалить курс |

Пример JSON для `POST` и `PATCH`:

```json
{
  "title": "Symfony Basics"
}
```

## Тесты

Тесты используют отдельную базу `app_test`:

```powershell
docker compose up -d
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:migrations:migrate --env=test --no-interaction
php vendor/bin/phpunit --testdox
```
