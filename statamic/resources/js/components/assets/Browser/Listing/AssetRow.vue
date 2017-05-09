<template>

    <tr @click="toggle" @dblclick="editAsset" :class="{ 'selected': isSelected }">

        <td class="thumbnail-col" @dragstart="assetDragStart">
            <div v-if="canShowSvg"
                 class="img svg-img"
                 :style="svgBackgroundStyle">
            </div>
            <div class="img" v-else>
                <img v-if="asset.is_image" :src="asset.thumbnail" />
                <file-icon v-else :extension="asset.extension"></file-icon>
            </div>
        </td>

        <td>
            <span v-if="asset.title !== asset.filename" :title="asset.basename">{{ asset.title }}</span>
            <span v-else>{{ asset.basename }}</span>
        </td>

        <td>{{ asset.size_formatted }}</td>
        <td>{{ asset.last_modified_formatted }}</td>

        <td class="column-actions">

            <div class="btn-group" :class="{ open: showActionsDropdown }">
                <button type="button" class="btn-more dropdown-toggle" @click.prevent.stop="toggleActions">
                    <i class="icon icon-dots-three-vertical"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="" @click.prevent="closeDropdownAndEditAsset">{{ translate('cp.edit') }}</a></li>
                    <li class="divider"></li>
                    <li class="warning"><a href="" @click.prevent="closeDropdownAndDeleteAsset">{{ translate('cp.delete') }}</a></li>
                </ul>
            </div>

        </td>

    </tr>

</template>


<script>
import Asset from './Asset';
import Row from './Row';

export default {

    mixins: [Asset, Row],

    methods: {

        closeDropdownAndEditAsset() {
            this.showActionsDropdown = false;
            this.editAsset();
        },

        closeDropdownAndDeleteAsset() {
            this.showActionsDropdown = false;
            this.deleteAsset();
        }

    }

}
</script>
