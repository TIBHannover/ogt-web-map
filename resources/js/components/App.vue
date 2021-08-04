<template>
    <main>
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
        <div>
            <router-view></router-view>
        </div>
    </main>
</template>

<script>
export default {}
</script>
