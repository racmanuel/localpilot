# Integración Mapbox

## Alcance

Geocodificar la dirección del pedido, guardar el resultado, mostrar el destino en la entrega y permitir corrección manual administrativa. Sus coordenadas también pueden usarse para validar una posición puntual al completar. No incluye tracking en vivo ni optimización.

## Dependencias

- Metadatos del pedido.
- Settings nativos de WooCommerce.
- Editor de pedido.
- Mi cuenta.
- Servicio de validación puntual de ubicación, si se activa.
- Seguridad para endpoints administrativos.

## Configuración

En **WooCommerce → Ajustes → LocalPilot → Mapbox**:

- activar mapas;
- access token;
- estilo;
- zoom;
- país preferido;
- idioma;
- geocodificación automática;
- corrección manual.

El token público debe restringirse por URL desde Mapbox. No registrar el token en logs, eventos o errores visibles.

## Flujo de geocodificación

1. Confirmar que el pedido es elegible.
2. Construir una dirección estable desde envío y fallback documentado a facturación.
3. Normalizar espacios y componentes, sin cambiar semántica.
4. Consultar Mapbox desde servidor con timeout.
5. Validar HTTP y forma de respuesta.
6. Seleccionar resultado conforme a criterios explícitos.
7. Guardar coordenadas, place ID, dirección resuelta, fecha y estado.
8. Registrar evento/note sin datos secretos.

Estados:

```text
pending
success
failed
manual
```

No geocodificar en cada render. Los reintentos son explícitos o asíncronos y acotados.

## Archivos a crear o tocar

- `includes/maps/class-*-mapbox-client.php`
- `includes/maps/class-*-geocoding-service.php`
- `includes/maps/class-*-location-service.php`
- integración de settings en `includes/integrations/`
- partial del mapa admin
- partial del mapa de Mi cuenta
- JS/CSS condicionales

## Contrato del cliente

Entrada: dirección normalizada + opciones de país/idioma.  
Salida exitosa:

```text
latitude
longitude
place_id
formatted_address
relevance
```

Salida fallida: código interno estable, mensaje seguro y contexto técnico sanitizado para logs.

## Tareas

1. Implementar cliente con WordPress HTTP API.
2. Definir timeouts y errores recuperables.
3. Implementar servicio con caché en meta.
4. Agregar ajuste de token sin exponerlo fuera de páginas necesarias.
5. Mostrar marcador en detalle del driver.
6. Implementar mapa admin con marcador arrastrable y modo dual si hay GPS de entrega.
7. Guardar corrección manual con nonce/capacidad.
8. Añadir enlace de navegación por coordenadas usando opción soportada.
9. Cargar SDK/estilos únicamente cuando se renderiza mapa.
10. Documentar atribución requerida por Mapbox.
11. Dibujar círculo geodésico del radio de validación si la función está activa.
12. En pedidos validados, usar el destino y radio guardados en el snapshot histórico, no la configuración global vigente.
13. Recalcular el círculo y la línea de distancia al arrastrar el destino de una entrega activa.
14. Ajustar el viewport para incluir destino, GPS y perímetro mediante `LngLatBounds`.
15. Bloquear el marcador y la corrección manual en estados terminales.

## Criterios de aceptación

- Un pedido se geocodifica una sola vez salvo reintento.
- Un fallo de Mapbox no impide asignar ni abrir el pedido.
- El admin puede corregir coordenadas y queda evento `delivery_location_updated`.
- La corrección manual no se sobrescribe automáticamente.
- Latitud/longitud se validan por rango.
- El token no aparece en logs ni páginas ajenas.
- Mapa y atribución cumplen requisitos de Mapbox.
- La UI ofrece dirección textual si JS o Mapbox fallan.
- El mapa admin muestra un marcador de destino y, cuando existe, un segundo marcador con la ubicación GPS registrada al completar.
- El mapa admin dibuja el radio histórico alrededor del destino usado por la validación.
- Una línea visual une destino y GPS sin sustituir la distancia calculada por el servidor.
- El círculo, campos y marcador permanecen sincronizados durante una corrección activa.
- Los controles permiten encuadrar toda la zona y centrar cada punto.
- La leyenda y las coordenadas ofrecen una alternativa textual al mapa.

## Riesgos

- Costos/cuotas: caché persistente y reintentos limitados.
- Dirección ambigua: estado visible y corrección manual.
- Token abusado: restricciones por origen y mínimo alcance.
- Cambios de API/SDK: cliente aislado.
- Dependencia remota en frontend: fallback textual.

## Fuera de alcance

- posición del conductor en vivo;
- tracking continuo o ubicación en segundo plano;
- navegación turn-by-turn embebida;
- optimización multi-parada;
- ETA;
- geocercas;
- matriz de distancias.

## Definición de terminado

Mapbox está encapsulado, configurable y tolerante a fallos; las coordenadas se persisten en el pedido, solo pueden corregirse durante una entrega activa y el mapa utiliza snapshots históricos en estados terminales. El SDK solo se carga en contextos autorizados y necesarios.
