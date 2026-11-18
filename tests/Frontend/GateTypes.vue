<template>
    <Gate allows="users::read">Users</Gate>
    <Gate :any="abilities">Actions</Gate>
    <button :disabled="$gate.denies(abilities)">Update</button>
    <!-- @vue-expect-error -->
    <Gate>Missing condition</Gate>
    <!-- @vue-expect-error -->
    <Gate allows="users::read" :any="abilities">Conflicting conditions</Gate>
    <!-- @vue-expect-error -->
    <Gate :allows="true">Invalid ability</Gate>
</template>

<script setup lang="ts">
import '../../resources/js';

const abilities = ['users::read', 'role::admin'] as const;
</script>
