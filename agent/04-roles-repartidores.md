# Roles, capacidades y repartidores

## Alcance

Usar usuarios de WordPress como repartidores y las pantallas nativas de Usuarios para gestionarlos. No crear un CRUD paralelo.

## Dependencias

- Arquitectura e identidad técnica.
- Política de permisos.
- Repositorio de asignaciones para contar activas.

## Rol y capacidades

Rol:

```text
localpilot_driver
```

Capacidades:

```text
lclplt_view_assigned_deliveries
lclplt_accept_delivery
lclplt_start_delivery
lclplt_complete_delivery
lclplt_fail_delivery
lclplt_upload_delivery_proof
lclplt_manage_deliveries
```

El rol driver recibe solo las seis primeras según configuración funcional. Administrador y, si el propietario lo aprueba, `shop_manager`, reciben `lclplt_manage_deliveries`.

## Metadatos de usuario

```text
_lclplt_driver_phone
_lclplt_driver_active
_lclplt_driver_vehicle_type
_lclplt_driver_vehicle_plate
_lclplt_driver_capacity
_lclplt_driver_notes
```

No guardar documentos, ubicación en vivo, horarios o zonas en el MVP.

## Archivos a crear o tocar

- `includes/deliveries/class-*-driver-role.php`
- `includes/deliveries/class-*-driver-repository.php`
- `includes/integrations/class-*-user-profile.php`
- `admin/class-*-admin.php` para hooks delegados
- activador/desinstalador solo mediante integrador

## Tareas

1. Registrar rol/capacidades de forma idempotente.
2. Añadir campos al perfil solo para usuarios pertinentes.
3. Validar `edit_user`, nonce y tipos de campo al guardar.
4. Añadir columnas Estado, Vehículo y Entregas activas si no afectan usabilidad.
5. Reutilizar filtro nativo por rol.
6. Implementar repositorio de drivers activos.
7. Impedir nuevas asignaciones a drivers inactivos.
8. Definir comportamiento al desactivar un driver con entregas activas.
9. Revocar capacidades creadas al desinstalar solo si la política lo permite.

## Decisión provisional

Desactivar un repartidor:

- impide nuevas asignaciones;
- no cancela automáticamente asignaciones existentes;
- muestra advertencia administrativa;
- requiere reasignación explícita.

Eliminar un usuario con entregas históricas no elimina eventos; `driver_id` se conserva como identificador histórico y la UI muestra “Usuario eliminado”.

## Criterios de aceptación

- El repartidor puede iniciar sesión sin acceso operativo a `wp-admin`.
- Un cliente normal no ve Mis entregas.
- Un driver inactivo no aparece en el selector.
- Un driver solo ve sus asignaciones.
- Los campos de perfil se sanitizan y no se guardan sin permiso.
- La activación repetida no duplica capacidades.
- La desactivación del plugin no destruye el rol ni datos.
- Administrador puede editar el perfil desde Usuarios.

## Riesgos

- Conceder capacidades de WooCommerce demasiado amplias: usar capacidades propias.
- Confundir rol con autorización: comprobar capacidad y ownership.
- Borrado de usuario: mantener historial sin depender de `WP_User`.
- Conteos costosos: usar consulta indexada y evitar N+1.

## Definición de terminado

Los repartidores se gestionan nativamente como usuarios, las capacidades son mínimas e idempotentes, los campos se guardan con seguridad y el repositorio solo devuelve candidatos válidos para asignación.
