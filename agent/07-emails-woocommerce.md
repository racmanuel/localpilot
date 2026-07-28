# Emails nativos de WooCommerce

## Alcance

Registrar notificaciones como clases `WC_Email` visibles y configurables en **WooCommerce → Ajustes → Correos electrónicos**.

## Dependencias

- Eventos de dominio posteriores a commit.
- Datos de driver y pedido.
- Contratos de hooks.
- Templates e internacionalización.

## Emails del MVP

| Email | Destinatario | Evento |
|---|---|---|
| Pedido asignado | Repartidor | `lclplt_delivery_assigned` |
| Asignación cancelada/reasignada | Repartidor afectado | evento correspondiente |
| Pedido en reparto | Cliente, configurable | `lclplt_delivery_started` |
| Entrega completada | Cliente/admin según configuración | `lclplt_delivery_completed` |
| Entrega fallida | Admin/gestor configurado | `lclplt_delivery_failed` |

Cada clase define habilitación, asunto, encabezado, tipo y destinatario cuando aplique. Evitar una página propia de configuración de correo.

## Archivos a crear o tocar

- `includes/emails/class-*-email-*.php`
- `templates/emails/*.php`
- `templates/emails/plain/*.php`
- integración/filtro de registro de emails
- strings de traducción

## Tareas

1. Registrar clases mediante el filtro de WooCommerce.
2. Suscribirse a hooks posteriores a persistencia exitosa.
3. Construir recipient sin asumir que todos los usuarios tienen email válido.
4. Preparar datos mínimos para template.
5. Crear versión HTML y plain text.
6. Escapar salida y no imprimir secretos/notas internas.
7. Evitar duplicados ante reintento.
8. Probar vista previa/configuración si WooCommerce la ofrece.
9. Añadir filtros para extensibilidad sin comprometer permisos.

## Criterios de aceptación

- Cada email aparece en la sección nativa de WooCommerce.
- Puede activarse/desactivarse y personalizar asunto/encabezado.
- Un evento lógico produce como máximo un envío por destinatario.
- Reasignar notifica correctamente al nuevo y, si está habilitado, al anterior.
- HTML y texto plano contienen enlaces válidos y datos autorizados.
- La ausencia de email no rompe la transición.
- Los correos se disparan después de persistir cambios.
- Las plantillas pueden sobrescribirse mediante el mecanismo acordado.

## Riesgos

- Emails duplicados: usar ID de evento/asignación como clave lógica.
- Datos obsoletos: cargar pedido/driver al disparar y pasar snapshot mínimo.
- Entrega lenta: permitir que WooCommerce/Action Scheduler gestione diferido si ya está disponible; no incorporarlo sin necesidad.
- PII excesiva: incluir solo información necesaria.

## Definición de terminado

Los emails del MVP son extensiones nativas de WooCommerce, configurables, traducibles, tienen plantillas HTML/plain, se disparan una sola vez por evento y nunca bloquean el flujo de entrega ante un fallo de envío.
