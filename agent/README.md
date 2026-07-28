# LocalPilot MVP — paquete de coordinación multiagente

**Producto:** LocalPilot – Local Delivery Drivers for WooCommerce  
**Objetivo:** entregar un MVP nativo de WooCommerce para asignar pedidos a repartidores locales, operar entregas desde **Mi cuenta → Mis entregas**, geocodificar destinos con Mapbox, registrar evidencia y conservar trazabilidad.  
**Base existente:** WordPress Plugin Boilerplate de DevinVinson ya generado por el propietario del proyecto.

## Regla principal

Este paquete **no recrea, sustituye ni renombra el boilerplate**. Cada ruta mencionada es relativa a la raíz del plugin existente. Si el generador produjo nombres distintos, se conserva el nombre real y se adapta únicamente la referencia.

No introducir Composer, namespaces, un contenedor de dependencias, un framework CSS o una aplicación JavaScript completa salvo que ya existan en el repositorio y el integrador lo autorice.

## Resultado funcional del MVP

1. El administrador gestiona pedidos en las pantallas nativas de WooCommerce.
2. Un pedido elegible puede geocodificarse y asignarse manualmente a un repartidor activo.
3. El repartidor ve únicamente sus asignaciones en **Mi cuenta → Mis entregas**.
4. El repartidor puede aceptar, iniciar, completar o reportar el fallo de la entrega según transiciones válidas.
5. La entrega completada registra receptor, nota y evidencia según la configuración.
6. Mapbox muestra el destino y permite corrección manual administrativa.
7. Los eventos importantes generan datos estructurados y una nota legible en el pedido.
8. Los correos se registran como emails nativos de WooCommerce.
9. Todo el acceso a pedidos usa CRUD de WooCommerce y funciona con HPOS activo.

## Documentos por frente

| Archivo | Frente | Propietario sugerido |
|---|---|---|
| [01-arquitectura-boilerplate.md](01-arquitectura-boilerplate.md) | Adaptación y contratos base | Agente integrador |
| [02-datos-hpos.md](02-datos-hpos.md) | Persistencia, esquema y HPOS | Agente datos |
| [03-integracion-pedidos-woocommerce.md](03-integracion-pedidos-woocommerce.md) | Lista y editor de pedidos | Agente WooCommerce Admin |
| [04-roles-repartidores.md](04-roles-repartidores.md) | Rol, capacidades y perfiles | Agente identidad |
| [05-mi-cuenta-mis-entregas.md](05-mi-cuenta-mis-entregas.md) | Experiencia del repartidor | Agente frontend WooCommerce |
| [06-mapbox.md](06-mapbox.md) | Geocodificación y mapa | Agente mapas |
| [07-emails-woocommerce.md](07-emails-woocommerce.md) | Correos nativos | Agente emails |
| [08-evidencias.md](08-evidencias.md) | Carga y consulta de prueba | Agente evidencias |
| [09-seguridad-permisos.md](09-seguridad-permisos.md) | Modelo de autorización | Agente seguridad |
| [10-pruebas-qa.md](10-pruebas-qa.md) | Estrategia y matriz de pruebas | Agente QA |
| [11-roadmap.md](11-roadmap.md) | Fases, hitos y fuera de alcance | Product owner |
| [12-coordinacion-multiagente.md](12-coordinacion-multiagente.md) | Protocolo de ejecución | Coordinador |
| [13-contratos-compartidos.md](13-contratos-compartidos.md) | Estados, hooks y errores | Integrador |
| [14-checklist-integracion-release.md](14-checklist-integracion-release.md) | Ensamble y salida | Integrador + QA |

## Orden recomendado

```text
Arquitectura + contratos
        ↓
Datos/HPOS + roles
        ↓
Servicio de asignaciones y transiciones
        ↓
Pedidos Admin + Mi cuenta + Mapbox
        ↓
Evidencias + emails
        ↓
Seguridad transversal + QA + release
```

Los frentes de interfaz pueden avanzar en paralelo después de congelar los contratos de `13-contratos-compartidos.md`. La seguridad no se deja para el final: su agente revisa cada entrega parcial.

## Identidad técnica confirmada por el boilerplate existente

- Directorio/slug y text domain: `localpilot`.
- Archivo principal esperado: `localpilot.php` (confirmar en el repositorio real).
- Paquete/clases: `Localpilot` y `Localpilot_*`.
- Prefijo de funciones, hooks, opciones y tablas: `lclplt_`.
- Prefijo de metadatos privados: `_lclplt_`.
- Prefijo de constantes: `LOCALPILOT_`.
- Namespace REST: `localpilot/v1`.
- Rol: `localpilot_driver`.
- Endpoint de cuenta: `mis-entregas`.
- Versión inicial/stable tag: `1.0.0`.
- WordPress mínimo: `6.0`.
- PHP mínimo: `7.4`.
- Tested up to declarado: `7.0.2`.
- Zona horaria para persistencia: UTC; formateo mediante WordPress/WooCommerce.
- Pedidos: solo `wc_get_order()`, `wc_get_orders()`, `WC_Order_Query` y métodos de `WC_Order`.
- Salida HTML escapada en el último momento y entrada sanitizada según tipo.

Ejemplo de clase compatible con la convención existente: `Localpilot_Assignment_Service`.

Los metadatos de distribución y el header entregados por el propietario se registran en `01-arquitectura-boilerplate.md`. No cambiar estas convenciones a `localpilot-delivery` o `lpd_`.

## Regla de propiedad de archivos

Los siguientes archivos son de **propiedad exclusiva del agente integrador** durante el trabajo paralelo:

- archivo principal del plugin;
- `includes/class-*-loader.php`;
- `includes/class-*.php` principal;
- `includes/class-*-activator.php`;
- `includes/class-*-deactivator.php`;
- `uninstall.php`;
- archivos de configuración global o carga de dependencias.

Los demás agentes crean o modifican solo los archivos asignados en su documento. Si necesitan un hook en el bootstrap, entregan al integrador:

1. clase y constructor;
2. hooks requeridos;
3. prioridad y número de argumentos;
4. dependencias;
5. fragmento mínimo de registro propuesto.

## Puertas de calidad

Una tarea no está terminada hasta cumplir:

- sintaxis PHP válida y estándares del repositorio;
- permisos y nonce comprobados en el servidor;
- compatibilidad HPOS probada;
- no acceso directo a tablas de pedidos;
- textos traducibles con el text domain correcto;
- pruebas del camino feliz y al menos un fallo relevante;
- sin errores ni advertencias con `WP_DEBUG` activo;
- documentación del cambio y handoff al integrador.

## Cómo usar este paquete

1. El coordinador copia este directorio a `docs/localpilot-mvp/` del repositorio del plugin, si así lo desea.
2. El integrador completa en `01-arquitectura-boilerplate.md` la tabla de nombres reales del boilerplate.
3. Se congela `13-contratos-compartidos.md` antes del desarrollo paralelo.
4. Cada agente recibe un documento de frente y `09-seguridad-permisos.md`.
5. Cada agente trabaja en rama o worktree propio y evita archivos centrales.
6. El integrador ensambla en el orden indicado por `12-coordinacion-multiagente.md`.
7. QA ejecuta `10-pruebas-qa.md` y el checklist final.

## Decisiones que requieren aprobación del propietario

- Versiones mínimas de WordPress, WooCommerce y PHP.
- Si la aceptación del repartidor estará activada por defecto.
- Si la evidencia será obligatoria por defecto.
- Estado WooCommerce al completar y al fallar.
- Retención y eliminación de evidencia al desinstalar.
- País, idioma y estilo predeterminados de Mapbox.

Hasta recibir estas respuestas, usar los valores provisionales documentados sin convertirlos en decisiones irreversibles.
