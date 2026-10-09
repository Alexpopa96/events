import { ref } from 'vue';

/* Open state of the mobile menu sheet, shared so the tab bar can open the
   sheet that lives in SiteHeader. */
const open = ref(false);

export function useMobileMenu() {
    return { open };
}
