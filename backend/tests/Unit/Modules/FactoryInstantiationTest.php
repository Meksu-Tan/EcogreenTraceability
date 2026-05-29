<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use PHPUnit\Framework\TestCase;

class FactoryInstantiationTest extends TestCase
{
    public function test_manufacturer_factory_class_exists(): void
    {
        $this->assertTrue(class_exists(\Database\Factories\ManufacturerFactory::class));
    }

    public function test_material_factory_class_exists(): void
    {
        $this->assertTrue(class_exists(\Database\Factories\MaterialFactory::class));
    }

    public function test_supplier_factory_class_exists(): void
    {
        $this->assertTrue(class_exists(\Database\Factories\SupplierFactory::class));
    }

    public function test_plant_factory_class_exists(): void
    {
        $this->assertTrue(class_exists(\Database\Factories\PlantFactory::class));
    }

    public function test_tank_factory_class_exists(): void
    {
        $this->assertTrue(class_exists(\Database\Factories\TankFactory::class));
    }

    public function test_storage_factories_class_exist(): void
    {
        $this->assertTrue(class_exists(\Database\Factories\WarehouseFactory::class));
        $this->assertTrue(class_exists(\Database\Factories\StorageTankFactory::class));
        $this->assertTrue(class_exists(\Database\Factories\StorageDetailFactory::class));
    }

    public function test_transaction_factories_class_exist(): void
    {
        $this->assertTrue(class_exists(\Database\Factories\BalanceHeaderFactory::class));
        $this->assertTrue(class_exists(\Database\Factories\BalanceDetailFactory::class));
        $this->assertTrue(class_exists(\Database\Factories\BalanceTemporaryFactory::class));
        $this->assertTrue(class_exists(\Database\Factories\TraceHeaderFactory::class));
        $this->assertTrue(class_exists(\Database\Factories\TraceDetailFactory::class));
        $this->assertTrue(class_exists(\Database\Factories\MaterialDocumentFactory::class));
    }
}
