import { computed } from 'vue';
import axios from '../axios';

export function useDataRobustness() {
    const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000';

    /**
     * Determines if we are in the Electron/Engine environment.
     */
    const isEngine = computed(() => {
        // 1. Check for Electron-specific renderer globals
        const isElectron = window.process?.versions?.electron ||
            window.navigator.userAgent.toLowerCase().includes('electron') ||
            window.location.protocol === 'file:';

        // 2. Fallback check for port mismatch (if not on 3000, we're likely in the built app)
        const isNotDev = !window.location.host.includes('3000') && window.location.host !== '';

        return isElectron || isNotDev;
    });

    /**
     * Tries to fetch JSON from the local data folder.
     * Falls back to API if local fetch fails or returns non-JSON (HTML fallback).
     */
    const fetchData = async (localPath, apiEndpoint) => {
        // Try local JSON first
        try {
            const response = await fetch(`./data/${localPath}`);
            const contentType = response.headers.get("content-type");

            if (response.ok && contentType && contentType.includes("application/json")) {
                return await response.json();
            }
        } catch (e) {
            console.log(`[DataRobustness] Local fetch failed for ${localPath}, falling back to API.`);
        }

        // Fallback to API
        try {
            console.log(`[DataRobustness] Fetching from API: ${apiUrl}/api/${apiEndpoint}`);
            const apiResponse = await axios.get(`/api/${apiEndpoint}`);
            return apiResponse.data;
        } catch (e) {
            console.error(`[DataRobustness] API fetch failed for ${apiEndpoint}`, e);
            throw e;
        }
    };

    /**
     * Resolves an asset path based on the current environment.
     */
    const resolveAssetUrl = (path) => {
        if (!path) return '';

        // Strip leading slash if present for relative paths
        const cleanPath = path.startsWith('/') ? path.substring(1) : path;

        if (isEngine.value) {
            // In Electron, assets are in the public/assets folder
            return `./assets/${cleanPath}`;
        } else {
            // In Emulation, assets are served from the Laravel storage
            return `${apiUrl}/storage/${cleanPath}`;
        }
    };

    const resolveImgUrl = (path) => {
        if (!path) return '';

        // Strip leading slash if present for relative paths
        const cleanPath = path.startsWith('/') ? path.substring(1) : path;

        if (isEngine.value) {
            // In Electron, assets are in the public/assets folder
            return `./assets/artwork/${cleanPath}`;
        } else {
            // In Emulation, assets are served from the Laravel storage
            return `${apiUrl}/storage/artwork/${cleanPath}`;
        }
    };

    /**
     * Resolves a static asset in the public folder (fonts, icons, etc.)
     */
    const resolveStaticUrl = (path) => {
        if (!path) return '';
        const cleanPath = path.startsWith('/') ? path.substring(1) : path;
        return isEngine.value ? `./${cleanPath}` : `/${cleanPath}`;
    };

    /**
     * Simple slugification for filenames/paths.
     */
    const slugify = (str) => {
        if (!str) return '';
        return str.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_]+/g, '-')
            .replace(/^-+|-+$/g, '');
    };

    /**
     * Resolves a character's GLB path based on their name.
     */
    const getCharacterGlbUrl = (name) => {
        if (!name) return '';
        const slug = slugify(name);
        return resolveAssetUrl(`glb/${slug}.glb`);
    };

    return {
        isEngine,
        fetchData,
        resolveAssetUrl,
        resolveImgUrl,
        resolveStaticUrl,
        slugify,
        getCharacterGlbUrl
    };
}
