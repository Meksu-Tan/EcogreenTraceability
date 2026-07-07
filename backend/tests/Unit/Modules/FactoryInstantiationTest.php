<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use Database\Factories\BalanceDetailFactory;
use Database\Factories\BalanceHeaderFactory;
use Database\Factories\BalanceTemporaryFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\MaterialDocumentFactory;
use Database\Factories\MaterialFactory;
use Database\Factories\PlantFactory;
use Database\Factories\SupplierFactory;
use Database\Factories\TankFactory;
use Database\Factories\TraceDetailFactory;
use Database\Factories\TraceHeaderFactory;
use PHPUnit\Framework\TestCase;

class FactoryInstantiationTest extends TestCase
{
    public function test_manufacturer_factory_class_exists(): void
    {
        $this->assertTrue(class_exists(ManufacturerFactory::class));
    }

    public function test_material_factory_class_exists(): void
    {
        $this->assertTrue(class_exists(MaterialFactory::class));
    }

    public function test_supplier_factory_class_exists(): void
    {
        $this->assertTrue(class_exists(SupplierFactory::class));
    }

    public function test_plant_factory_class_exists(): void
    {
        $this->assertTrue(class_exists(PlantFactory::class));
    }

    public function test_tank_factory_class_exists(): void
    {
        $this->assertTrue(class_exists(TankFactory::class));
    }

    public function test_transaction_factories_class_exist(): void
    {
        $this->assertTrue(class_exists(BalanceHeaderFactory::class));
        $this->assertTrue(class_exists(BalanceDetailFactory::class));
        $this->assertTrue(class_exists(BalanceTemporaryFactory::class));
        $this->assertTrue(class_exists(TraceHeaderFactory::class));
        $this->assertTrue(class_exists(TraceDetailFactory::class));
        $this->assertTrue(class_exists(MaterialDocumentFactory::class));
    }
}
