<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameState extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'inventory',
        'unlocked_locations',
        'flags',
        'stats',
    ];

    protected $casts = [
        'inventory' => 'array',
        'unlocked_locations' => 'array',
        'flags' => 'array',
        'stats' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the user has a specific item.
     */
    public function hasItem($itemId): bool
    {
        $inventory = $this->inventory ?? [];
        return in_array($itemId, $inventory);
    }

    /**
     * Add an item to the inventory.
     */
    public function addItem($itemId): void
    {
        $inventory = $this->inventory ?? [];
        if (!in_array($itemId, $inventory)) {
            $inventory[] = $itemId;
            $this->inventory = $inventory;
            $this->save();
        }
    }

    /**
     * Remove an item from the inventory.
     */
    public function removeItem($itemId): void
    {
        $inventory = $this->inventory ?? [];
        if (($key = array_search($itemId, $inventory)) !== false) {
            unset($inventory[$key]);
            $this->inventory = array_values($inventory);
            $this->save();
        }
    }

    /**
     * Check if a location is unlocked.
     */
    public function isLocationUnlocked($locationId): bool
    {
        $locations = $this->unlocked_locations ?? [];
        return in_array($locationId, $locations);
    }

    /**
     * Unlock a location.
     */
    public function unlockLocation($locationId): void
    {
        $locations = $this->unlocked_locations ?? [];
        if (!in_array($locationId, $locations)) {
            $locations[] = $locationId;
            $this->unlocked_locations = $locations;
            $this->save();
        }
    }

    /**
     * Set a story flag.
     */
    public function setFlag($key, $value): void
    {
        $flags = $this->flags ?? [];
        $flags[$key] = $value;
        $this->flags = $flags;
        $this->save();
    }

    /**
     * Get a story flag.
     */
    public function getFlag($key, $default = null)
    {
        $flags = $this->flags ?? [];
        return $flags[$key] ?? $default;
    }

    /**
     * Check a generic condition from a dialog node.
     * 
     * @param array $condition ['type' => '...', 'key' => '...', 'value' => '...']
     * @return bool
     */
    public function checkCondition(array $condition): bool
    {
        $type = $condition['type'] ?? null;

        switch ($type) {
            case 'has_item':
                return $this->hasItem($condition['value']);
            case 'missing_item':
                return !$this->hasItem($condition['value']);
            case 'flag_equals':
                return $this->getFlag($condition['key']) == $condition['value'];
            case 'flag_true':
                return (bool) $this->getFlag($condition['key']);
            case 'flag_false':
                return !(bool) $this->getFlag($condition['key']);
            case 'location_unlocked':
                return $this->isLocationUnlocked($condition['value']);
            default:
                return true; // Default to true if unknown to avoid blocking
        }
    }

    /**
     * Perform a generic action from a dialog node.
     * 
     * @param array $action ['type' => '...', 'key' => '...', 'value' => '...']
     */
    public function performAction(array $action): void
    {
        $type = $action['type'] ?? null;

        switch ($type) {
            case 'add_item':
                $this->addItem($action['value']);
                break;
            case 'remove_item':
                $this->removeItem($action['value']);
                break;
            case 'set_flag':
                $this->setFlag($action['key'], $action['value']);
                break;
            case 'unlock_location':
                $this->unlockLocation($action['value']);
                break;
        }
    }
}
