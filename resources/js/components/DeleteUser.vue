<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="$t('ajustes.eliminar.titulo')"
            :description="$t('ajustes.eliminar.descripcion')"
        />
        <!-- Tokens de estado, no utilidades crudas de paleta: `bg-red-50` no
             tiene par oscuro y dejaba texto rojo claro sobre fondo rojo claro. -->
        <div
            class="space-y-4 rounded-lg border border-estado-cancelada-borde bg-estado-cancelada-fondo p-4"
        >
            <div class="relative space-y-0.5 text-estado-cancelada-texto">
                <p class="font-medium">{{ $t('ajustes.eliminar.aviso') }}</p>
                <p class="text-sm">
                    {{ $t('ajustes.eliminar.aviso_cuerpo') }}
                </p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        data-test="delete-user-button"
                        >{{ $t('ajustes.eliminar.boton') }}</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>{{
                                $t('ajustes.eliminar.confirmar_titulo')
                            }}</DialogTitle>
                            <DialogDescription>
                                {{ $t('ajustes.eliminar.confirmar_cuerpo') }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">{{
                                $t('ajustes.eliminar.contrasena')
                            }}</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                :placeholder="$t('ajustes.eliminar.contrasena')"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="outline"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    {{ $t('comun.cancelar') }}
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                {{ $t('ajustes.eliminar.boton') }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
