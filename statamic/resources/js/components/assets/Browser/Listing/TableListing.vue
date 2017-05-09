<template>

    <div class="asset-table-listing">

        <table v-if="hasResults">

            <thead>
                <tr>
                    <th></th>
                    <th>{{ translate('cp.title') }}</th>
                    <th>{{ translate('cp.filesize') }}</th>
                    <th>{{ translate('cp.date_modified') }}</th>
                    <th class="column-actions"></th>
                </tr>
            </thead>

            <tbody>

                <tr is="folderRow"
                    v-for="folder in subfolders"
                    :folder="folder"
                    @open-dropdown="closeDropdowns"
                    @selected="selectFolder"
                    @editing="editFolder"
                    @deleting="deleteFolder"
                    @dropped-on-folder="droppedOnFolder">
                </tr>

                <tr is="assetRow"
                    v-for="asset in assets"
                    :asset="asset"
                    :selected-assets="selectedAssets"
                    @open-dropdown="closeDropdowns"
                    @selected="selectAsset"
                    @deselected="deselectAsset"
                    @editing="editAsset"
                    @deleting="deleteAsset"
                    @assetdragstart="assetDragStart">
                </tr>

            </tbody>
        </table>

    </div>

</template>


<script>
import Listing from './Listing';

export default {

    mixins: [Listing],


    components: {
        AssetRow: require('./AssetRow.vue'),
        FolderRow: require('./FolderRow.vue')
    },


    methods: {
        closeDropdowns: function(context) {
            this.$broadcast('close-dropdown', context);
        },

        droppedOnFolder(folder, e) {
            const asset = e.dataTransfer.getData('asset');
            e.dataTransfer.clearData('asset');

            // discard any drops that weren't started on an asset
            if (asset == '') return;

            this.$emit('assets-dragged-to-folder', folder);
        }

    }

}
</script>
