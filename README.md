# LexCita — Plataforma de Gestión de Citas Legales

LexCita es una aplicación web integral desarrollada para la automatización, agendación y control de citas jurídicas. El sistema optimiza el flujo de atención en bufetes legales mediante la implementación de pagos anticipados obligatorios y un sistema automatizado de notificaciones instantáneas.

---

## 🚀 Arquitectura y Tecnologías Core

* **Backend Framework:** Laravel 11 (PHP >= 8.2)
* **Base de Datos:** MySQL 8 (Estructura relacional optimizada)
* **Pasarela de Pagos:** Pay Pal API
* **Mensajería:** Twilio API (Protocolo WhatsApp Business para confirmaciones)
* **Frontend:** Tailwind CSS & JavaScript Asíncrono (Fetch API / FullCalendar / AJAX)

---

## 🛠️ Requisitos del Sistema e Instalación (Desarrollo)

### Requisitos Previos
* Servidor Local (Recomendado: **Laragon v6.0+**)
* PHP 8.2 o superior con extensiones activas (`mbstring`, `xml`, `curl`, `zip`, `pdo_mysql`)
* Composer v2+
* Gestor de bases de datos (HeidiSQL o phpMyAdmin)

### Instrucciones de Despliegue Local

1. **Clonar el repositorio dentro del directorio raíz del servidor:**
   ```bash
   cd C:\laragon\www
   git clone [https://github.com/g95410464-svg/Lexcita.git](https://github.com/g95410464-svg/Lexcita.git) lexcita-app
   cd lexcita-app