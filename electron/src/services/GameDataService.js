export class GameDataService {
    async loadSectors() {
        return this._fetch('sectors.json');
    }

    async loadLocations() {
        return this._fetch('locations.json');
    }

    async loadPersonages() {
        return this._fetch('personages.json');
    }

    async loadItems() {
        return this._fetch('items.json');
    }

    async loadDialogues() {
        return this._fetch('dialogues.json');
    }

    async loadScenes() {
        return this._fetch('scenes.json');
    }

    async loadSettings() {
        return this._fetch('settings.json');
    }

    async getSceneById(sceneId) {
        const id = parseInt(sceneId);
        const scenes = await this.loadScenes() || [];

        const scene = scenes.find(s => s.id === id);
        if (scene) return scene;

        // Fallback to searching in other structures if necessary
        console.warn(`Scene with ID ${id} not found in scenes.json, checking legacy sources...`);

        // Search in sectors
        const sectors = await this.loadSectors() || [];
        for (const sector of sectors) {
            if (sector.scenes) {
                const s = sector.scenes.find(sc => sc.id === id);
                if (s) return s;
            }
        }

        // Search in locations
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

    async _fetch(filename) {
        if (this._cache && this._cache[filename]) {
            return this._cache[filename];
        }

        try {
            const response = await fetch(`./data/${filename}`);
            if (!response.ok) {
                throw new Error(`Failed to load ${filename}: ${response.statusText}`);
            }
            const data = await response.json();

            this._cache = this._cache || {};
            this._cache[filename] = data;

            return data;
        } catch (error) {
            console.error(`GameDataService Error: ${error.message}`);
            return null;
        }
    }
}

export const gameDataService = new GameDataService();
