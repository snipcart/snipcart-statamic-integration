<template>
    <input type="number" :class="classes" v-model="data" tabindex="0" :autofocus="autofocus" />
</template>

<script>

module.exports = {

    props: ['name', 'data', 'config', 'autofocus'],

    computed: {
        classes: function() {
            return 'form-control';
        }
    },

};
</script>
