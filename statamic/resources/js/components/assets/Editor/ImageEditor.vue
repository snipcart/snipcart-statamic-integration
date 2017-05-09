<template>

    <div>
        <img v-el:image :src="url" class="aviary-trigger-image" />
    </div>

</template>


<style lang="scss">
    .aviary-trigger-image {
        display: none !important;
    }
</style>


<script>
Statamic.AdobeSdkApiKey = '16a7538bf91a4ce4bf21e7f7ab8bec1d';
Statamic.Aviary = null;

export default {

    props: ['id', 'container', 'path', 'url'],


    ready() {
        this.initEditor();
    },


    methods: {

        openEditor() {
            Statamic.Aviary.launch({
                image: this.$els.image,
                hiresUrl: this.url
            });
        },

        initEditor() {
            Statamic.Aviary = new Aviary.Feather({
                apiKey: Statamic.AdobeSdkApiKey,
                authenticationURL: cp_url('assets/image-editor-auth'),
                theme: 'light',
                noCloseButton: true,
                onSaveButtonClicked: function () {
                    Statamic.Aviary.saveHiRes();
                    return false;
                },
                onSaveHiRes: (imageId, newUrl) => {
                    this.$els.image.src = newUrl;
                    this.replaceAsset(newUrl);
                    this.closeEditor();
                },
                onError: function(error) {
                    console.error('Image editor error: ' + error.message);
                },
                onLoad: () => {
                    this.openEditor();
                }
            });
        },

        closeEditor() {
            Statamic.Aviary.close();
        },

        replaceAsset(url) {
            this.$http.post(cp_url('assets/replace-edited-image'), {
                new_url: url,
                id: this.id,
                container: this.container,
                path: this.path
            }).success((response) => {
                this.updateThumbnail(response.thumbnail);
            });
        },

        updateThumbnail(url) {
            this.$emit('saved', url);
        }

    }

}
</script>
