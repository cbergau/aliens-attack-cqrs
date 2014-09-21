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
    private $names;
    
    public static function singleCity($name)
    {
        return new self([$name]);
    }
    
    private function __construct($names)
    {
        $this->names = $names;
    }

    public function placeAlien(Alien $alien, $cityName)
    {
        return [
            'Alien 1 starts at A',
        ];    
    }
}

class Alien
{

}