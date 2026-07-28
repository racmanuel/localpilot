<div align="center">
  <img src="screenshots/admin-panel.jpg" alt="LocalPilot" width="120">
  <h1 align="center">LocalPilot</h1>
  <p align="center">
    <strong>Local Delivery Drivers for WooCommerce</strong>
    <br>
    Asigna pedidos a repartidores locales, gestiona entregas desde Mi cuenta y registra evidencia con Mapbox.
  </p>
  <p>
    <img src="https://img.shields.io/badge/WordPress-6.9%2B-blue" alt="WordPress">
    <img src="https://img.shields.io/badge/WooCommerce-10.9%2B-96588A" alt="WooCommerce">
    <img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4" alt="PHP">
    <img src="https://img.shields.io/badge/licencia-GPLv2-green" alt="License">
  </p>
</div>

---

## 📋 Descripción

**LocalPilot** extiende WooCommerce para operar entregas locales con repartidores. Está construido sobre las pantallas nativas de WooCommerce — sin tableros duplicados, sin reinventar la rueda.

Los repartidores operan desde **Mi cuenta** en el frontend, sin necesidad de acceder al panel de administración. Los gestores de tienda mantienen el control total desde el editor nativo del pedido.

> 🇪🇸 Hecho en México — textos en español incluidos.

---

## ✨ Funcionalidades

### 🚚 Gestión de repartidores

- Rol nativo `localpilot_driver` con capacidades propias.
- Perfil con teléfono, vehículo, patente y notas.
- Activación/desactivación individual.

### 📦 Asignación desde WooCommerce

- Asignar, reasignar o retirar repartidores desde el editor del pedido.
- Columnas informativas en la lista de pedidos (HPOS y almacenamiento heredado).
- Filtros por repartidor y estado de entrega.

### 📱 Mi cuenta — Mis entregas

- Listado con resumen, contadores por estado y filtros.
- Vista responsive: tabla en escritorio, tarjetas en móvil.
- Acciones contextuales: aceptar, iniciar reparto, completar, reportar fallo.
- Subida de evidencia fotográfica (JPG, PNG, WebP).
- Validación puntual de ubicación al completar (opcional).

### 🗺️ Mapbox

- Geocodificación automática de direcciones al asignar.
- Mapa interactivo en el detalle de la entrega.
- Mapa administrativo con corrección manual de coordenadas.
- Círculo de radio de validación y marcador dual (destino + GPS).
- **Rutas bajo demanda** desde la ubicación del repartidor hasta el destino.
- Perfiles: auto con tráfico, auto, bicicleta y caminando.
- Privacidad: la ruta solo existe en memoria, no se guarda.

### 📧 Correos electrónicos

5 notificaciones nativas de WooCommerce configurables:
- Pedido asignado.
- Repartidor retirado.
- Reparto iniciado.
- Entrega completada.
- Entrega fallida.

### 🔒 Privacidad

- La ubicación usada para rutas no se almacena ni persiste.
- La validación puntual solo guarda el resumen, no recorridos.
- Sin tracking en vivo, GPS en segundo plano ni `watchPosition()`.
- Datos conservados al desinstalar por defecto (configurable).

---

## 📸 Capturas de pantalla

### Panel de administración

| Editor de pedido | Ajustes generales | Ajustes Mapbox |
|---|---|---|
| ![Panel admin](screenshots/admin-panel.jpg) | ![Ajustes generales](screenshots/settings-general.jpg) | ![Ajustes Mapbox](screenshots/settings-mapbox.jpg) |

### Frontend — Mis entregas

| Listado | Detalle | Ruta calculada |
|---|---|---|
| ![Listado](screenshots/deliveries-list.jpg) | ![Detalle](screenshots/delivery-detail.jpg) | ![Ruta](screenshots/delivery-detail-route.jpg) |

---

## 🚀 Instalación

### Requisitos

- WordPress 6.9+
- WooCommerce 10.9+
- PHP 7.4+
- HTTPS (necesario para geolocalización del navegador)

### Instalación rápida

1. Descarga el plugin desde [GitHub Releases](https://github.com/racmanuel/localpilot/releases).
2. Ve a **Plugins → Añadir nuevo → Subir plugin** y selecciona el archivo ZIP.
3. Activa el plugin.
4. Ve a **WooCommerce → Ajustes → LocalPilot** para configurar.

### Configuración inicial

1. Crea uno o más usuarios con el rol `localpilot_driver`.
2. Actívalos desde su perfil de usuario (checkbox "Activo").
3. Configura Mapbox en **WooCommerce → Ajustes → LocalPilot → Mapbox**.
4. Ve a WooCommerce → Pedidos, abre un pedido y asígnalo a un repartidor.

---

## ⚙️ Estados de entrega

```
Sin asignar → Asignado → Aceptado* → En reparto → Entregado
                    ↘                        ↘→ Fallido
                    ↘→ Cancelado
```

*Aceptación obligatoria configurable.

---

## 🗺️ Rutas en el mapa

Las rutas están disponibles solo para entregas activas (`Asignado`, `Aceptado`, `En reparto`) y deben activarse explícitamente en los ajustes.

**Detalles técnicos:**
- La ruta se calcula mediante **Mapbox Directions API v5**.
- Cada clic en **Calcular ruta** genera una solicitud facturable.
- La ubicación, geometría, distancia y duración **no se guardan en WordPress**.
- No se utiliza `watchPosition()`, GPS en segundo plano ni recálculo automático.
- Google Maps permanece como opción de navegación externa.

---

## 🔐 Seguridad

- Control de acceso por repartidor (ownership verificado en servidor).
- Nonces y capacidades en cada acción.
- Validación de transiciones por estado persistido.
- Evidencia validada por MIME real, no por extensión.
- Token Mapbox restringido por URL y scopes mínimos.
- Sin exposición de coordenadas, tokens o rutas en logs o eventos.

---

## 🧪 Pruebas

El plugin incluye un runner de QA básico en `tests/qa-runner.php`. Para ejecutarlo:

```bash
wp eval-file tests/qa-runner.php
```

---

## 📄 Licencia

GPLv2 o posterior. Ver [LICENSE.txt](LICENSE.txt).

---

## 👨‍💻 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Haz fork del repositorio.
2. Crea una rama (`git checkout -b feat/mi-mejora`).
3. Haz commit de tus cambios (`git commit -m 'feat: añade mi mejora'`).
4. Haz push a la rama (`git push origin feat/mi-mejora`).
5. Abre un Pull Request.

---

## 🧑‍💻 Autor

**racmanuel** — [racmanuel.dev](https://racmanuel.dev/)

---

## 🙏 Reconocimientos

- [Mapbox](https://www.mapbox.com/) — mapas, geocodificación y direcciones.
- [WooCommerce](https://woocommerce.com/) — la plataforma de comercio electrónico.
- [WordPress](https://wordpress.org/) — el CMS que lo hace posible.
