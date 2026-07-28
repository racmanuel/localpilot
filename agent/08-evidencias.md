# Evidencias de entrega

## Alcance

Permitir una imagen por intento o evento relevante, asociarla al pedido/evento y mostrarla solo a usuarios autorizados.

## Dependencias

- Servicio de transiciones.
- Modelo de eventos.
- Política de permisos.
- Settings de tamaño/obligatoriedad.

## Datos admitidos

- JPG/JPEG
- PNG
- WebP
- máximo provisional: 5 MB

Datos de completado:

```text
received_by
delivery_note
proof_attachment_id
completed_at
```

Datos de fallo:

```text
failed_reason
failure_note
proof_attachment_id
failed_at
```

La evidencia obligatoria es configurable. El receptor es obligatorio por defecto para entrega completada.

## Archivos a crear o tocar

- `includes/deliveries/class-*-proof-service.php`
- forms/templates de completar y fallar
- controlador de carga, REST o POST tradicional
- integración del panel de pedido para consulta
- settings correspondientes

## Flujo de carga

1. Validar autenticación, capacidad, ownership, nonce y estado.
2. Comprobar error de upload, tamaño real y MIME detectado por WordPress.
3. Crear attachment con APIs de WordPress.
4. Asociar ID a transición/evento.
5. Persistir transición.
6. Si la transición falla, tratar el attachment huérfano según política.
7. Mostrar confirmación sin exponer ruta física.

## Decisión sobre privacidad

La biblioteca de medios de WordPress suele servir archivos por URL directa; marcar un attachment como privado no protege el archivo físico. Por tanto:

- el MVP no debe prometer protección absoluta de URL;
- no se enlaza evidencia en contenido público;
- se usa un endpoint autorizado de descarga/visualización cuando sea viable;
- se documenta el riesgo si el servidor sirve el archivo directamente;
- una solución de almacenamiento privado queda para fase posterior si el requisito es estricto.

## Tareas

1. Implementar `Proof_Service`.
2. Validar MIME real, extensión y tamaño.
3. Renombrar archivo mediante APIs seguras, sin datos sensibles.
4. Añadir metadatos privados de relación.
5. Mostrar thumbnail autorizado en admin.
6. Implementar eliminación acorde a retención/uninstall.
7. Evitar ejecución de archivos y SVG.
8. Limitar cantidad por request.
9. Limpiar huérfanos identificables de forma segura.

## Criterios de aceptación

- Un driver no puede adjuntar a un pedido ajeno.
- Un archivo no permitido se rechaza antes de cambiar estado.
- Una carga válida queda asociada al pedido, asignación y evento.
- Completar falla de forma segura si evidencia es obligatoria y falta.
- El admin autorizado puede revisar la imagen.
- Clientes normales no reciben enlaces a evidencia.
- Repetir el request no crea múltiples completados.
- No se aceptan SVG, PHP ni MIME disfrazado.

## Riesgos

- Media URLs públicas: documentación honesta y endpoint autorizado.
- Archivos huérfanos: compensación/limpieza programada y segura.
- Consumo de disco: límite, retención y una imagen por evento.
- EXIF con ubicación: decidir si se elimina; recomendado procesar con editor de imágenes si no degrada compatibilidad.

## Definición de terminado

La carga está integrada con las transiciones, valida contenido y permisos, mantiene relaciones estructuradas, ofrece consulta administrativa segura dentro de los límites de WordPress y documenta claramente la privacidad del almacenamiento.
