<?php

class AcceptanceTest extends PHPUnit_Framework_TestCase
{
    public function testAlienFight()
    {
        $this->givenAlien(1)->atCity('A');
        $this->givenAlien(2)->atCity('B');

        $this->whenAlien(1)->movesTo('B');

        $this->thenAlien(2)->dies();
        //$this->thenAlien(1)->hasTakenPossessionOf('B');
    }

    private function givenAlien($name)
    {
        $this->alienHelpers[$name] = new AlienHelper(new Alien($name), $this);
        return $this->alienHelpers[$name];
    }

    private function whenAlien($name)
    {
        return $this->alienHelpers[$name];
    }

    private function thenAlien($name)
    {
        return $this->alienHelpers[$name];
    }

    public function setUp()
    {
        $this->projection = new CityInhabitantsProjection(); 
    }

    public $events = [];
}

class AlienHelper
{
    private $alien;
    
    public function __construct(Alien $alien, $context)
    {
        $this->alien = $alien;
        $this->context = $context;
    }

    public function atCity($cityName)
    {
        $city = new City($cityName); 
        $this->context->cities[$cityName] = $city;
        $this->registerEvents($city->placeAlien($this->alien));

    }

    public function movesTo($cityName)
    {
        $currentCity = $this->context->projection->whereIs($this->alien->__toString());
        $nextCity = $this->context->cities[$cityName]; 

        $this->registerEvents($currentCity->moveAlienTo($nextCity));
    }

    public function dies()
    {
        foreach ($this->context->events as $event)
        {
            if ($event instanceof AlienDead) {
                if ($event->alien() == $this->alien->__toString()) {
                    return;
                }
            }
        }
        $this->context->fail("Alien {$this->alien} is not dead.");
    }

    private function registerEvents($events)
    {
        $this->context->events = array_merge($this->context->events, $events);
        $this->context->projection->accept($events);
    }
}

class CityInhabitantsProjection
{
    private $alienToCity;

    public function accept($events)
    {
        foreach ($events as $event) {
            if ($event instanceof AlienLanded) {
                $this->alienToCity[$event->alien()] = $event->city();
            } 
        }
    }

    public function whereIs($alienName)
    {
        return $this->alienToCity[$alienName];
    }
}

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
                'Alien Vagrant has won the possession of city B from Resident',
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
            new AlienLanded((string) $alien, $this)
        ];    
    }

    public function moveAlienTo(self $nextCity)
    {
        if (!$this->alien) {
            throw new AlienNotPresent($nextCity);
        }
        if (!$nextCity->alien) {
            $nextCity->alien = $this->alien;
        }
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
            "Alien {$incoming} has won the possession of city {$this} from {$this->alien}",
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

class AlienLanded
{
    private $alienName;
    private $cityName;
    
    public function __construct($alienName, $cityName)
    {
        $this->alienName = $alienName;
        $this->cityName = $cityName;
    }

    public function alien()
    {
        return $this->alienName;
    }

    public function city()
    {
        return $this->cityName;
    }

    public function __toString()
    {
        return "Alien {$this->alien} starts at {$this->cityName}";
    }
}

class AlienNotPresent extends LogicException
{
}
