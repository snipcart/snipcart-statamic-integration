<template>

    <div class="asset-grid-listing">

        <folder-tile
            v-for="folder in subfolders"
            :folder="folder"
            @selected="selectFolder"
            @editing="editFolder"
            @deleting="deleteFolder">
        </folder-tile>

        <asset-tile
            v-for="asset in assets"
            :asset="asset"
            :selected-assets="selectedAssets"
            @selected="selectAsset"
            @deselected="deselectAsset"
            @editing="editAsset">
        </asset-tile>

        <!-- hack for flexbox spacing the last row properly. -->
        <div class="asset-tile ghost"></div>
        <div class="asset-tile ghost"></div>
        <div class="asset-tile ghost"></div>
        <div class="asset-tile ghost"></div>
        <div class="asset-tile ghost"></div>

    </div>

</template>


<script>
import Listing from './Listing';

export default {

    mixins: [Listing],


    components: {
        AssetTile: require('./AssetTile.vue'),
        FolderTile: require('./FolderTile.vue')
    },

}
</script>
