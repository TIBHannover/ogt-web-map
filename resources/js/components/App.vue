<template>
    <v-app>
        <v-main>
            <nav>
                <div>
                    <router-link to="/" href="#">OGT</router-link>
                    <div>
                        <div>
                            <router-link to="/">Welcome</router-link>
                            <router-link to="/map">Map</router-link>
                        </div>
                    </div>
                </div>
            </nav>
            <v-btn
                class="mt-5 ml-5"
                color="primary"
                fab
                absolute
            >
                <v-icon>
                    mdi-menu
                </v-icon>
            </v-btn>
            <router-view></router-view>
        </v-main>
    </v-app>
</template>

<script>
export default {}
</script>
