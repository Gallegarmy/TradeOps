# Trueque Simple

¡¡Total y completamente vibecodeado!!

Mini app de trueques en PHP 8.3 + MySQL + Bootstrap 5.3.

## Requisitos

- PHP 8.3+
- MySQL 8+

## Configuración

1. Importa el esquema:
   - `mysql -u root -p < database/schema.sql`
2. Configura variables de entorno (puedes partir de `.env.example`):
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
3. Arranca servidor local:
   - `php -S localhost:8000 -t public`


4. Comentario humano: En nginx, añadir:
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }


## Rutas

- `GET /` portada + buscador + kanban
- `GET|POST /register`
- `GET|POST /login`
- `POST /logout`
- `GET|POST /swaps/new`
- `POST /offers/create`
- `POST /offers/{id}/accept`
- `POST /offers/{id}/reject`
- `POST /offers/{id}/complete`
- `POST /offers/{id}/rate`
