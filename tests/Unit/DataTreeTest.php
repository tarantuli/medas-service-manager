<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Unit;

use Medas\ServiceManager\{
    DataTree\DataTree,
    DataTree\History,
    Exceptions\HistoryNotAvailableInDataTree,
    Exceptions\IndexNotFoundInDataTree
};
use PHPUnit\Framework\TestCase;

final class DataTreeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Basic get / set
    // -------------------------------------------------------------------------
    public function testGetThrowsForMissingKey(): void
    {
        $this->expectException(IndexNotFoundInDataTree::class);

        new DataTree()->get('missing');
    }

    public function testGetReturnsSetValue(): void
    {
        $tree = new DataTree();

        $tree->set('key', 'value');

        self::assertSame('value', $tree->get('key'));
    }

    public function testSetReturnsSelf(): void
    {
        $tree = new DataTree();

        self::assertSame($tree, $tree->set('key', 'value'));
    }

    // -------------------------------------------------------------------------
    // Null values must be stored and retrievable
    // -------------------------------------------------------------------------
    public function testNullValueIsStored(): void
    {
        $tree = new DataTree();

        $tree->set('key', null);

        self::assertTrue($tree->has('key'));
        self::assertNull($tree->get('key'));
    }

    public function testNullValueInDefaultsIsStored(): void
    {
        $tree = new DataTree(['key' => null]);

        self::assertTrue($tree->has('key'));
        self::assertNull($tree->get('key'));
    }

    // -------------------------------------------------------------------------
    // has() / getElse()
    // -------------------------------------------------------------------------
    public function testHasReturnsFalseForMissingKey(): void
    {
        self::assertFalse(new DataTree()->has('missing'));
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $tree = new DataTree();

        $tree->set('key', 'value');

        self::assertTrue($tree->has('key'));
    }

    public function testGetElseReturnsFallbackWhenMissing(): void
    {
        self::assertSame('fallback', new DataTree()->getElse('missing', 'fallback'));
    }

    public function testGetElseReturnsValueWhenPresent(): void
    {
        $tree = new DataTree();

        $tree->set('key', 'value');

        self::assertSame('value', $tree->getElse('key', 'fallback'));
    }

    // -------------------------------------------------------------------------
    // Dot-path notation
    // -------------------------------------------------------------------------
    public function testDotPathSetAndGet(): void
    {
        $tree = new DataTree();

        $tree->set('a.b.c', 'deep');

        self::assertSame('deep', $tree->get('a.b.c'));
    }

    public function testParentNodeReturnsSubtreeArray(): void
    {
        $tree = new DataTree();

        $tree->set('db.host', 'localhost');
        $tree->set('db.port', 3306);

        self::assertSame(['host' => 'localhost', 'port' => 3306], $tree->get('db'));
    }

    public function testEscapedDotIsNotASeparator(): void
    {
        $tree = new DataTree();

        $tree->set('key\\.with\\.dots', 'value');

        self::assertTrue($tree->has('key\\.with\\.dots'));
        self::assertSame('value', $tree->get('key\\.with\\.dots'));
    }

    // -------------------------------------------------------------------------
    // mergeArray / mergeDefaults
    // -------------------------------------------------------------------------
    public function testMergeArraySetsNestedValues(): void
    {
        $tree = new DataTree();

        $tree->mergeArray(['db' => ['user' => 'root', 'pass' => 'secret']]);

        self::assertSame('root', $tree->get('db.user'));
        self::assertSame('secret', $tree->get('db.pass'));
    }

    public function testMergeDefaultsDoesNotOverwriteExistingValues(): void
    {
        $tree = new DataTree();

        $tree->set('env', 'production');
        $tree->mergeDefaults(['env' => 'development']);

        self::assertSame('production', $tree->get('env'));
    }

    public function testMergeDefaultsSetsAbsentValues(): void
    {
        $tree = new DataTree();

        $tree->mergeDefaults(['env' => 'development']);

        self::assertSame('development', $tree->get('env'));
    }

    // -------------------------------------------------------------------------
    // History
    // -------------------------------------------------------------------------
    public function testHistoryTracksWrites(): void
    {
        $tree = new DataTree();

        $tree->set('env', 'test', 'default');
        $tree->set('env', 'dev', 'runtime');

        $history = $tree->getHistory('env');

        self::assertCount(2, $history);
        self::assertSame('default', $history[0]->source);
        self::assertSame('test', $history[0]->value);
        self::assertSame('runtime', $history[1]->source);
        self::assertSame('dev', $history[1]->value);
    }

    public function testGetHistoryThrowsForMissingKey(): void
    {
        $this->expectException(IndexNotFoundInDataTree::class);

        new DataTree()->getHistory('missing');
    }

    public function testGetSourceReturnsLatestSource(): void
    {
        $tree = new DataTree();

        $tree->set('env', 'test', 'default');
        $tree->set('env', 'dev', 'runtime');

        self::assertSame('runtime', $tree->getSource('env'));
    }

    public function testGetSourceThrowsForMissingKey(): void
    {
        $this->expectException(IndexNotFoundInDataTree::class);

        new DataTree()->getSource('missing');
    }

    public function testDefaultsConstructorSetsDefaultSource(): void
    {
        $tree = new DataTree(['env' => 'test']);

        self::assertSame('default', $tree->getSource('env'));
    }

    // -------------------------------------------------------------------------
    // Serialization (cache) drops history
    // -------------------------------------------------------------------------
    public function testSerializedTreeKeepsValues(): void
    {
        $tree = new DataTree(['db' => ['user' => 'test'], 'nullable' => null]);
        $restored = unserialize(serialize($tree));

        self::assertSame('test', $restored->get('db.user'));
        self::assertTrue($restored->has('nullable'));
        self::assertNull($restored->get('nullable'));
    }

    public function testSerializedFormContainsNoHistory(): void
    {
        $tree = new DataTree(['env' => 'test']);

        self::assertStringNotContainsString(History::class, serialize($tree));
    }

    public function testRestoredTreeThrowsOnGetHistory(): void
    {
        $restored = unserialize(serialize(new DataTree(['env' => 'test'])));

        $this->expectException(HistoryNotAvailableInDataTree::class);

        $restored->getHistory('env');
    }

    public function testRestoredTreeThrowsOnGetSource(): void
    {
        $restored = unserialize(serialize(new DataTree(['env' => 'test'])));

        $this->expectException(HistoryNotAvailableInDataTree::class);

        $restored->getSource('env');
    }

    public function testRestoredTreeStillReportsMissingKeyFirst(): void
    {
        $restored = unserialize(serialize(new DataTree()));

        $this->expectException(IndexNotFoundInDataTree::class);

        $restored->getHistory('missing');
    }

    public function testRestoredTreeAcceptsNewValues(): void
    {
        $restored = unserialize(serialize(new DataTree(['env' => 'test'])));

        $restored->set('env', 'dev');

        self::assertSame('dev', $restored->get('env'));
    }

    public function testGetElseReturnsFallbackBelowScalarLeaf(): void
    {
        $tree = new DataTree(['env' => 'test']);

        self::assertFalse($tree->has('env.deeper'));
        self::assertSame('fallback', $tree->getElse('env.deeper', 'fallback'));
    }

    // -------------------------------------------------------------------------
    // History is a value object
    // -------------------------------------------------------------------------
    public function testHistoryIsReadonly(): void
    {
        /** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
        $history = new History('source', 'value');

        $this->expectException(\Error::class);

        /** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
        $history->source = 'mutated';
    }
}
