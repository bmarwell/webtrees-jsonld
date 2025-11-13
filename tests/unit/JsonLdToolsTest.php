<?php

declare(strict_types=1);

namespace bmhm\WebtreesModules\jsonld;

use PHPUnit\Framework\TestCase;

/**
 * Test JsonLD functionality without complex webtrees mocking.
 * Tests focus on the JsonLD classes themselves rather than integration with webtrees.
 */
class JsonLdToolsTest extends TestCase
{
    /**
     * Test creating a Person object and converting to JSON-LD.
     */
    public function testPersonJsonLDCreation(): void
    {
        $person = new Person(true);
        $person->givenName = 'John';
        $person->familyName = 'Doe';
        $person->gender = 'M';

        $person_json = json_encode(
            JsonLDTools::jsonize($person),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        $person_decoded = json_decode($person_json, true);

        $this->assertIsArray($person_decoded);
        $this->assertEquals('John', $person_decoded['givenName']);
        $this->assertEquals('Doe', $person_decoded['familyName']);
        $this->assertEquals('M', $person_decoded['gender']);
    }

    /**
     * Test that empty fields are removed from JSON-LD output.
     */
    public function testJsonLDRemovesEmptyFields(): void
    {
        $person = new Person(true);
        $person->givenName = 'Jane';
        $person->familyName = 'Smith';
        // Leave birthPlace empty

        $person_json = json_encode(
            JsonLDTools::jsonize($person),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        $person_decoded = json_decode($person_json, true);

        $this->assertArrayHasKey('givenName', $person_decoded);
        $this->assertArrayNotHasKey('birthPlace', $person_decoded);
    }

    /**
     * Test Person with additional data like places.
     */
    public function testPersonWithPlaceData(): void
    {
        $person = new Person(true);
        $person->givenName = 'Alice';
        $person->familyName = 'Johnson';
        
        $birthPlace = new JsonLD_Place();
        $birthPlace->name = 'New York';
        $person->birthPlace = $birthPlace;

        $person_json = json_encode(
            JsonLDTools::jsonize($person),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        $person_decoded = json_decode($person_json, true);

        $this->assertArrayHasKey('birthPlace', $person_decoded);
        $this->assertIsArray($person_decoded['birthPlace']);
    }

    /**
     * Test that Person has correct JSON-LD @context.
     */
    public function testPersonHasContext(): void
    {
        $person = new Person(true);
        $person->givenName = 'Test';

        $person_json = json_encode(
            JsonLDTools::jsonize($person),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        $person_decoded = json_decode($person_json, true);

        $this->assertArrayHasKey('@context', $person_decoded);
        $this->assertEquals('http://schema.org', $person_decoded['@context']);
    }
}
