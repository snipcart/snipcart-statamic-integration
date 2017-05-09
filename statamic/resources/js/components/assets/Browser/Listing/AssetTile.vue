<template>

    <div class="asset-tile"
         :class="{ 'is-image': isImage && !canShowSvg, 'is-svg': canShowSvg, 'is-file': !isImage && !canShowSvg }"
         :title="asset.filename"
         @click="toggle"
         @dblclick="editAsset"
         @dragstart="assetDragStart"
    >
        <i class="icon icon-check selected-icon" v-if="isSelected"></i>

        <div class="asset-thumb-container">
            <div v-if="canShowSvg"
                 class="svg-img"
                 :style="svgBackgroundStyle">
            </div>
            <template v-else>
                <div class="asset-thumb" v-if="isImage">
                    <img :src="asset.thumbnail">
                </div>
                <file-icon v-else type="div" :extension="asset.extension"></file-icon>
            </template>
        </div>

        <div class="asset-meta">
            <div class="asset-filename" :title="label">{{ label }}</div>
            <div class="asset-filesize">{{ asset.size }}</div>
        </div>

    </div>

</template>


<script>
import Asset from './Asset';

export default {

    mixins: [Asset],


    computed: {

        isImage() {
            return this.asset.is_image;
        },

        icon() {
            return resource_url('img/filetypes/'+ this.asset.extension +'.png');
        },

        label() {
            return this.asset.title || this.asset.basename;
        }

    }

}
</script>
