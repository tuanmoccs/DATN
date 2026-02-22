<template>
  <Header />
  <slot />
  <Footer />
</template>
<script>
import Header from '../components/partial/Header.vue';
import Footer from '../components/partial/Footer.vue';
export default {
  name: "LayoutDefault",
  components: { Header, Footer }
}
</script>