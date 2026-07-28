# Validación puntual de ubicación al completar

## Alcance

Esta funcionalidad no implementa GPS en vivo. Obtiene una única ubicación del navegador cuando el repartidor intenta completar una entrega y compara la posición con las coordenadas del destino ya geocodificado.

Esta captura no debe confundirse con **Ruta al destino**. El planificador de rutas puede solicitar otra posición puntual mientras la entrega está activa, pero esa posición se envía directamente a Mapbox, solo dibuja una ruta temporal y no participa en la auditoría, el formulario ni la decisión de completar.

## Flujo

1. El gestor activa la validación y configura el radio en **WooCommerce → Ajustes → LocalPilot → Flujo de entrega**.
2. La entrega debe estar en `out_for_delivery`.
3. El repartidor pulsa **Completar entrega**.
4. El navegador solicita permiso de ubicación mediante `navigator.geolocation.getCurrentPosition()`.
5. El cliente envía latitud, longitud, precisión, timestamp y estado técnico.
6. El servidor obtiene las coordenadas del destino desde `Localpilot_Order_Delivery_Meta`.
7. El servidor calcula la distancia Haversine y compara contra el radio configurado.
8. El completado continúa, genera advertencia o se bloquea según la política configurada.
9. Se conserva el resumen de la validación en el pedido y en el evento de completado, sin coordenadas crudas en `event_data`.
10. Las coordenadas GPS del repartidor se guardan como `_lclplt_delivery_location_lat/lng`.
11. El destino usado se guarda una vez en `_lclplt_location_validation_target_lat/lng`.
12. En el panel administrativo, el mapa muestra destino, GPS de entrega, línea de distancia y círculo del radio histórico.

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
- La posición del planificador de ruta no se reutiliza como evidencia ni rellena los campos POST de esta validación.
- La geometría, distancia y duración mostradas por Directions API no se consideran prueba de llegada.
- No se incluyen coordenadas crudas en notas de pedido, emails ni eventos.
- Se guarda el estado, distancia, radio, precisión, fecha, snapshot del destino y coordenadas GPS puntuales del repartidor.

## Limitaciones del navegador

La captura requiere HTTPS y permiso del usuario. Una página web no puede garantizar ubicación si el navegador está suspendido, el GPS está desactivado o el sistema operativo deniega el permiso. El formulario ofrece reintento y, en modo `warning`, una continuación explícita con advertencia.

## Visualización en el mapa administrativo

Cuando el pedido tiene validación registrada, el mapa en el panel de administración muestra:

- **Marcador rojo**: destino geocodificado; arrastrable solo durante una entrega activa.
- **Marcador verde** fijo: ubicación GPS del repartidor al completar.
- **Círculo verde** semi-transparente con borde punteado: radio utilizado al validar.
- **Línea punteada**: separación visual entre destino y GPS.

El círculo se dibuja con 64 puntos calculados geodésicamente y no requiere una librería geométrica externa. Al completar se conservan radio y destino como snapshot: modificar después el ajuste global no cambia la representación histórica. Los pedidos `delivered`, `failed` y `cancelled` son de solo lectura.

En entregas activas, arrastrar el destino actualiza inmediatamente círculo, línea y campos. Los controles permiten encuadrar toda la zona, centrar los marcadores, copiar el GPS y abrirlo en Google Maps. Si Mapbox falla, dirección y coordenadas continúan visibles como fallback textual.

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
- ETA compartido, navegación en vivo y geocercas dinámicas dentro del flujo de validación.
- Uso de la ruta visible como validación de llegada o evidencia de entrega.
- WebSockets o aplicación nativa.
