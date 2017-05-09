export default {

    props: ['asset', 'selectedAssets'],


    computed: {

        /**
         * Determine if an asset should be in the selected state.
         */
        isSelected() {
            return _.contains(this.selectedAssets, this.asset.id);
        },

        /**
         * Whether the asset can be rendered as svg
         */
        canShowSvg() {
            return this.asset.extension === 'svg' && !this.asset.url.includes(':');
        },

        /**
         * The inline style used to display an SVG background image
         */
        svgBackgroundStyle() {
            return 'background-image: url("' + this.asset.url + '")';
        }
    },


    methods: {

        /**
         * Trigger a toggle of the selected state.
         */
        toggle() {
            if (this.isSelected) {
                this.$emit('deselected', this.asset.id);
            } else {
                this.$emit('selected', this.asset.id);
            }
        },

        /**
         * Trigger editing of an asset.
         */
        editAsset() {
            this.$emit('editing', this.asset.id);
        },

        /**
         * Trigger deleting of an asset.
         */
        deleteAsset() {
            this.$emit('deleting', this.asset.id)
        },

        assetDragStart(e) {
            e.dataTransfer.setData('asset', this.asset.id);
            e.dataTransfer.effectAllowed = 'move';
            this.$emit('assetdragstart', this.asset.id);
        }

    }

}
