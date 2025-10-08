# Backend – Gerenciador de Viagens Corporativas

Esta API em Laravel fornece os endpoints utilizados pelo front-end em Vue para gerenciar pedidos de viagem corporativa. Ela utiliza JWT para autenticação, Spatie Permissions para controle de acesso e envia notificações por e-mail quando um pedido é aprovado ou cancelado.

## Requisitos

- PHP 8.2+
- Composer
- MySQL 8+
- Docker / Docker Compose (opcional, recomendado)

## Instalação

```bash
cd back
cp .env.example .env
# Ajuste as variáveis de ambiente (APP_KEY, DB_*, MAIL_*, etc.)
composer install
php artisan key:generate
php artisan migrate --seed
```

> A seed `InitialSetupSeeder` cria o banco (se necessário), aplica as migrations, registra permissões/roles (`administrador`, `usuario`) e usuários padrões:
>
> - admin@gmail.com / admin@gmail.com
> - usuario@gmail.com / usuario@gmail.com

## Executando com Docker (recomendado)

```bash
cd docker-laravel-vue
docker compose up -d --build
```

| Serviço | Container | Porta |
| --- | --- | --- |
| Laravel FPM | `laravel-backend` | Interna 9000 |
| Front (Vite) | `vue-frontend` | Interna 5173 (acessível via Nginx) |
| Nginx | `nginx` | `http://localhost:91` |
| MySQL | `mysql` | `localhost:3306` |

Execute comandos artisan dentro do container, se necessário:

```bash
docker compose exec laravel-backend php artisan migrate
```

## Executando localmente sem Docker

1. Configure MySQL e o arquivo `.env`.
2. Rode `php artisan migrate --seed`.
3. Inicie o servidor: `php artisan serve --host=0.0.0.0 --port=8000`.

## Endpoints Principais

| Método | Rota | Descrição |
| --- | --- | --- |
| POST | `/api/login` | Autentica e retorna JWT (15 min). |
| GET | `/api/user` | Retorna o usuário autenticado + roles. |
| GET | `/api/travel-requests` | Lista pedidos com filtros (`status`, `location` – termo aplicado sobre cidade/estado/país, `from`, `to`) e paginação (`page`, `per_page`). |
| POST | `/api/travel-requests` | Cria novo pedido vinculado ao usuário logado. |
| GET | `/api/travel-requests/{id}` | Exibe detalhes (owner ou admin). |
| PUT | `/api/travel-requests/{id}` | Atualiza dados do pedido (solicitante enquanto `requested` ou admin). |
| PATCH | `/api/travel-requests/{id}/status` | Atualiza status (`approved`, `cancelled`) – apenas administradores. |
| DELETE | `/api/travel-requests/{id}` | Remove pedido (`requested` do próprio usuário ou administrador). |

Todas as rotas (exceto `/login` e `/user`) exigem `Authorization: Bearer <token>`.

## Permissões e Papéis

- `travel.create`: criar/listar seus pedidos.
- `travel.manage`: visualizar e alterar pedidos de todos os usuários.

O seeder atribui:

- `administrador` → `travel.create`, `travel.manage`
- `usuario` → `travel.create`

## Notificações

`TravelRequestStatusChanged` envia e-mails quando um pedido é aprovado ou cancelado. Configure o mailer no `.env` (`MAIL_MAILER`, `MAIL_HOST`, etc.).

## Testes

```bash
php artisan test --testsuite=Feature --filter=TravelRequestApiTest
```

Cobertura atual:

- Criação e listagem de pedidos do usuário.
- Atualização de status por administradores e disparo de notificação.
- Bloqueio de atualização de status por usuários comuns.

## Estrutura

- `app/Http/Controllers/TravelRequestController.php`
- `app/Http/Middleware/JwtAuthenticate.php`
- `app/Notifications/TravelRequestStatusChanged.php`
- `app/Policies/TravelRequestPolicy.php`
- `tests/Feature/TravelRequestApiTest.php`

## JWT

Os tokens são assinados com `APP_KEY` (HS256) e expiram em 15 minutos. Refaça o login para gerar um novo token.

## Próximos Passos

- Configurar fila para envio assíncrono das notificações (atualmente síncronas).
- Expandir cobertura de testes (filtros, deleção, notificações).
