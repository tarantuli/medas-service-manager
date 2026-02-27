<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\ServiceManager\{DataTree\DataTree, DataTree\History, Exceptions\IndexNotFoundInDataTree};
use PHPUnit\Framework\TestCase;

class DataTreeTest extends TestCase
{
    public function testScalar(): void
    {
        $dataTree = new DataTree();

        // String
        $dataTree->set('env', 'test');

        $this->assertEquals('test', $dataTree->get('env'));

        // Integer value
        $dataTree->set('int', 1);

        $this->assertEquals('integer', gettype($dataTree->get('int')));
    }

    public function testUnknownPath(): void
    {
        $dataTree = new DataTree();

        $this->expectException(IndexNotFoundInDataTree::class);

        $dataTree->get('env');
    }

    public function testUnknownPathWithDefaults(): void
    {
        $dataTree = new DataTree(['env' => 'test']);

        $this->assertEquals('test', $dataTree->get('env'));
    }

    public function testOverwriteDefaultsAtRuntime(): void
    {
        $dataTree = new DataTree(['env' => 'test']);

        $dataTree->set('env', 'dev');

        $this->assertEquals('dev', $dataTree->get('env'));
        $this->assertEquals('runtime', $dataTree->getSource('env'));

        // Value history
        $expectedHistory = [
            new History('default', 'test'),
            new History('runtime', 'dev'),
        ];

        $this->assertEquals($expectedHistory, $dataTree->getHistory('env'));
    }

    public function testUnknownPathWithFallback(): void
    {
        $dataTree = new DataTree();

        self::assertEquals('dev', $dataTree->getElse('env', 'dev'));
    }

    public function testPath(): void
    {
        $dataTree = new DataTree();

        $dataTree->set('db.user', 'test');

        $this->assertEquals('test', $dataTree->get('db.user'));
        $this->assertEquals(['user' => 'test'], $dataTree->get('db'));

        // The history of a node is also a tree
        $this->assertArrayHasKey('user', $dataTree->getHistory('db'));
    }

    public function testSetRecursive(): void
    {
        $dataTree = new DataTree();

        $dataTree->mergeArray(['db' => ['user' => 'root']]);

        $this->assertEquals('root', $dataTree->get('db.user'));
    }
}
