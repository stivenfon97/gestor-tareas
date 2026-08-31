<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import EquipoController from '@/actions/App/Http/Controllers/Gestion/EquipoController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/equipos';
</script>

<template>
    <Head :title="$t('gestion.equipos.crear')" />

    <div class="flex max-w-xl flex-col gap-6">
        <Heading variant="small" :title="$t('gestion.equipos.crear')" />

        <Form v-bind="EquipoController.store.form()" class="space-y-6" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="nombre">{{ $t('comun.nombre') }}</Label>
                <Input id="nombre" name="nombre" required />
                <InputError :message="errors.nombre" />
            </div>

            <div class="grid gap-2">
                <Label for="descripcion">{{ $t('comun.descripcion') }}</Label>
                <Input id="descripcion" name="descripcion" />
                <InputError :message="errors.descripcion" />
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="processing">{{ $t('comun.guardar') }}</Button>
                <Button as-child variant="ghost">
                    <Link :href="index()">{{ $t('comun.cancelar') }}</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
