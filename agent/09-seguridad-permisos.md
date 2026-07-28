# Seguridad y permisos

## Alcance

Definir controles obligatorios para Admin, Mi cuenta, REST, archivos y servicios externos. Este documento es transversal y acompaña a todos los frentes.

## Principios

1. El rol no basta: comprobar capacidad.
2. La capacidad no basta para drivers: comprobar ownership.
3. El nonce no es autorización.
4. Nunca confiar en `order_id`, `driver_id`, estado o MIME enviados por cliente.
5. Validar transición usando estado persistido actual.
6. Escapar al renderizar, sanitizar al recibir.
7. Respuestas de acceso no deben confirmar si existe un pedido ajeno.

## Matriz de autorización

| Acción | Driver propietario | Gestor | Cliente |
|---|---:|---:|---:|
| Ver entrega | Sí | Sí | No |
| Aceptar/iniciar | Con capacidad y transición válida | Según política | No |
| Completar/fallar | Con capacidad y transición válida | Según política | No |
| Subir evidencia | Con capacidad y ownership | Sí | No |
| Validar ubicación al completar | Sí, con posición puntual y entrega propia | Puede revisar el resultado | No |
| Calcular ruta efímera | Sí, entrega propia y activa | No desde la vista del driver | No |
| Asignar/reasignar | No | `lclplt_manage_deliveries` | No |
| Editar ubicación | No | `lclplt_manage_deliveries`, solo entrega activa | No |
| Ver evidencia | Propia si se decide mostrar | Sí | No |

## Controles por superficie

### Admin

- `current_user_can( 'lclplt_manage_deliveries' )`;
- nonce por acción;
- ID normalizado;
- redirect seguro;
- notices sin datos sensibles.

### Mi cuenta

- sesión;
- capacidad específica;
- asignación activa cuyo `driver_id` coincide;
- transición válida;
- POST para mutaciones;
- nonce independiente por formulario.

### REST

- `permission_callback` real;
- cookie auth + REST nonce para frontend autenticado;
- schema, sanitize y validate callbacks;
- códigos HTTP coherentes;
- no autorización dentro de callback tardío solamente.

### Mapbox

- token nunca en logs;
- URL construida con helpers;
- timeout;
- respuesta no confiable validada;
- no SSRF mediante URL arbitraria.
- token público dedicado con scopes mínimos y restricciones por URL;
- perfiles Directions limitados a una allowlist cerrada;
- una solicitud de ruta solo después de una acción explícita;
- `AbortController` y protección contra respuestas fuera de orden;
- monitoreo de consumo y rotación del token ante abuso.

### Ruta efímera del repartidor

- solo se renderiza después de validar sesión, capacidad, ownership y estado activo;
- usa las coordenadas persistidas del destino, nunca un destino suministrado por el navegador;
- la posición actual se obtiene una vez con permiso explícito y se envía directamente a Mapbox Directions;
- WordPress no recibe ni persiste origen, geometría, distancia o duración;
- no se incluyen datos de ruta en eventos, notas, emails, analytics, cookies, `localStorage` o logs;
- no existe endpoint REST para rutas en la primera versión;
- no usa `watchPosition()`, GPS en segundo plano ni recálculo automático;
- cambiar de perfil no ejecuta una petición; el usuario debe confirmar otro cálculo;
- `NoRoute`, `NoSegment`, 401/403, 429, timeout y red fallan de forma segura y conservan la navegación externa;
- una Content Security Policy debe permitir únicamente los orígenes Mapbox requeridos.

### Evidencias

- tamaño y MIME real;
- nombre controlado;
- sin SVG/ejecutables;
- endpoint de consulta autorizado;
- no rutas físicas en errores.

### Validación puntual de ubicación

- requiere sesión, capacidad, ownership y transición válida;
- el servidor obtiene las coordenadas del destino desde el pedido, nunca del cliente;
- latitud, longitud, precisión y timestamp se validan por tipo, rango y antigüedad;
- el resultado se calcula en servidor; no se acepta un `passed` enviado por el navegador;
- el radio solo procede de los ajustes del gestor y tiene límites configurados;
- no se guardan recorridos ni tracking continuo;
- las coordenadas GPS del repartidor se persisten como metadatos del pedido y solo se muestran en el mapa administrativo (requiere `lclplt_manage_deliveries`);
- el destino usado por la validación se conserva como snapshot privado para que una configuración posterior no altere la auditoría;
- no se incluyen coordenadas crudas en notas, emails, mensajes visibles ni `event_data`;
- la captura requiere HTTPS y permiso explícito del navegador;
- los estados pueden ser `passed`, `outside_radius`, `permission_denied`, `unavailable`, `timeout`, `stale`, `low_accuracy`, `no_destination` o `disabled`.

## Archivos a tocar

Este frente idealmente no implementa una segunda lógica. Revisa y añade utilidades pequeñas:

- `includes/helpers/class-*-capabilities.php`
- `includes/helpers/class-*-request.php`
- callbacks de permisos en controladores
- tests de autorización

## Tareas

1. Crear matriz de permisos ejecutable en pruebas.
2. Revisar cada endpoint/handler antes de merge.
3. Probar IDOR con dos drivers.
4. Probar CSRF con nonces ausentes/incorrectos.
5. Probar carrera y request repetido.
6. Revisar escaping por contexto: HTML, atributo, URL, JSON.
7. Revisar SQL preparado.
8. Revisar secretos y logs.
9. Definir retención de IP y evidencia.
10. Validar desinstalación opt-in.
11. Probar manipulación de coordenadas, resultado enviado, timestamp futuro/antiguo y precisión falsa.
12. Verificar que la política `warning` no se convierta en autorización para cambiar el radio desde el frontend.
13. Manipular el POST administrativo para intentar reasignar, retirar o corregir una entrega terminal; todas deben rechazarse en servidor.
14. Verificar que eventos de geocodificación, corrección y completado no contengan coordenadas crudas.
15. Manipular el DOM para habilitar rutas en una entrega terminal y confirmar que el servidor nunca expone un endpoint de mutación o persistencia asociado.
16. Probar perfiles alterados, respuestas Directions inválidas y coordenadas fuera de rango.
17. Verificar que abrir la página y cambiar de perfil produzcan cero solicitudes Directions.
18. Confirmar que origen, geometría, distancia y duración desaparecen al recargar y no existen en base de datos, eventos ni logs.

## Criterios de aceptación

- Driver A nunca ve ni muta pedidos de Driver B.
- Un nonce válido sin permiso no autoriza.
- Un gestor sin nonce no muta.
- Un estado enviado por cliente no salta transiciones.
- Los errores ajenos son indistinguibles.
- No hay SQL concatenado con input.
- No aparecen tokens, rutas o stack traces en UI/log normal.
- El token público visible en el navegador está limitado por scopes y orígenes autorizados.
- El cálculo de ruta no amplía permisos ni crea un endpoint accesible por clientes u otros repartidores.
- La ruta del repartidor no persiste ubicación ni recorrido y nunca sustituye la validación de ubicación al completar.
- La ubicación puntual no revela coordenadas a usuarios no autorizados.
- Una posición fuera del radio se bloquea o advierte según la configuración persistida.
- Cambiar el radio global después de completar no altera el radio ni el destino auditados en el pedido.
- Una entrega terminal no puede mutarse aunque se omitan las restricciones de la interfaz.
- Los uploads maliciosos se rechazan.
- Todas las mutaciones son POST/REST mutativo.

## Riesgos

- Capabilities otorgadas a `shop_manager` sin aprobación.
- Endpoints de evidencia servidos por URL pública.
- Race conditions en asignar/completar.
- Filtración por caches de Mi cuenta.
- Notas o emails con PII excesiva.

## Definición de terminado

Existe evidencia de pruebas negativas para cada superficie, la autorización se ejecuta en servidor y combina capacidad, ownership y estado, los secretos están protegidos y las decisiones de privacidad están documentadas.
