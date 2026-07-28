=== LocalPilot – Local Delivery Drivers for WooCommerce ===
Contributors: racmanuel
Donate link: https://racmanuel.dev/
Tags: localpilot, local, delivery, drivers, woocommerce
Requires at least: 6.9
Tested up to: 7.0.2
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Asigna pedidos de WooCommerce a repartidores locales, gestiona entregas desde Mi cuenta y registra evidencia con Mapbox.

== Description ==

LocalPilot extiende WooCommerce para operar entregas locales con repartidores. Está construido sobre las pantallas nativas de WooCommerce — sin tableros duplicados.

= Funcionalidades actuales (v1.0.0) =

* **Repartidores nativos**: crea usuarios con el rol `localpilot_driver` desde Usuarios. Campos de perfil: teléfono, vehículo, patente, capacidad y notas.
* **Asignación desde el pedido**: asigna, reasigna o retira repartidores directamente desde el editor del pedido de WooCommerce.
* **Columnas en lista de pedidos**: columnas Repartidor, Estado de entrega, Fecha y Ubicación en la lista de pedidos (HPOS y almacenamiento heredado).
* **Filtros**: filtra pedidos por repartidor y estado de entrega.
* **Estados de entrega**: `unassigned` → `assigned` → `accepted` → `out_for_delivery` → `delivered` / `failed` / `cancelled`.
* **Ajustes en WooCommerce**: configura estados elegibles, aceptación obligatoria, evidencia, Mapbox y más desde WooCommerce → Ajustes → LocalPilot.
* **Eventos y notas**: cada acción genera un evento estructurado y una nota privada en el pedido.
* **HPOS**: compatible con High-Performance Order Storage y almacenamiento heredado.
* **Mi cuenta — Mis entregas**: los repartidores aceptan, inician, completan o fallan entregas desde el frontend.
* **Resumen operativo para repartidores**: contadores por estado, filtros visibles y listado responsive con acciones contextuales.
* **Mapbox**: geocodificación automática de direcciones al asignar, mapa interactivo en Mi cuenta y mapa admin con corrección manual de coordenadas.
* **Evidencia fotográfica**: sube una foto (JPG/PNG/WebP) al completar o fallar una entrega. Vista previa en el panel del pedido.
* **Correos nativos de WooCommerce**: 5 notificaciones configurables desde WooCommerce → Ajustes → Correos electrónicos (asignado, retirado, en reparto, completado, fallido).

= En desarrollo (1.1.6-dev) =

* **Validación puntual de ubicación**: al completar una entrega, solicita una ubicación del navegador y compara la distancia con el destino geocodificado. El radio y la política de advertencia/bloqueo son configurables. No es GPS en vivo ni guarda recorridos.
* **Panel operativo rediseñado**: resumen, progreso, prueba de entrega, mapa de auditoría y timeline de actividad en el editor nativo del pedido.
* **Auditoría geográfica histórica**: conserva el radio y destino usados al validar, muestra destino y GPS de entrega y bloquea cambios en entregas cerradas.
* **Detalle de entrega rediseñado**: prioriza el mapa y la ruta cuando están disponibles, continúa con cliente y destino, y ofrece acciones rápidas, productos compactos, prueba de entrega, formularios contextuales y timeline traducido con diseño responsive.
* **Rutas Mapbox bajo demanda**: en entregas activas, el repartidor puede calcular desde su ubicación una ruta con marcador de origen, línea, distancia y duración estimadas.
* **Perfiles configurables**: auto con tráfico, auto, bicicleta y caminando, con perfil global y selector temporal opcional.
* **Privacidad de rutas**: el origen, la geometría, la distancia y la duración solo existen mientras la página está abierta; LocalPilot no los guarda ni implementa tracking continuo.

== Installation ==

1. Asegúrate de tener WooCommerce 10.9+ activo.
2. Ve a Plugins → Añadir nuevo → Subir plugin y selecciona el archivo ZIP.
3. Activa el plugin.
4. Ve a WooCommerce → Ajustes → LocalPilot para configurar las opciones generales y de Mapbox.
5. Crea uno o más usuarios con el rol `localpilot_driver` desde Usuarios.
6. Activa a los repartidores desde su perfil (checkbox "Activo").
7. Ve a WooCommerce → Pedidos, abre un pedido en estado Processing u On-hold y asígnale un repartidor desde el panel LocalPilot.
8. El repartidor puede ver sus entregas desde Mi cuenta → Mis entregas en el frontend.

== Frequently Asked Questions ==

= ¿Qué versión de WooCommerce necesito? =

WooCommerce 10.9 o superior. El plugin declara compatibilidad con HPOS (Custom Order Tables) y funciona también con el almacenamiento heredado.

= ¿Los repartidores necesitan acceder a wp-admin? =

No. Los repartidores pueden operar sus entregas desde Mi cuenta en el frontend (disponible en M3). En M2 solo los administradores y gestores de tienda gestionan asignaciones desde wp-admin.

= ¿Qué datos almacena LocalPilot? =

Asignaciones, eventos del ciclo de entrega, metadatos en pedidos (coordenadas puntuales, snapshot del destino, receptor, evidencia y resumen de validación de ubicación) y metadatos de perfil de repartidores. La validación puntual no guarda un historial de recorridos y las coordenadas crudas no se incluyen en eventos, notas ni emails. Por defecto los datos se conservan al desinstalar. Puedes activar la eliminación en Ajustes → Datos y privacidad.

= ¿Cuándo puede un repartidor calcular una ruta? =

Únicamente al abrir el detalle de una entrega propia en estado Asignado, Aceptado o En reparto, siempre que Mapbox y Rutas para repartidores estén activados y el destino tenga coordenadas. La ruta requiere pulsar un botón: nunca se calcula automáticamente al abrir la página o cambiar de perfil.

= ¿LocalPilot guarda la ruta o sigue al repartidor? =

No. La ubicación se solicita una sola vez por cada cálculo y se usa directamente con Mapbox Directions API. LocalPilot no guarda el origen, la línea, la distancia ni la duración, no usa GPS en segundo plano y no implementa seguimiento continuo. La captura usada para calcular una ruta es independiente de la validación puntual realizada al completar.

= ¿Las rutas de Mapbox pueden generar costos? =

Sí. Cada cálculo manual es una solicitud a Mapbox Directions API y está sujeto a sus precios y términos vigentes. Usa un token público dedicado con scopes mínimos, restricciones por URL y monitoreo de consumo antes de habilitar rutas en producción.

= ¿Soporta multisite? =

Sí, el plugin funciona en redes multisite de WordPress. La detección de WooCommerce incluye plugins activados en toda la red.

= ¿Qué pasa si desactivo un repartidor con entregas activas? =

El repartidor deja de aparecer en el selector de asignaciones, pero sus entregas activas no se cancelan automáticamente. Debes reasignarlas explícitamente.

== Screenshots ==

1. Panel LocalPilot en el editor de pedido (asignación, mapa, evidencia).
2. Columnas en la lista de pedidos (Repartidor, Estado, Fecha, Ubicación).
3. Ajustes de LocalPilot en WooCommerce (General, Flujo, Mapbox, Datos).
4. Mis entregas — lista y detalle para el repartidor.
5. Email de pedido asignado en WooCommerce → Correos electrónicos.

== Changelog ==

= 1.1.6-dev =
* Rutas Mapbox bajo demanda para entregas activas desde Mi cuenta.
* Selector configurable entre auto con tráfico, auto, bicicleta y caminando.
* Marcador temporal de origen, línea GeoJSON, distancia y duración estimadas.
* Manejo accesible de permisos GPS y errores de Directions API con fallback externo.
* Ubicación y resultados de ruta efímeros, sin persistencia ni tracking continuo.

= 1.1.5-dev =
* Detalle de Mis entregas rediseñado para entregas activas y cerradas.
* Cliente, destino, navegación, productos y prueba de entrega organizados en tarjetas responsive.
* Completar se presenta como acción principal y reportar fallo como panel secundario plegable.
* Estados accesibles para GPS, carga de Mapbox y timeline de actividad traducido.

= 1.1.4-dev =
* Validación puntual de ubicación al completar una entrega.
* Snapshot privado del destino y radio usados en la validación.
* Panel LocalPilot rediseñado con resumen, progreso, prueba, mapa y timeline.
* Mapa administrativo con marcador dual, círculo histórico, línea de distancia y encuadre automático.
* Entregas terminales en modo de solo lectura.

= 1.0.0 =
* Versión inicial del MVP.
* Administración nativa de entregas con asignación, reasignación y retiro.
* Columnas y filtros en la lista de pedidos (HPOS + legacy).
* Panel de asignación en el editor del pedido.
* Mi cuenta → Mis entregas: aceptar, iniciar, completar y fallar.
* Mapbox: geocodificación automática, mapa en Mi cuenta, mapa admin con corrección manual.
* Evidencia fotográfica (JPG/PNG/WebP) con validación MIME real.
* 5 correos nativos de WooCommerce configurables.
* Ajustes en WooCommerce con 4 secciones.
* Compatibilidad HPOS y almacenamiento heredado.
* Soporte multisite.
* Traducciones: español (México) incluido.
* Rol `localpilot_driver` con capacidades propias.
* Perfil de repartidor con campos personalizados.
* Ajustes en WooCommerce → Ajustes → LocalPilot.
* Declaración de compatibilidad HPOS.
* Roles administrador y shop_manager autorizados para gestionar entregas.
* Soporte multisite.
* Textos en español traducibles mediante el text domain `localpilot`.