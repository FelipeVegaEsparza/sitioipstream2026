# Migraciones de base de datos

IPStream no usa un ORM ni un framework de migraciones. Para aplicar cambios de
esquema en producción (Dokploy) de forma controlada y repetible usamos
`migrate.php` + la carpeta `php/migrations/`.

## Cómo funciona

- Cada cambio de esquema vive en un archivo `.sql` dentro de `php/migrations/`.
- `migrate.php` los ejecuta en orden alfabético y registra los aplicados en la
  tabla `schema_migrations`.
- Los archivos ya aplicados se omiten, así que es **seguro ejecutarlo varias
  veces** (idempotente).
- Si una migración falla, el runner se detiene y no registra ese archivo.

El `schema.sql` sigue siendo la fuente de verdad para instalaciones nuevas
(se monta en `/docker-entrypoint-initdb.d/` en el contenedor MySQL). Las
migraciones son para bases de datos ya existentes.

## Cómo agregar una migración

1. Crea un archivo en `php/migrations/` con nombre ordenable por fecha:

   ```
   php/migrations/2026_10_01_01_add_campo_a_plans.sql
   ```

2. Escribe SQL idempotente cuando sea posible (`CREATE TABLE IF NOT EXISTS`,
   `INSERT IGNORE`, etc.). Para columnas en MySQL 8 no existe
   `ADD COLUMN IF NOT EXISTS`; envuélvelo en una verificación con
   `information_schema` o acepta que solo corra una vez.

3. Verifica primero en local con dry-run, luego aplica en producción.

## Producción (Dokploy)

El token se lee de la variable de entorno `MIGRATION_TOKEN`, definida en
`docker-compose.yml` para el servicio `web`.

### Aplicar por URL (recomendado)

```
https://ipstream.cl/migrate.php?token=TU_MIGRATION_TOKEN
```

Solo lista lo pendiente, sin aplicar:

```
https://ipstream.cl/migrate.php?token=TU_MIGRATION_TOKEN&dry_run=1
```

La respuesta es texto plano e incluye el resultado de cada archivo
(`OK`, `SKIP`, `PEND`, `ERROR`).

### Aplicar por terminal (alternativa)

En Dokploy abre el terminal del servicio **web** (tiene las variables de
entorno de la base de datos) y ejecuta:

```sh
php migrate.php --dry-run   # ver pendientes
php migrate.php             # aplicar
```

### Cambiar el token

1. Genera uno nuevo:

   ```sh
   openssl rand -hex 24
   ```

2. Actualiza `MIGRATION_TOKEN` en `docker-compose.yml` o directamente en la
   variable de entorno del servicio en Dokploy.

3. Redeploya el servicio `web`.

Si `MIGRATION_TOKEN` queda vacío o no está definido, el acceso por URL responde
**403** y solo funciona por CLI.

## Desarrollo local

Con `docker-compose.dev.yml` el token es `dev-migrate-token`:

```
http://localhost:8002/migrate.php?token=dev-migrate-token
```

O por CLI dentro del contenedor:

```sh
docker compose -f docker-compose.dev.yml exec web php migrate.php
```

## Seguridad

- El token es la única credencial del endpoint: mantenlo fuera de enlaces
  públicos y rota si se filtra.
- `migrate.php` envía `X-Robots-Tag: noindex, nofollow`.
- Cuando ya no lo necesites, puedes restringir el acceso en
  `docker/nginx.conf` (por ejemplo bloquear `location = /migrate.php` y usar
  solo CLI) o eliminar el archivo.

## Solución de problemas

- **403 Acceso denegado**: token inválido o `MIGRATION_TOKEN` no configurado.
- **Error de conexión a la base de datos**: revisa `DB_HOST`, `DB_USER`,
  `DB_PASSWORD`, `DB_NAME` en el entorno del servicio `web`.
- **`ERROR` en una migración**: el SQL no es válido o ya existe un objeto
  incompatible. Corrige el archivo y vuelve a ejecutar; los anteriores ya
  quedaron registrados y se omiten.
