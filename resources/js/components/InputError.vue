<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message">
        <p class="text-fino text-estado-cancelada-texto">
            {{ message }}
        </p>
    </div>
</template>
