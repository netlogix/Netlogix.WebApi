<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Tests\Unit\Service;

use Netlogix\WebApi\Domain\Model\SortParameter;
use Netlogix\WebApi\Service\DefaultSortParametersParser;
use Neos\Flow\Tests\UnitTestCase;

class DefaultSortParametersParserTest extends UnitTestCase
{
    /**
     * @var DefaultSortParametersParser
     */
    private $sortParametersParser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sortParametersParser = new DefaultSortParametersParser();
    }

    /**
     * @test
     * @dataProvider provideSortQueryStrings
     */
    public function testParseQueryString(string $queryString, array $expectedResults): void
    {
        $result = $this->sortParametersParser->parseQueryString($queryString);
        $this->assertCount(\count($expectedResults), $result);

        /** @var SortParameter $sortParameter */
        foreach ($result as $i => $sortParameter) {
            $this->assertSame($expectedResults[$i]['direction'], $sortParameter->getDirection());
            $this->assertSame($expectedResults[$i]['field'], $sortParameter->getField());
        }
    }

    public function provideSortQueryStrings()
    {
        return [
            [
                '-vorname',
                [
                    ['field' => 'vorname',  'direction' => 'DESC']
                ],
            ],
            [
                '+vorname,-nachname',
                [
                    ['field' => 'vorname',  'direction' => 'ASC'],
                    ['field' => 'nachname',  'direction' => 'DESC'],
                ]
            ],
        ];
    }
}
