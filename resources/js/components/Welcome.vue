<template>
    <v-container
        class="grey lighten-3 justify-center"
        fill-height
        fluid
    >
        <v-row>
            <v-col>
                <router-link to="/map">
                    <v-img
                        class="mx-auto"
                        max-width="1150"
                        :src="this.$ogtGlobals.proxyPath + '/images/de/banner.jpg'">
                    </v-img>
                </router-link>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
export default {
    name: 'Welcome',
};
</script>

<style scoped>

</style>
