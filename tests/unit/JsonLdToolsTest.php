<?php

declare (strict_types = 1);

namespace bmhm\WebtreesModules\jsonld;

use bmhm\WebtreesModules\jsonld\JsonLDTools;
use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Place;
use PHPUnit\Framework\MockObject\MockObject;

class JsonLdToolsTest extends \PHPUnit\Framework\TestCase
{

    public function testJsonLDValid_simple(): void
    {
        /* Given this individual */
        /** @var Person $person */
        $person = $this->createPersonMock();

        $this->assertEquals($person->familyName, 'best');
        $this->assertEquals($person->gender, 'M');
        /** @var string $person_json */
        $person_json = json_encode(
            JsonLDTools::jsonize($person),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        /* check the generated json. That could actually be another test case. */
        $person_decoded = (array) json_decode($person_json, true);

        $this->assertFalse(isset($person_decoded['birthPlace']));
        $this->assertTrue(isset($person_decoded['givenName']));
    }

    /**
     * Test which adds some more data.
     */
    public function testJsonLDValid_complex(): void
    {
        /* Given this individual */
        $record = $this->createMockRecord_complex();
        /** @var Person $person */
        $person = $this->createPersonMock($record);

        /** @var string $person_json */
        $person_json = json_encode(
            JsonLDTools::jsonize($person),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
        //print $person_json;

        /* check the generated json. That could actually be another test case. */
        $person_decoded = (array) json_decode($person_json, true);

        $this->assertTrue(isset($person_decoded['birthPlace']));
        $this->assertTrue(isset($person_decoded['deathPlace']));
    }

    private function createPersonMock(Individual $record = null): Person
    {
        /* Expect this person to be rendered correctly. */
        /** @var Person $person */
        $person = new Person(true);

        if ($record === null) {
            $record = $this->createMockRecord();
            return JsonLDTools::fillPersonFromRecord($person, $record);
        }

        return JsonLDTools::fillPersonFromRecord($person, $record);
    }

    private function createMockRecord(): Individual
    {
        /** @var MockObject|Individual|GedcomRecord $record */
        $record = $this->createMock(Individual::class);

        $place = $this->createMock(Place::class);

        $primaryName = array(
            'fullNN' => 'fish are best',
            'givn' => 'fish',
            'surn' => 'best',
        );
        // $record->getAllNames()[$record->getPrimaryName()]['surn'];
        $record->method('getPrimaryName')
            ->willReturn(0);
        $record->method('getAllNames')
            ->willReturn(array(0 => $primaryName));
        $record->method('sex')
            ->willReturn('M');
        $record->method('url')
            ->willReturn('http://localhost.invalid/');
        $record->method('findHighlightedMediaFile')
            ->willReturn(null);

        return $record;
    }

    private function createMockRecord_complex(): Individual
    {
        /** @var MockObject|Individual|GedcomRecord $record */
        $record = $this->createMockRecord();

        $place = $this->createMock(Place::class);
        $place->method('url')
            ->willReturn('http://localhost.invalid');

        $record->method('getBirthPlace')
            ->willReturn($place);
        $record->method('getDeathPlace')
            ->willReturn($place);

        return $record;
    }

}
