<!--
    resources/js/Components/FlashBanner.vue
    DITINGGALKAN (deprecated): notifikasi flash kini ditampilkan global sebagai
    SweetAlert toast melalui bridge di resources/js/app.ts (useSwal → showFlash).
    Komponen ini sengaja tidak merender apa pun agar pemakaian lama tetap aman
    tanpa menampilkan banner ganda.
-->
<template>
    <span style="display: none;" aria-hidden="true" />
</template>
