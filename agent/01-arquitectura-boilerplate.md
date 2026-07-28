# Arquitectura y adaptación al boilerplate existente

## Propósito

Extender el WordPress Plugin Boilerplate de DevinVinson ya existente sin regenerarlo ni convertirlo en otra arquitectura. El boilerplate permanece como shell de carga, hooks, internacionalización, activación, administración y frontend.

## Inventario obligatorio antes de editar

El propietario ya confirmó estas convenciones. El integrador solo debe verificar las rutas físicas contra el repositorio:

| Elemento | Ruta real |
|---|---|
| Archivo principal | `localpilot.php` (verificar) |
| Clase principal | `includes/class-localpilot.php` |
| Loader | `includes/class-localpilot-loader.php` |
| Activador | `includes/class-localpilot-activator.php` |
| Desactivador | `includes/class-localpilot-deactivator.php` |
| Clase admin | `admin/class-localpilot-admin.php` |
| Clase public | `public/class-localpilot-public.php` |
| Clase principal PHP | `Localpilot` |
| Prefijo de clases | `Localpilot_` |
| Text domain | `localpilot` |
| Prefijo funcional | `lclplt_` |
| Prefijo de constantes | `LOCALPILOT_` |

Ningún agente debe migrar estas convenciones a `localpilot-delivery`, `lpd_` o un namespace nuevo.

## Metadatos del plugin confirmados

```text
Nombre público: LocalPilot – Local Delivery Drivers for WooCommerce
Contributors: racmanuel
Donate link: https://racmanuel.dev/
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 7.0.2
Stable tag: 1.0.0
License: GPLv2 or later / GPL-2.0+
Text Domain: localpilot
Domain Path: /languages
```

Tags proporcionados:

```text
localpilot, local, delivery, drivers, for, woocommerce
```

Antes de publicar en WordPress.org, el responsable de release debe revisar el límite y utilidad de tags; esto no altera el código del MVP.

## Correcciones puntuales del fragmento entregado

Al verificar el archivo real:

- usar `__FILE__`, con dos guiones bajos a cada lado; `**FILE**` es solo una deformación de Markdown y no es PHP válido;
- corregir `https://racmanuel.dev//` a `https://racmanuel.dev/`;
- sustituir `https://plugin.com/localpilot-uri/` por la URL real o retirarla antes del release;
- corregir el typo del comentario `callign` si se toca esa sección;
- mantener `LOCALPILOT_VERSION`, `LOCALPILOT_CSS_FRAMEWORK`, `LOCALPILOT_CSS_ENQUEUE_LOCATION` y `LOCALPILOT_BASE_NAME`;
- mantener `lclplt_activate()`, `lclplt_deactivate()` y `lclplt_run()`;
- conservar `Localpilot_Activator`, `Localpilot_Deactivator` y `Localpilot`.

Estas son correcciones localizadas; no justifican regenerar el boilerplate.

## Adaptación compatible

Se conserva:

- entrypoint y ciclo `run()` del boilerplate;
- loader como registro central de actions/filters;
- clases `admin` y `public` como adaptadores de presentación;
- activador/desactivador;
- carpetas de assets y traducciones existentes.

Se añaden clases de dominio bajo `includes/` sin alterar la filosofía base:

```text
includes/
├── database/
├── deliveries/
├── maps/
├── rest/
└── integrations/
```

Estas carpetas son una extensión compatible, no un reemplazo. Si el repositorio ya usa otra agrupación, se adopta la existente.

## Capas y dependencias

| Capa | Responsabilidad | Puede depender de |
|---|---|---|
| Bootstrap | Construcción y registro de hooks | Todas, solo para cableado |
| Presentación admin/public | Render, requests, notices | Servicios de aplicación |
| REST | Validación de request y respuesta | Servicios de aplicación |
| Aplicación | Asignar, transicionar, geocodificar | Repositorios/adaptadores |
| Persistencia | Tablas propias y `WC_Order` CRUD | WordPress/WooCommerce |
| Integraciones | Mapbox y emails | Interfaces/servicios definidos |

La presentación no escribe metadatos directamente. Debe invocar servicios compartidos para evitar que Admin, REST y Mi cuenta implementen reglas diferentes.

## Servicios mínimos

- `Assignment_Service`: asignar, reasignar y retirar.
- `Delivery_Status`: constantes, etiquetas y máquina de estados.
- `Delivery_Transition_Service`: validar y ejecutar cambios de estado.
- `Delivery_Query`: consultar asignaciones visibles.
- `Event_Repository`: persistir eventos estructurados.
- `Order_Delivery_Meta`: encapsular metadatos del estado actual.
- `Geocoding_Service`: coordinar Mapbox y persistencia.
- `Proof_Service`: validar y asociar evidencias.

## Archivos a tocar

**Solo integrador:**

- clase principal del boilerplate;
- loader;
- activador;
- desactivador;
- entrypoint si se requiere declaración de compatibilidad HPOS.

**Agentes de frente:**

- clases nuevas de su dominio;
- métodos acotados en `admin/` o `public/`;
- templates y assets de su frente.

## Tareas

1. Inventariar nombres y versiones actuales.
2. Confirmar que WooCommerce se valida antes de iniciar módulos dependientes.
3. Definir factorías o construcción manual coherente con el boilerplate.
4. Registrar cada hook mediante el loader cuando sea viable.
5. Declarar compatibilidad HPOS desde el entrypoint en el hook adecuado.
6. Mantener compatibilidad con el ciclo de activación existente.
7. Ejecutar upgrades de esquema también en arranque administrativo controlado; no depender únicamente del hook de activación.
8. Crear un mapa de clases y sus dependencias.
9. Evitar singletons globales nuevos salvo patrón ya presente.

## Criterios de aceptación

- El plugin conserva el arranque generado por el boilerplate.
- Activar/desactivar no produce fatal errors con WooCommerce presente.
- Sin WooCommerce, el plugin muestra un aviso claro y no inicia módulos operativos.
- Los módulos pueden probarse sin depender de HTML.
- Ningún frente necesita editar el loader simultáneamente.
- No se carga Mapbox ni assets de entrega en páginas ajenas.
- Las cadenas usan el text domain real acordado.

## Riesgos y mitigación

| Riesgo | Mitigación |
|---|---|
| Agentes renombran clases base | Inventario congelado y ownership del integrador |
| Clase principal crece demasiado | Solo construcción y registro, sin negocio |
| Lógica duplicada en REST/admin | Servicios compartidos |
| Autoload inexistente | `require_once` controlados por el integrador |
| Dependencias circulares | Flujo de capas y constructores explícitos |
| Incompatibilidad con versión actual del boilerplate | Adaptar al repositorio, no a una plantilla teórica |

## Definición de terminado

Existe un bootstrap estable que registra todos los módulos mediante la estructura actual, las dependencias están documentadas, el plugin puede activarse con y sin WooCommerce de forma controlada y cada agente dispone de límites de edición claros.
