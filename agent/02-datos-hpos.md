# Datos, esquema y compatibilidad HPOS

## Alcance

Definir la fuente de verdad del estado actual, el historial estructurado y el acceso compatible con High-Performance Order Storage.

## Dependencias

- Arquitectura congelada.
- Contratos de estados y eventos.
- WooCommerce activo.
- Activador bajo control del integrador.

## Modelo de persistencia

### Metadatos del pedido: estado actual

```text
_lclplt_driver_id
_lclplt_delivery_status
_lclplt_assignment_id
_lclplt_assigned_at
_lclplt_accepted_at
_lclplt_out_for_delivery_at
_lclplt_delivered_at
_lclplt_failed_at
_lclplt_failed_reason
_lclplt_received_by
_lclplt_proof_attachment_id
_lclplt_delivery_notes
_lclplt_delivery_latitude
_lclplt_delivery_longitude
_lclplt_mapbox_place_id
_lclplt_geocoded_address
_lclplt_geocoded_at
_lclplt_geocoding_status
```

Toda lectura/escritura se realiza sobre `WC_Order` con `get_meta()`, `update_meta_data()`, `delete_meta_data()` y `save()`.

### Tabla `{$wpdb->prefix}lclplt_assignments`

| Campo | Tipo orientativo | Notas |
|---|---|---|
| `id` | bigint unsigned PK | autoincremental |
| `order_id` | bigint unsigned | indexado |
| `driver_id` | bigint unsigned | indexado |
| `assigned_by` | bigint unsigned | actor |
| `status` | varchar(32) | contrato de estados |
| timestamps de ciclo | datetime nullable | UTC |
| `created_at` | datetime | UTC |
| `updated_at` | datetime | UTC |

Índices mínimos: `order_id`, `driver_id`, `status`, `(driver_id,status)`, `(order_id,status)`.

### Tabla `{$wpdb->prefix}lclplt_events`

| Campo | Tipo orientativo | Notas |
|---|---|---|
| `id` | bigint unsigned PK | autoincremental |
| `order_id` | bigint unsigned | indexado |
| `assignment_id` | bigint unsigned nullable | indexado |
| `driver_id` | bigint unsigned nullable | indexado |
| `user_id` | bigint unsigned nullable | actor |
| `event_type` | varchar(64) | indexado |
| `event_data` | longtext nullable | JSON mediante helpers WP |
| `ip_address` | varchar(45) nullable | política de privacidad |
| `created_at` | datetime | UTC |

## Consistencia

El servicio de aplicación coordina tablas y metadatos. No existe transacción distribuida portable entre `WC_Order` y tablas propias; por ello:

1. validar todo antes de escribir;
2. crear/actualizar la asignación;
3. actualizar el pedido;
4. crear evento;
5. agregar nota de pedido;
6. disparar hooks después del éxito.

Si un paso posterior falla, registrar error y dejar una operación reintentable. No ocultar inconsistencias.

## Archivos a crear o tocar

- `includes/database/class-*-db-schema.php`
- `includes/database/class-*-assignment-repository.php`
- `includes/database/class-*-event-repository.php`
- `includes/deliveries/class-*-order-delivery-meta.php`
- activador, únicamente mediante patch del integrador
- `uninstall.php`, únicamente al cierre y con aprobación
- archivo principal, únicamente para declaración HPOS por el integrador

## Tareas

1. Crear esquema con `dbDelta()` y versión `lclplt_db_version`.
2. Hacer upgrades idempotentes por versión.
3. Permitir que el bootstrap administrativo detecte una versión atrasada y ejecute el upgrade; una actualización de plugin no dispara necesariamente el hook de activación.
4. Implementar repositorios con consultas preparadas.
5. Centralizar claves de meta como constantes.
6. Centralizar timestamps UTC y formateo.
7. Declarar compatibilidad HPOS.
8. Prohibir `get_post_meta()` para datos del pedido.
9. Prohibir consultas a `wp_posts`/`wp_postmeta` para pedidos.
10. Implementar paginación y límites en consultas históricas.
11. Definir política de uninstall opt-in.

## Criterios de aceptación

- El mismo flujo pasa con HPOS activado y desactivado.
- Reactivar el plugin no duplica tablas ni datos.
- Un upgrade de esquema interrumpido puede reintentarse.
- Todas las consultas variables usan `$wpdb->prepare()`.
- No se almacenan objetos PHP serializados en `event_data`.
- Los timestamps se guardan en UTC.
- Las consultas por repartidor y estado usan índices.
- La eliminación de datos no ocurre por defecto al desinstalar.

## Riesgos

- Desincronización entre meta y tabla: servicios únicos y prueba de reconciliación.
- Carrera de doble asignación: comprobación inmediatamente anterior a escritura y restricción lógica de asignación activa.
- Tablas multisite: activación por sitio; no asumir prefijo global.
- Meta queries costosas: usar tabla de asignaciones para operación y meta para estado del pedido.
- Datos personales en eventos: almacenar solo lo necesario.

## Definición de terminado

El esquema es versionado e idempotente, los repositorios están aislados, HPOS está declarado y probado, no existe acceso heredado a pedidos y el modelo soporta asignación, transición, eventos y Mapbox sin duplicar la fuente de verdad.
