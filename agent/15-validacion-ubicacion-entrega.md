# Validación puntual de ubicación al completar

## Alcance

Esta funcionalidad no implementa GPS en vivo. Obtiene una única ubicación del navegador cuando el repartidor intenta completar una entrega y compara la posición con las coordenadas del destino ya geocodificado.

## Flujo

1. El gestor activa la validación y configura el radio en **WooCommerce → Ajustes → LocalPilot → Flujo de entrega**.
2. La entrega debe estar en `out_for_delivery`.
3. El repartidor pulsa **Completar entrega**.
4. El navegador solicita permiso de ubicación mediante `navigator.geolocation.getCurrentPosition()`.
5. El cliente envía latitud, longitud, precisión, timestamp y estado técnico.
6. El servidor obtiene las coordenadas del destino desde `Localpilot_Order_Delivery_Meta`.
7. El servidor calcula la distancia Haversine y compara contra el radio configurado.
8. El completado continúa, genera advertencia o se bloquea según la política configurada.
9. Se conserva únicamente un resumen de la validación en el pedido y el evento de completado.
10. Las coordenadas GPS del repartidor se guardan como `_lclplt_delivery_location_lat/lng`.
11. En el panel administrativo, el mapa muestra dos marcadores (🔴 destino + 🟢 entrega) y un círculo 🟢 que representa el radio configurado.

## Ajustes

- `lclplt_location_validation_enabled`: `yes`/`no`.
- `lclplt_location_validation_radius`: 10–5000 metros; valor recomendado 100.
- `lclplt_location_validation_mode`: `warning` o `blocking`.

El modo recomendado inicialmente es `warning` para evitar bloquear entregas legítimas por mala señal GPS, permisos denegados o direcciones sin coordenadas.

## Resultados

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

`passed` significa que la distancia calculada por el servidor está dentro del radio y la precisión es aceptable. Los resultados distintos de `passed` se permiten en modo `warning`, salvo que la política futura defina una excepción distinta.

## Seguridad

- El cliente nunca envía un resultado confiable; el servidor lo calcula.
- El pedido, repartidor y asignación se obtienen del contexto autenticado y se comprueban con ownership/capacidad.
- Se mantienen nonce, validación de estado e idempotencia del flujo existente.
- No se guarda un historial de coordenadas ni se implementa seguimiento continuo.
- No se incluyen coordenadas crudas en notas de pedido, emails ni eventos.
- Se guarda el estado, distancia, radio, precisión, fecha de validación y coordenadas GPS del repartidor.

## Limitaciones del navegador

La captura requiere HTTPS y permiso del usuario. Una página web no puede garantizar ubicación si el navegador está suspendido, el GPS está desactivado o el sistema operativo deniega el permiso. El formulario ofrece reintento y, en modo `warning`, una continuación explícita con advertencia.

## Visualización en el mapa administrativo

Cuando el pedido tiene validación registrada, el mapa en el panel de administración muestra:

- 🔴 **Marcador rojo** (arrastrable): destino geocodificado.
- 🟢 **Marcador verde** (fijo): ubicación GPS del repartidor al completar.
- 🟢 **Círculo verde** semi-transparente con borde punteado: radio de validación configurado.

El círculo se dibuja con 64 puntos geodésicos corregidos por latitud y no requiere librerías externas. Si se modifica el radio en los ajustes, el cambio se refleja al recargar la página.

## Archivos principales

- `includes/location/class-localpilot-location-validation-service.php`
- `includes/deliveries/class-localpilot-order-delivery-meta.php`
- `includes/deliveries/class-localpilot-delivery-transition-service.php`
- `includes/integrations/class-localpilot-my-account.php`
- `includes/integrations/class-localpilot-settings.php`
- `templates/my-account/deliveries/detail.php`
- `public/js/localpilot-public.js`
- `admin/partials/localpilot-order-delivery-panel.php`
- `admin/js/localpilot-mapbox.js` — mapa con dos marcadores y círculo de radio

## Fuera de alcance

- GPS en vivo.
- Ubicación en segundo plano.
- Historial de recorridos.
- ETA, rutas y geocercas dinámicas.
- WebSockets o aplicación nativa.
