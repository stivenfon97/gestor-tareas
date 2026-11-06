<script setup lang="ts">
/**
 * Un valor del historial (antes o después), pintado según su tipo.
 *
 * Fechas y montos pasan por `lib/formato.ts`; los valores de un enum llegan
 * como clave de traducción. Un valor oculto (la contraseña) dice que no se
 * registró, nunca enseña nada en su lugar.
 */
import { computed } from 'vue';
import {
    formatearFecha,
    formatearFechaHora,
    formatearMonto,
} from '@/lib/formato';
import { trans } from '@/lib/i18n';
import type { ValorDeHistorial } from './tipos';

const props = defineProps<{ valor: ValorDeHistorial }>();

const texto = computed((): string => {
    const { tipo, valor } = props.valor;

    switch (tipo) {
        case 'clave':
            return trans(String(valor));
        case 'fecha':
            return formatearFecha(String(valor));
        case 'fecha_hora':
            return formatearFechaHora(String(valor));
        case 'monto_usd':
            return formatearMonto(String(valor), 'USD');
        case 'monto_cop':
            return formatearMonto(String(valor), 'COP');
        case 'booleano':
            return valor ? trans('historial.si') : trans('historial.no');
        case 'vacio':
            return trans('historial.vacio');
        case 'oculto':
            return trans('historial.oculto');
        default:
            return String(valor ?? '');
    }
});

const esApagado = computed(
    () => props.valor.tipo === 'vacio' || props.valor.tipo === 'oculto',
);
</script>

<template>
    <span
        :class="
            esApagado
                ? 'text-texto-tenue italic'
                : 'break-words whitespace-pre-wrap text-texto'
        "
        >{{ texto }}</span
    >
</template>
