# Integración Mapbox

## Alcance

Geocodificar la dirección del pedido, guardar el resultado, mostrar el destino en la entrega y permitir corrección manual administrativa. Sus coordenadas también pueden usarse para validar una posición puntual al completar y para calcular, bajo demanda, una ruta efímera desde la ubicación actual del repartidor. No incluye tracking en vivo, navegación turn-by-turn ni optimización.

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
- rutas para repartidores (`lclplt_enable_routes`);
- perfil predeterminado (`driving-traffic`, `driving`, `cycling` o `walking`);
- selector temporal de perfil para el repartidor.

El token público debe ser dedicado, tener los scopes mínimos y restringirse por URL desde Mapbox. No registrar el token en logs, eventos o errores visibles. Directions API factura por solicitud de acuerdo con los precios vigentes de Mapbox; abrir la página, mover el mapa o cambiar de perfil nunca debe iniciar una petición.

## Rutas bajo demanda para el repartidor

La ruta aparece únicamente en el detalle de una entrega propia con estado `assigned`, `accepted` u `out_for_delivery`, siempre que Mapbox y rutas estén activados y existan token y coordenadas de destino.

Flujo:

1. El repartidor selecciona un perfil y pulsa **Calcular ruta desde mi ubicación**.
2. El navegador solicita una única posición con `navigator.geolocation.getCurrentPosition()`.
3. El frontend llama directamente a Directions API v5; WordPress no recibe el origen.
4. La respuesta se valida antes de dibujarla.
5. Mapbox GL muestra un marcador azul de origen, el marcador rojo de destino y una línea GeoJSON.
6. La interfaz muestra distancia, duración estimada y perfil.
7. Recalcular exige otra acción explícita y sustituye la ruta en memoria.

Solicitud:

```text
GET https://api.mapbox.com/directions/v5/mapbox/{profile}/{origin_lng},{origin_lat};{destination_lng},{destination_lat}
alternatives=false
geometries=geojson
overview=full
steps=false
language={idioma configurado}
access_token={token público restringido}
```

El cliente acepta únicamente los cuatro perfiles configurados y valida `code=Ok`, `routes[0]`, geometría `LineString`, coordenadas, distancia y duración. Usa `AbortController` y un número de secuencia para ignorar respuestas anteriores. `NoRoute`, `NoSegment`, errores de autorización, rate limit, red o payload inválido degradan al mapa del destino y al enlace **Abrir navegación**.

La ruta es efímera: origen, geometría, distancia y duración no se guardan en WordPress, eventos, notas, emails, cookies ni almacenamiento web. No se usa `watchPosition()` y la captura de ruta es independiente de la validación al completar.

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
16. Añadir cálculo manual de ruta solo para entregas activas propias.
17. Dibujar la geometría como fuente/capas GeoJSON sin recrear el mapa.
18. Mostrar distancia y duración estimadas, sin instrucciones giro a giro.
19. Mantener una sola petición activa y no recalcular al cambiar el perfil.
20. Conservar la ruta solo en memoria y mantener Google Maps como fallback externo.

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
- Las rutas solo aparecen en `assigned`, `accepted` y `out_for_delivery`.
- Abrir el detalle o cambiar el perfil no consume Directions API.
- Cada clic explícito produce como máximo una solicitud de ruta.
- Los perfiles no incluidos en la allowlist caen a `driving-traffic`.
- El origen se distingue del destino por forma, etiqueta y color.
- Distancia y duración se identifican claramente como estimaciones.
- Cerrar la página elimina todos los datos de la ruta porque nunca se persisten.
- Permiso denegado, timeout, `NoRoute`, `NoSegment`, 401/403, 429 y fallos de red conservan el fallback externo.

## Riesgos

- Costos/cuotas: caché persistente y reintentos limitados.
- Dirección ambigua: estado visible y corrección manual.
- Token abusado: restricciones por origen y mínimo alcance.
- Cambios de API/SDK: cliente aislado.
- Dependencia remota en frontend: fallback textual.
- Directions API tiene facturación independiente: cálculo estrictamente manual, monitoreo de consumo y token restringido.
- El token público puede inspeccionarse: restricciones por origen y scopes mínimos son obligatorios; la ofuscación no es seguridad.
- La geolocalización requiere HTTPS y permiso del navegador.

## Fuera de alcance

- posición del conductor en vivo;
- tracking continuo o ubicación en segundo plano;
- navegación turn-by-turn embebida;
- optimización multi-parada;
- ETA compartido o seguimiento de progreso;
- instrucciones giro a giro y voz;
- recálculo automático por movimiento o desvíos;
- geocercas;
- matriz de distancias.

## Definición de terminado

Mapbox está encapsulado, configurable y tolerante a fallos; las coordenadas del destino se persisten en el pedido, solo pueden corregirse durante una entrega activa y el mapa administrativo utiliza snapshots históricos en estados terminales. La ruta del repartidor es manual, temporal y no persistente. El SDK solo se carga en contextos autorizados y necesarios.
