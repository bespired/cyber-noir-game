import { useDataRobustness } from '../composables/useDataRobustness';

export class GameDataService {
    constructor() {
        const { fetchData } = useDataRobustness();
        this.fetchData = fetchData;
        this._cache = {};
    }

    async loadSectors() {
        return this._getFile('sectors.json', 'sectors');
    }

    async loadLocations() {
        return this._getFile('locations.json', 'locations');
    }

    async loadPersonages() {
        return this._getFile('personages.json', 'personages');
    }

    async loadItems() {
        return this._getFile('items.json', 'aanwijzingen');
    }

    async loadDialogues() {
        return this._getFile('dialogues.json', 'dialogen');
    }

    async loadScenes() {
        return this._getFile('scenes.json', 'scenes');
    }

    async loadSettings() {
        return this._getFile('settings.json', 'instellingen');
    }

    async getSceneById(sceneId) {
        const id = parseInt(sceneId);
        const scenes = await this.loadScenes() || [];

        const scene = scenes.find(s => s.id === id);
        if (scene) return scene;

        // Fallback search in sectors/locations if not found in primary list
        console.warn(`Scene with ID ${id} not found in scenes.json, checking legacy sources...`);

        const sectors = await this.loadSectors() || [];
        for (const sector of sectors) {
            if (sector.scenes) {
                const s = sector.scenes.find(sc => sc.id === id);
                if (s) return s;
            }
        }

        const locations = await this.loadLocations() || [];
        for (const location of locations) {
            if (location.scenes) {
                const s = location.scenes.find(sc => sc.id === id);
                if (s) return s;
            }
        }

        console.error(`Scene with ID ${id} not found anywhere`);
        return null;
    }

    async _getFile(filename, apiEndpoint) {
        if (this._cache[filename]) {
            return this._cache[filename];
        }

        try {
            const data = await this.fetchData(filename, apiEndpoint);
            this._cache[filename] = data;
            return data;
        } catch (error) {
            console.error(`GameDataService Error loading ${filename}:`, error);
            return null;
        }
    }
}

export const gameDataService = new GameDataService();
