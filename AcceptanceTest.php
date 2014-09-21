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
                'Alien 1 left city A',
                'Alien 1 reached city B',
            ],
            $events
        );
    }

    public function testAnAlienCanMoveForMoreThanOneTurn()
    {
        $pastCity = new City('A');
        $pastCity->placeAlien(new Alien(1));
        $currentCity = new City('B');
        $pastCity->moveAlienTo($currentCity);
        $nextCity = new City('C');

        $events = $currentCity->moveAlienTo($nextCity);
        $this->assertEquals(
            [
                'Alien 1 left city B',
                'Alien 1 reached city C',
            ],
            $events
        );
    }

    public function testAnAlienCannotBeInTwoPlaces()
    {
        $currentCity = new City('A');
        $currentCity->placeAlien(new Alien(1));
        $currentCity->moveAlienTo(new City('B'));

        $this->setExpectedException('AlienNotPresent');
        $currentCity->moveAlienTo(new City('C'));
    }

    public function testAnAlienArrivingAtAnOccupiedCityFightsWithAnotherAlien()
    {
        $currentCity = new City('A');
        $currentCity->placeAlien(new Alien('Vagrant'));
        $occupiedCity = new City('B');
        $occupiedCity->placeAlien(new Alien('Resident'));
        $currentCity->moveAlienTo($occupiedCity);

        $events = $occupiedCity->alienArrives(new Alien('Vagrant'));
        $this->assertEquals(
            [
                'Alien Vagrant fights Alien Resident in city B',
                'Alien Vagrant has won the possession of city B',
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

    public function moveAlienTo(self $nextCity)
    {
        if (!$this->alien) {
            throw new AlienNotPresent($nextCity);
        }
        $nextCity->alien = $this->alien;
        $this->alien = null;
        return [
            "Alien {$nextCity->alien} left city {$this}",
            "Alien {$nextCity->alien} reached city {$nextCity}",
        ];
    }

    public function alienArrives(Alien $incoming)
    {
        return [
            "Alien {$incoming} fights Alien {$this->alien} in city {$this}",
            //'Alien Vagrant has won the possession of city B',
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

class AlienNotPresent extends LogicException
{
}
