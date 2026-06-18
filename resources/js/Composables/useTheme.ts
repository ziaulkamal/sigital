import { ref, watch, onMounted } from 'vue';

const STORAGE_KEY = 'crm-theme';

const isDark = ref(false);

function applyTheme(dark: boolean): void {
    document.documentElement.classList.toggle('dark', dark);
}

function getSystemPreference(): boolean {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function loadSavedTheme(): boolean {
    const saved = localStorage.getItem(STORAGE_KEY);
    return saved !== null ? saved === 'dark' : getSystemPreference();
}

export function useTheme() {
    onMounted(() => {
        const alreadyApplied = document.documentElement.classList.contains('dark');
        const shouldBeDark   = loadSavedTheme();
        isDark.value = alreadyApplied || shouldBeDark;
        applyTheme(isDark.value);
    });

    watch(isDark, (val) => {
        applyTheme(val);
        localStorage.setItem(STORAGE_KEY, val ? 'dark' : 'light');
    });

    function toggleTheme(): void { isDark.value = !isDark.value; }
    function setTheme(dark: boolean): void { isDark.value = Boolean(dark); }

    return { isDark, toggleTheme, setTheme };
}
