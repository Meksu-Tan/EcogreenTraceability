<?php
declare(strict_types=1);
namespace Modules\Shared\Traits;

trait TankNameFormatterTrait
{
    /**
     * Format a tank name for display.
     */
    protected function formatTankName(?string $name): ?string
    {
        if (!$name) return $name;
        
        if (stripos($name, 'ADJUSTMENT') !== false) {
            return str_ireplace(
                ['ADJUSTMENT IN', 'ADJUSTMENT OUT'],
                ['Adjustment IN', 'Adjustment OUT'],
                strtoupper($name)
            );
        }

        if (preg_match('/^(EOB|EOMB)\s*(\d*)\s*(FEED|PRODUCT|WIP|STORAGE|MPR)\s*(TANK)?/i', $name, $matches)) {
            $plantType = strtoupper($matches[1]);
            $plantNum = $matches[2];
            $type = strtoupper($matches[3]);
            
            if ($type !== 'WIP' && $type !== 'MPR') {
                $type = ucfirst(strtolower($type));
            }
            
            $plant = $plantType . ($plantNum ? ' ' . $plantNum : '');
            return trim($type . ' ' . $plant);
        }

        return $name;
    }
}
