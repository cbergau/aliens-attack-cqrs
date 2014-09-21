<?php

class AcceptanceTest extends PHPUnit_Framework_TestCase
{
    public function testAliensStartFromACity()
    {
        $map = Map::singleCity('A');
        $events = $map->placeAlien(new Alien(1), 'A');
        $this->assertEquals(
            [
                'Alien 1 starts at A',
            ],
            $events
        );
    }
}

class Map
{
    public static function singleCity($name)
    {
        return new self();
    }

    public function placeAlien(Alien $alien, $cityName)
    {
        return [
            "Alien $alien starts at {$cityName}",
        ];    
    }
}

class Alien
{
    private $name;
    
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function __toString()
    {
        return (string) $this->name;
    }
}
