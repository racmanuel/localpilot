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
| Asignar/reasignar | No | `lclplt_manage_deliveries` | No |
| Editar ubicación | No | `lclplt_manage_deliveries` | No |
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

### Evidencias

- tamaño y MIME real;
- nombre controlado;
- sin SVG/ejecutables;
- endpoint de consulta autorizado;
- no rutas físicas en errores.

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

## Criterios de aceptación

- Driver A nunca ve ni muta pedidos de Driver B.
- Un nonce válido sin permiso no autoriza.
- Un gestor sin nonce no muta.
- Un estado enviado por cliente no salta transiciones.
- Los errores ajenos son indistinguibles.
- No hay SQL concatenado con input.
- No aparecen tokens, rutas o stack traces en UI/log normal.
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
