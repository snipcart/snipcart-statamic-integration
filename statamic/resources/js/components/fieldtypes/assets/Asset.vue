<template>

    <div class="asset-tile"
         :class="{ 'is-image': isImage && !canShowSvg, 'is-svg': canShowSvg, 'is-file': !isImage && !canShowSvg }"
         :title="asset.filename"
    >

        <asset-editor
            v-if="editing"
            :id="asset.id"
            :allow-deleting="false"
            @closed="closeEditor"
            @saved="assetSaved">
        </asset-editor>

        <div class="asset-thumb-container">
            <div class="asset-thumb">
                <a :href="toenail" class="zoom" v-if="isImage" :title="label">
                    <img :src="thumbnail" />
                </a>
                <template v-else>
                    <div v-if="canShowSvg"
                         class="svg-img"
                         :style="'background-image:url('+asset.url+')'">
                    </div>
                    <file-icon v-else type="div" :extension="asset.extension"></file-icon>
                </template>

                <div class="asset-controls">
                    <button
                        @click="edit"
                        class="btn btn-icon icon icon-pencil"
                        :alt="translate('cp.edit')"></button>

                    <button
                        @click="remove"
                        class="btn btn-icon icon icon-trash"
                        :alt="translate('cp.remove')"></button>
                </div>
            </div>
        </div>

        <div class="asset-meta">
            <div class="asset-filename" :title="label">{{ label }}</div>
            <div class="asset-filesize">{{ asset.size }}</div>
        </div>
    </div>

</template>


<script>
export default {

    components: {
        AssetEditor: require('../../assets/Editor/Editor.vue')
    },


    props: {
        asset: Object
    },


    data() {
        return {
            editing: false
        }
    },


    computed: {

        isImage() {
            return this.asset.is_image;
        },

        canShowSvg() {
            return this.asset.extension === 'svg' && ! this.asset.url.includes(':');
        },

        thumbnail() {
            return this.asset.thumbnail;
        },

        toenail() {
            return this.asset.toenail;
        },

        label() {
            return this.asset.title || this.asset.basename;
        }
    },


    methods: {

        edit() {
            this.editing = true;
        },

        remove() {
            this.$emit('removed', this.asset);
        },

        makeZoomable() {
            if (this.isImage) {
                new Luminous($(this.$el).find('a.zoom')[0], {
                    closeOnScroll: true,
                    captionAttribute: 'title'
                });
            }
        },

        closeEditor() {
            this.editing = false;
        },

        assetSaved(asset) {
            this.asset = asset;
            this.closeEditor();
        }

    },


    ready() {
        this.makeZoomable();
    }

}
</script>
