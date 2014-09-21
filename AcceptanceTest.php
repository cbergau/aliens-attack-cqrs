<?php

class CityTest extends PHPUnit_Framework_TestCase
{
    public function testAliensStartFromACity()
    {
        $city = new City('A');
        $events = $city->placeAlien(new Alien(1));
        $this->assertEquals(
            [
                'Alien 1 starts at A',
            ],
            $events
        );
    }
}

class City
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

    public function placeAlien(Alien $alien)
    {
        return [
            "Alien $alien starts at {$this->name}",
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
