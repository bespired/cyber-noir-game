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

    async loadSettings() {
        return this._fetch('settings.json');
    }

    async _fetch(filename) {
        try {
            // In Vite dev, public/data is at /data
            // In Electron build, it's relative
            const response = await fetch(`./data/${filename}`);
            if (!response.ok) {
                throw new Error(`Failed to load ${filename}: ${response.statusText}`);
            }
            return await response.json();
        } catch (error) {
            console.error(`GameDataService Error: ${error.message}`);
            return null;
        }
    }
}

export const gameDataService = new GameDataService();
