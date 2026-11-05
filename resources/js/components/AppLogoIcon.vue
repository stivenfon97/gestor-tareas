<script setup lang="ts">
/**
 * Marca de la aplicación: un portapapeles con una marca de verificación, que
 * es lo que hace la herramienta —tareas que se cierran y se entregan—.
 *
 * Es un icono de Lucide y no un SVG propio para que herede el trazo y el
 * tamaño del resto de la interfaz; el color lo pone quien lo usa con un token.
 */
import { ClipboardCheck } from '@lucide/vue';
</script>

<template>
    <ClipboardCheck aria-hidden="true" />
</template>
