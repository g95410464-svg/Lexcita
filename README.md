# ⚖️ LexCita — Plataforma de Gestión y Videoconsultas Legales

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)
![WebRTC](https://img.shields.io/badge/WebRTC-RealTime-333333?style=for-the-badge&logo=webrtc&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)

**Sistema web integral para la automatización de agenda jurídica, cobro de honorarios, notificaciones y videoconsultas virtuales en tiempo real.**

[Características principales](#-características-principales) •
[Arquitectura](#-arquitectura-y-tecnologías) •
[Instalación](#-instalación-y-configuración-local) •
[Módulos y Base de Datos](#-módulos-de-videoconsulta) •
[Despliegue](#-despliegue)

</div>

---

## 📋 Descripción General

**LexCita** es una plataforma web desarrollada para optimizar el flujo operativo en bufetes y despachos jurídicos. Resuelve la problemática del ausentismo en consultas mediante la exigencia de pagos anticipados obligatorios y facilita la atención remota con salas virtuales privadas de videoconsulta integradas.

---

## ⚡ Características Principales

* 📅 **Agendamiento Inteligente:** Integración con FullCalendar para selección dinámica de horarios disponibles por abogado.
* 💳 **Pasarela de Pagos Integrada:** Confirmación de citas mediante integración con PayPal API REST.
* 📹 **Videoconsultas Integradas:** Salas de videoconferencia virtuales privadas nativas con WebRTC y comunicación en tiempo real con Laravel Reverb.
* 💬 **Chat y Transferencia de Documentos:** Envío de mensajes y archivos dentro de la sesión de consulta jurídica.
* 📝 **Notas de Consulta Privadas:** Registro de anotaciones confidenciales exclusivas para el abogado responsable de la cita.
* 📲 **Notificaciones:** Confirmación automática vía correo electrónico y mensajería en WhatsApp.

---

## 🚀 Arquitectura y Tecnologías

* **Backend:** Laravel 12 (PHP >= 8.3)
* **Base de Datos:** PostgreSQL / MySQL 8.0+
* **Real-time / WebSockets:** Laravel Reverb + WebRTC Nativo
* **Pasarela de Pagos:** PayPal REST API
* **Mensajería & Correo:** Twilio API (WhatsApp Business) / Resend Mail API
* **Frontend:** Tailwind CSS, Alpine.js, FullCalendar API & JavaScript Asíncrono (Fetch API)

---

## 🛠️ Requisitos del Sistema

* **PHP:** >= 8.3 (Extensiones: `pdo_pgsql`/`pdo_mysql`, `mbstring`, `openssl`, `curl`, `sockets`, `zip`)
* **Composer:** v2.7+
* **Node.js & NPM:** Node.js v20+ / NPM 10+
* **Motor de Base de Datos:** PostgreSQL 14+ o MySQL 8.0+

---

## 📦 Instalación y Configuración Local

### 1. Clonar el Repositorio
```bash
git clone [https://github.com/g95410464-svg/Lexcita.git](https://github.com/g95410464-svg/Lexcita.git) lexcita-app
cd lexcita-app