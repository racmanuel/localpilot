# Checklist de integración y release

## Ensamble

- [ ] Se completó el inventario del boilerplate real.
- [ ] No se recreó ni sustituyó el boilerplate.
- [ ] El integrador es único autor de cambios centrales.
- [ ] Todos los hooks recibidos están registrados una sola vez.
- [ ] No existen includes duplicados ni clases con nombres conflictivos.
- [ ] Las dependencias de WooCommerce se validan antes de construir módulos.
- [ ] Los assets se cargan solo en las pantallas necesarias.

## Datos y HPOS

- [ ] Compatibilidad HPOS declarada.
- [ ] No hay consultas directas a posts/postmeta de pedidos.
- [ ] Metadatos pasan por `WC_Order`.
- [ ] Tablas se crean/actualizan idempotentemente.
- [ ] Índices y consultas preparadas revisados.
- [ ] Timestamps y zona horaria consistentes.
- [ ] Uninstall conserva datos por defecto.

## Funcionalidad

- [ ] Driver activo puede seleccionarse.
- [ ] Asignar, reasignar y retirar son coherentes.
- [ ] Driver anterior pierde acceso tras reasignar.
- [ ] Mi cuenta lista solo asignaciones propias.
- [ ] Todas las transiciones válidas funcionan.
- [ ] Transiciones inválidas se rechazan.
- [ ] Evidencia/receptor/motivo respetan settings.
- [ ] Notas y eventos se generan.
- [ ] Emails se envían una sola vez.
- [ ] Mapbox falla de forma no bloqueante.

## Experiencia nativa

- [ ] No existe dashboard/listado administrativo duplicado.
- [ ] Pedidos usa columnas/panel nativos.
- [ ] Repartidores se gestionan en Usuarios.
- [ ] Ajustes están en WooCommerce.
- [ ] Emails están en WooCommerce → Correos.
- [ ] Mi cuenta usa notices, botones y paginación nativos.
- [ ] No se agregó framework CSS.

## Seguridad y privacidad

- [ ] Capabilities en servidor.
- [ ] Ownership en cada lectura/mutación de driver.
- [ ] Nonces en mutaciones.
- [ ] REST `permission_callback`.
- [ ] Inputs sanitizados y outputs escapados.
- [ ] SQL preparado.
- [ ] Upload MIME/tamaño probado.
- [ ] Token Mapbox ausente de logs.
- [ ] Política de IP/evidencia documentada.
- [ ] Pruebas IDOR y CSRF aprobadas.

## QA

- [ ] Matriz crítica completa.
- [ ] HPOS end-to-end aprobado.
- [ ] `WP_DEBUG` sin errores propios.
- [ ] UI usable en 320 px y teclado.
- [ ] Smoke en tema clásico y de bloques.
- [ ] Activación, upgrade, desactivación y uninstall probados.
- [ ] Traducciones/text domain revisados.
- [ ] Readme y changelog actualizados.

## Empaquetado

- [ ] Versión consistente en header, constante, readme y changelog.
- [ ] Slug/directorio y text domain permanecen como `localpilot`.
- [ ] Prefijos `lclplt_`, `Localpilot_` y `LOCALPILOT_` se usan según su categoría.
- [ ] `__FILE__` aparece correctamente en basename, includes y activation hooks.
- [ ] Plugin URI ya no apunta al placeholder `plugin.com`.
- [ ] Author URI no contiene una doble `/` final.
- [ ] Requires WordPress 6.0, Requires PHP 7.4, Tested up to 7.0.2 y Stable tag 1.0.0 son consistentes.
- [ ] Sin archivos de desarrollo, secretos o fixtures.
- [ ] Licencias de SDK/assets revisadas.
- [ ] Atribución Mapbox presente.
- [ ] ZIP instala con un único directorio raíz.
- [ ] Instalación limpia del ZIP aprobada.
- [ ] Upgrade desde versión de prueba aprobado.

## Go / No-Go

El release es **No-Go** si ocurre cualquiera:

- fuga de pedido/evidencia entre drivers;
- escritura de pedidos incompatible con HPOS;
- doble asignación o doble completado reproducible;
- upload ejecutable;
- token secreto expuesto;
- pérdida de datos en desinstalación sin opt-in;
- fatal error al activar/desactivar;
- caso crítico de QA fallido.

## Definición de terminado

Todos los checks aplicables están marcados con evidencia; no existe condición No-Go; el ZIP se instala y completa el escenario principal en un entorno limpio con HPOS activo.
