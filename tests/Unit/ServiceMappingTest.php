<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Unit;

use Medas\ServiceManager\Mapping\ServiceMapping;
use PHPUnit\Framework\TestCase;

final class ServiceMappingTest extends TestCase
{
    // -------------------------------------------------------------------------
    // set() / get() / has()
    // -------------------------------------------------------------------------
    public function testHasReturnsFalseForUnknownType(): void
    {
        self::assertFalse(new ServiceMapping()->has('Unknown'));
    }

    public function testSetAndGet(): void
    {
        $mapping = new ServiceMapping();

        $mapping->set('InterfaceA', 'ClassA');

        self::assertTrue($mapping->has('InterfaceA'));
        self::assertSame('ClassA', $mapping->get('InterfaceA'));
    }

    public function testSetReturnsTrueWhenMappingChanges(): void
    {
        $mapping = new ServiceMapping();

        self::assertTrue($mapping->set('InterfaceA', 'ClassA'));
    }

    public function testSetReturnsFalseWhenMappingUnchanged(): void
    {
        $mapping = new ServiceMapping();

        $mapping->set('InterfaceA', 'ClassA');

        self::assertFalse($mapping->set('InterfaceA', 'ClassA'));
    }

    public function testSetOverwritesExistingMapping(): void
    {
        $mapping = new ServiceMapping();

        $mapping->set('InterfaceA', 'ClassA');
        $mapping->set('InterfaceA', 'ClassB');

        self::assertSame('ClassB', $mapping->get('InterfaceA'));
    }

    // -------------------------------------------------------------------------
    // addFromPackage() — single implementor
    // -------------------------------------------------------------------------
    public function testSingleImplementorIsResolvable(): void
    {
        $mapping = new ServiceMapping();

        $mapping->addFromPackage([['InterfaceA', 'ClassA']]);

        self::assertTrue($mapping->has('InterfaceA'));
        self::assertSame('ClassA', $mapping->get('InterfaceA'));
    }

    // -------------------------------------------------------------------------
    // addFromPackage() — multiple implementors for the same type
    // -------------------------------------------------------------------------
    public function testMultipleImplementorsMakeTypeUnresolvable(): void
    {
        $mapping = new ServiceMapping();

        $mapping->addFromPackage([
            ['InterfaceA', 'ClassA'],
            ['InterfaceA', 'ClassB'],
        ]);

        self::assertFalse($mapping->has('InterfaceA'));
    }

    // -------------------------------------------------------------------------
    // getAll()
    // -------------------------------------------------------------------------
    public function testGetAllExcludesAmbiguousTypes(): void
    {
        // makes InterfaceA ambiguous
        $mapping = new ServiceMapping();

        $mapping->addFromPackage([
            ['InterfaceA', 'ClassA'],
            ['InterfaceB', 'ClassB'],
            ['InterfaceA', 'ClassC'],
        ]);

        $all = $mapping->getClassNames();

        self::assertArrayHasKey('InterfaceB', $all);
        self::assertArrayNotHasKey('InterfaceA', $all);
    }

    public function testGetAllDoesNotExcludeFalsyValues(): void
    {
        // Regression: array_filter() without callback would strip '' and '0'
        $mapping = new ServiceMapping();

        $mapping->set('InterfaceA', 'ClassA');

        self::assertArrayHasKey('InterfaceA', $mapping->getClassNames());
    }
}
