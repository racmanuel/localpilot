# Contratos compartidos

Este archivo debe congelarse antes del desarrollo paralelo. Cambios posteriores requieren versión y aprobación del integrador.

## Identidad

```text
slug/directorio: localpilot
text domain: localpilot
function/hook/option/table prefix: lclplt_
private meta prefix: _lclplt_
class prefix: Localpilot_
constant prefix: LOCALPILOT_
REST namespace: localpilot/v1
account endpoint: mis-entregas
driver role: localpilot_driver
plugin version: 1.0.0
requires WordPress: 6.0
requires PHP: 7.4
tested up to: 7.0.2
```

## Estados de entrega

```text
unassigned
assigned
accepted
out_for_delivery
delivered
failed
cancelled
```

## Transiciones

| Desde | Hacia | Actor |
|---|---|---|
| `unassigned` | `assigned` | gestor |
| `assigned` | `accepted` | driver propietario |
| `assigned` | `out_for_delivery` | driver si aceptación desactivada |
| `accepted` | `out_for_delivery` | driver propietario |
| `assigned`/`accepted`/`out_for_delivery` | `failed` | según política |
| `out_for_delivery` | `delivered` | driver propietario |
| estado activo | `cancelled` | gestor |

Reasignar cierra la asignación anterior y crea una nueva en `assigned`. `delivered`, `failed` y `cancelled` son terminales para esa asignación.

## Eventos

```text
delivery_created
delivery_geocoded
delivery_location_updated
delivery_assigned
delivery_reassigned
delivery_unassigned
delivery_accepted
delivery_started
delivery_completed
delivery_failed
delivery_cancelled
proof_uploaded
```

## Hooks de dominio propuestos

Se disparan después de persistencia exitosa:

```text
lclplt_delivery_assigned($order_id, $driver_id, $assignment_id, $event_id)
lclplt_delivery_reassigned($order_id, $old_driver_id, $new_driver_id, $assignment_id, $event_id)
lclplt_delivery_unassigned($order_id, $old_driver_id, $event_id)
lclplt_delivery_accepted($order_id, $driver_id, $assignment_id, $event_id)
lclplt_delivery_started($order_id, $driver_id, $assignment_id, $event_id)
lclplt_delivery_completed($order_id, $driver_id, $assignment_id, $event_id)
lclplt_delivery_failed($order_id, $driver_id, $assignment_id, $event_id)
lclplt_delivery_location_updated($order_id, $actor_id, $event_id)
```

El prefijo se deriva del boilerplate real y queda congelado como `lclplt_`.

## Filtros propuestos

```text
lclplt_assignable_order_statuses
lclplt_driver_can_accept_delivery
lclplt_delivery_transition_allowed
lclplt_failure_reasons
lclplt_template_path
lclplt_mapbox_geocoding_query
```

Los filtros no sustituyen comprobaciones de seguridad.

## Capabilities

```text
lclplt_view_assigned_deliveries
lclplt_accept_delivery
lclplt_start_delivery
lclplt_complete_delivery
lclplt_fail_delivery
lclplt_upload_delivery_proof
lclplt_manage_deliveries
```

## Códigos de error

```text
lclplt_forbidden
lclplt_delivery_not_found
lclplt_invalid_transition
lclplt_already_assigned
lclplt_driver_inactive
lclplt_order_not_eligible
lclplt_invalid_proof
lclplt_geocoding_failed
lclplt_conflict
lclplt_validation_error
```

Mensajes visibles son traducibles; los consumidores dependen del código, no del texto.

## Resultado de servicio

Los servicios deben devolver un valor consistente con el estilo del repositorio. Recomendación:

- objeto/ID en éxito;
- `WP_Error` con código estable en fallo;
- nunca imprimir, redirigir o terminar la ejecución desde dominio.

## Settings

Prefijo `lclplt_` y uso de WooCommerce Settings API. Claves mínimas:

```text
lclplt_eligible_order_statuses
lclplt_require_acceptance
lclplt_show_order_total
lclplt_require_received_by
lclplt_require_proof
lclplt_max_proof_size
lclplt_completed_order_status
lclplt_failed_order_status
lclplt_mapbox_enabled
lclplt_mapbox_token
lclplt_mapbox_style
lclplt_mapbox_zoom
lclplt_mapbox_country
lclplt_mapbox_language
lclplt_auto_geocode
lclplt_allow_manual_location
lclplt_location_validation_enabled
lclplt_location_validation_radius
lclplt_location_validation_mode
lclplt_delete_data_on_uninstall
```

### Meta keys de ubicación

```text
_lclplt_delivery_latitude        — destino geocodificado
_lclplt_delivery_longitude       — destino geocodificado
_lclplt_delivery_location_lat    — GPS del repartidor al completar
_lclplt_delivery_location_lng    — GPS del repartidor al completar
_lclplt_location_validation_status
_lclplt_location_validation_distance
_lclplt_location_validation_radius
_lclplt_location_validation_accuracy
_lclplt_location_validation_at
```

## Validación puntual de ubicación

No es GPS en vivo. Al completar una entrega, el navegador puede enviar una sola
posición y el servidor calcula la distancia hasta las coordenadas del destino.

```text
lclplt_location_validation_enabled: yes|no
lclplt_location_validation_radius: 10..5000 metros
lclplt_location_validation_mode: warning|blocking
```

El cliente nunca envía un resultado confiable: `passed` o `outside_radius` se
determinan en servidor mediante Haversine. Se conserva solo un resumen en el
pedido (`status`, distancia, radio, precisión y fecha), no un historial de
coordenadas.

Resultados técnicos previstos:

```text
passed
outside_radius
permission_denied
unavailable
timeout
stale
low_accuracy
no_destination
disabled
```

En modo `warning`, la entrega puede continuar y el gestor revisa el resultado.
En modo `blocking`, los resultados distintos de `passed` impiden completar.

## Idempotencia

- una sola asignación activa por pedido;
- completar/fallar una asignación terminal no repite side effects;
- emails se correlacionan con `event_id`;
- geocodificación exitosa/manual no se repite automáticamente;
- upgrades de esquema son reentrantes.

## Definición de terminado

Estados, eventos, hooks, capabilities, errores, settings y reglas de idempotencia están aprobados, versionados y consumidos sin variantes locales por todos los frentes.
