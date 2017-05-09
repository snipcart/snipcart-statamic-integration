<template>
    <div class="taxonomy-fieldtype">
        <relate-fieldtype :data.sync="data" :name="name" :config="adjustedConfig"></relate-fieldtype>
    </div>
</template>

<script>
module.exports = {

    props: ['data', 'config', 'name'],


    computed: {

        adjustedConfig() {
            let c = this.config;

            // By default, create mode should be true.
            if (c.create === undefined) {
                c.create = true;
            }

            // If multiple taxonomies have been specified, the field can't know
            // in which taxonomy to create, so the feature will be disabled.
            if (typeof this.config.taxonomy !== 'string') {
                c.create = false;
            }

            return c;
        }

    }
};
</script>
