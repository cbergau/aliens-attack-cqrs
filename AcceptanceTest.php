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

    public function testAnAlienCanMoveToAnotherCity()
    {
        $currentCity = new City('A');
        $currentCity->placeAlien(new Alien(1));
        $nextCity = new City('B');

        $events = $currentCity->moveAlienTo($nextCity);
        $this->assertEquals(
            [
                'Alien 1 moved to city B',
            ],
            $events
        );
    }
}

class City
{
    private $name;
    private $alien;
    
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
        $this->alien = $alien;
        return [
            "Alien $alien starts at {$this->name}",
        ];    
    }

    // TODO: try self $nextCity
    public function moveAlienTo(City $nextCity)
    {
        return [
            "Alien {$this->alien} moved to city $nextCity",
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
