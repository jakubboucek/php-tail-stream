<?php

declare(strict_types=1);

namespace JakubBoucek\Tail\Tests;

use JakubBoucek\Tail\Processor;
use JakubBoucek\Tail\Stream\ExternalStream;
use ReflectionMethod;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class ProcessorTest extends TestCase
{
    public function dataSearchInBlock(): array
    {
        return [
            ['a....a....a....a....a', 'a', 1, 1, 20],
            ['b....b....b....b....b', 'b', 2, 2, 15],
            ['c....c....c....c....c', 'c', 3, 3, 10],
            ['d....d....d....d....d', 'd', 4, 4, 5],
            ['e....e....e....e....e', 'e', 5, 5, 0],
            ['f....f....f....f....f', 'f', 6, 5, null],
            ['.', 'g', 1, 0, null],
            ['.', 'h', 10, 0, null],
            ['', 'i', 1, 0, null],
            ['', 'j', 10, 0, null],
            ['kkkkk', 'k', 5, 5, 0],
            ['llllllllll', 'l', 5, 5, 5],
        ];
    }

    /**
     * @dataProvider dataSearchInBlock
     */
    public function testSearchInBlock(
        string $block,
        string $delimiter,
        int $limit,
        int $occurrences,
        ?int $position
    ): void {
        $processor = new Processor();
        // Method is private - use closure
        $method = new ReflectionMethod(Processor::class, 'searchInBlock');
        $closure = $method->getClosure($processor);
        $result = $closure($block, $delimiter, $limit);
        Assert::equal([$occurrences, $position], $result);
    }

    public function dataSearchLines(): array
    {
        return [
            ['a....a....a....a....a', 'a', 1, 1024, 16,20],
            ['b....b....b....b....b', 'b', 2, 1024, 11,20],
            ['c....c....c....c....c', 'c', 3, 1024, 6,20],
            ['d....d....d....d....d', 'd', 4, 1024, 1,20],
            ['e....e....e....e....e', 'e', 5, 1024, 0,20],
            ['f....f....f....f....f', 'f', 6, 1024, 0,20],
            ['g....g....g....g....g', 'g', 1, 10, 16,20],
            ['h....h....h....h....h', 'h', 2, 10, 11,20],
            ['i....i....i....i....i', 'i', 3, 10, 6,20],
            ['j....j....j....j....j', 'j', 4, 10, 1,20],
            ['k....k....k....k....k', 'k', 5, 10, 0,20],
            ['l....l....l....l....l', 'l', 6, 10, 0,20],
            ['.', 'm', 1, 1024, 0,1],
            ['.', 'n', 10, 10, 0,1],
            ['', 'o', 1, 1024, 0,0],
            ['', 'p', 10, 10, 0,0],
            ['qqqqq', 'q', 5, 1024, 0,4],
            ['rrrrrrrrrr', 'r', 5, 1024, 5,9],
            ['ssssssssss', 's', 5, 1, 5,9],
            ['..........', 't', 5, 1, 0,10],
        ];
    }

    /**
     * @dataProvider dataSearchLines
     */
    public function testSearchLines(
        string $content,
        string $delimiter,
        int $count,
        int $blocksSizes,
        int $expectedStart,
        int $expectedEnd,
    ): void {
        $stream = fopen('php://memory', 'wb+');
        fwrite($stream, $content);
        fseek($stream, 0);

        $processor = new Processor();
        // Method is private - use closure
        $method = new ReflectionMethod(Processor::class, 'searchLines');
        $closure = $method->getClosure($processor);
        [$start, $end] = $closure($count, $stream, $blocksSizes, $delimiter);

        fclose($stream);

        Assert::equal([$expectedStart, $expectedEnd], [$start, $end]);
    }

    public function dataLines(): array
    {
        return [
            ['a....a....a....a....a', '....a....a....', 'a', 3, 1024],
            ['....b....b....b....', '....b....b....', 'b', 3, 1024],
        ];
    }

    /**
     * @dataProvider dataLines
     */
    public function testLines(
        string $content,
        string $expected,
        string $delimiter,
        int $count,
        int $blocksSizes
    ): void {
        $input = fopen('php://memory', 'wb+');
        $inputSteam = new ExternalStream($input);

        $output = fopen('php://memory', 'wb+');
        $outputStream = new ExternalStream($output);

        fwrite($input, $content);
        fseek($input, 0);

        $processor = new Processor();
        // Method is private - use closure
        $method = new ReflectionMethod(Processor::class, 'lines');
        $closure = $method->getClosure($processor);
        $closure($count, $inputSteam, $outputStream, $blocksSizes, $delimiter);

        fseek($output, 0);
        $result = stream_get_contents($output);

        fclose($input);
        fclose($output);

        Assert::equal($expected, $result);
    }
}

(new ProcessorTest())->run();
