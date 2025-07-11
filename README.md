# 🛠️ Parche de Compatibilidad para Redsys WooCommerce

[![Compatible con PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://www.php.net/)
[![Compatible con WordPress 6.7+](https://img.shields.io/badge/WordPress-6.7%2B-green.svg)](https://wordpress.org/)
[![Compatible con WooCommerce 8.5+](https://img.shields.io/badge/WooCommerce-8.5%2B-purple.svg)](https://woocommerce.com/)

> ⚠️ **IMPORTANTE**: Este es un parche de compatibilidad no oficial para el plugin oficial de Redsys para WooCommerce. Su único propósito es proporcionar una solución temporal a problemas de compatibilidad con versiones recientes de PHP y WordPress.
>
> 📌 Para la documentación oficial y soporte, visita el [Portal de Desarrolladores de Redsys](https://pagosonline.redsys.es/desarrolladores-inicio/documentacion-tipos-de-integracion/modulos-pago/)

## 🎯 Propósito

Este repositorio contiene correcciones de compatibilidad para el plugin oficial de Redsys para WooCommerce, específicamente dirigidas a resolver problemas que surgen al usar PHP 8.2+ y WordPress 6.7+.

### 🔍 Problemas que Soluciona

1. **Advertencias de PHP 8.2+**
   - Elimina los avisos de "Creation of dynamic property"
   - Mejora la compatibilidad con versiones modernas de PHP
   - Código optimizado según estándares actuales

2. **Problemas de Traducciones**
   - Corrige la carga temprana del dominio de traducción
   - Mejora la integración con WooCommerce
   - Optimiza el manejo de textos internacionalizados

3. **Errores de Headers**
   - Resuelve problemas de "headers already sent"
   - Mejora el manejo de sesiones
   - Optimiza la gestión de redirecciones

4. **Gestión de Errores**
   - Sistema mejorado de logging
   - Control de visualización de errores en producción
   - Mejor depuración y diagnóstico

### Solución de Problemas Comunes

#### Error: Translation loading triggered too early

Si ves este error:
```
Notice: Function _load_textdomain_just_in_time was called incorrectly. 
Translation loading for the woocommerce domain was triggered too early.
```

Este error ha sido corregido en la versión 1.7.2 moviendo la carga de traducciones al hook `after_setup_theme` con prioridad 20. No se requiere ninguna acción adicional por parte del usuario.

## 📋 Requisitos

- PHP 8.2 o superior
- WordPress 6.7+
- WooCommerce 8.5+
- Plugin oficial de Redsys para WooCommerce instalado

## 🚀 Instalación

1. Realiza una copia de seguridad de tu instalación actual
2. Descarga los archivos de este repositorio
3. Reemplaza los archivos del plugin original con los de este parche
4. Limpia la caché de WordPress
5. Verifica el funcionamiento en un entorno de pruebas antes de aplicar en producción

## 📝 Cambios Técnicos

### Versión 1.7.2

#### Mejoras de Compatibilidad
- Declaración explícita de propiedades en clases principales:
  - WC_Redsys
  - WC_Redsys_Bizum
  - WC_Redsys_Insite

#### Optimizaciones de Rendimiento
- Mejora en la carga de traducciones:
  ```php
  function redsys_load_textdomain() {
      load_plugin_textdomain("redsys", false, dirname(plugin_basename(__FILE__)) . '/languages');
  }
  add_action('after_setup_theme', 'redsys_load_textdomain', 20);
  ```
  - Resuelve el error "Translation loading triggered too early"
  - Mejora la compatibilidad con WordPress 6.7+
  - Optimiza el orden de carga de recursos

- Mejora en el manejo de errores:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 0);
  ini_set('log_errors', 1);
  ```

## 📅 Changelog Detallado

### Versión 1.7.2 (16 Junio 2025)

#### 🔧 Correcciones
1. **Solución Error de Traducciones**
   - Resuelto: "Translation loading triggered too early"
   - Implementado nuevo sistema de carga de traducciones
   - Mejorada compatibilidad con WordPress 6.7+

2. **Compatibilidad PHP 8.3+**
   - Eliminadas advertencias de propiedades dinámicas
   - Actualizada estructura de clases
   - Optimizado código base

3. **Gestión de Errores**
   - Mejorado sistema de logging
   - Implementado control de visualización de errores
   - Añadido soporte para depuración avanzada

#### 🆕 Mejoras
- Optimización general del rendimiento
- Mejor integración con WooCommerce
- Documentación técnica actualizada

## 📚 Recursos Oficiales

- [Documentación Oficial para Desarrolladores](https://pagosonline.redsys.es/desarrolladores-inicio/documentacion-tipos-de-integracion/modulos-pago/) - Portal oficial de Redsys con documentación técnica y guías de integración
- Para soporte oficial y últimas versiones, visita el portal de desarrolladores de Redsys

## ⚖️ Aviso Legal

Este parche se proporciona "tal cual", sin garantías de ningún tipo. No está afiliado ni respaldado por Redsys. Los usuarios deben:

1. Utilizar este parche bajo su propia responsabilidad
2. Mantener todos los avisos de derechos de autor originales
3. Cumplir con los términos de licencia originales de Redsys
4. Usar este parche solo como solución temporal hasta que haya una actualización oficial

## 📮 Soporte

- Este es un parche comunitario sin soporte oficial
- Para soporte oficial, contacta directamente con Redsys
- Para problemas técnicos con el parche, puedes abrir un issue en este repositorio

## 🔄 Actualización Recomendada

Se recomienda mantener una vigilancia activa de las actualizaciones oficiales del plugin de Redsys y migrar a ellas tan pronto como estén disponibles.

## 🏷️ Etiquetas

Redsys, WooCommerce, WordPress, PHP 8.3, Compatibility Patch, Payment Gateway, E-commerce, Spanish Payment Gateway, TPV Virtual
