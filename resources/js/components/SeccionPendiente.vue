<script setup lang="ts">
/**
 * Sección del concentrador de proyecto que todavía no tiene contenido.
 *
 * Dice **qué fase la llena** y no muestra ni un dato. Un placeholder con cifras
 * de mentira se olvida, nadie lo distingue de un dato real y acaba enseñándose
 * a un cliente.
 */
defineProps<{ titulo: string; fase: number }>();
</script>

<template>
    <section
        class="flex flex-col gap-3 rounded-lg border border-dashed border-border p-4"
    >
        <div class="flex items-center justify-between gap-4">
            <h3 class="text-base font-medium text-muted-foreground">
                {{ titulo }}
            </h3>
            <slot />
        </div>
        <p class="text-sm text-muted-foreground">
            {{ $t('proyectos.pendiente_de_fase', { fase: String(fase) }) }}
        </p>
    </section>
</template>
