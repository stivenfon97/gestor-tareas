<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

/*
 * `title` y `description` llegan como claves de traducción desde el
 * `defineOptions` de cada página: ahí no se puede llamar a `trans()`, porque
 * se evalúa al importar el módulo, antes de que i18n esté instalado. `$t`
 * devuelve intacto lo que no encuentra, así que un texto ya resuelto también
 * pasa sin tocarse.
 */
defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-9 w-9 items-center justify-center rounded-md"
                        >
                            <AppLogoIcon
                                class="size-9 fill-current text-[var(--foreground)] dark:text-white"
                            />
                        </div>
                        <span class="sr-only">{{ $t(title ?? '') }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">
                            {{ $t(title ?? '') }}
                        </h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ $t(description ?? '') }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
