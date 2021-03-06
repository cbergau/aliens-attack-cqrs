<?php

class AcceptanceTest extends PHPUnit_Framework_TestCase
{
    public function testAlienFight()
    {
        $this->givenAlien(1)->atCity('A');
        $this->givenAlien(2)->atCity('B');

        $this->whenAlien(1)->movesTo('B');

        $this->thenAlien(2)->dies();
        $this->thenAlien(1)->isAt('B');
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
        $this->eventsThatGenerateNewCommands = [
            'AlienReachedCity' => function($event) {
                return function() use ($event) {
                    $city = $this->cities[$event->city()];
                    $alien = $this->alienHelpers[$event->alien()]->alien();
                    $events = $city->alienArrives($alien);
                    foreach ($events as $event) {
                        $this->accept($event);
                    }
                };
            }
        ];
    }

    public function accept($event)
    {
        $this->events[] = $event;
        $this->projection->accept($event);
        if (is_object($event)) {
            $eventClass = get_class($event);
            if (isset($this->eventsThatGenerateNewCommands[$eventClass])) {
                $newCommand = call_user_func(
                    $this->eventsThatGenerateNewCommands[$eventClass],
                    $event
                );
                $newCommand();
            }
        }
    }

    public $events = [];
    public $cities = [];
}

class AlienHelper
{
    private $alien;
    
    public function __construct(Alien $alien, $context)
    {
        $this->alien = $alien;
        $this->context = $context;
    }

    public function alien()
    {
        return $this->alien;
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

    public function isAt($city)
    {
        $currentCity = $this->context->projection->whereIs($this->alien->__toString());
        if ($currentCity != $city) {
            throw new Exception("Alien {$this->alien} should be at $city but it is at {$currentCity}");
        }
    }

    private function registerEvents($events)
    {
        foreach ($events as $event) {
            $this->context->accept($event);
        }
    }
}

class CityInhabitantsProjection
{
    private $alienToCity;

    public function accept($event)
    {
        if ($event instanceof AlienLanded) {
            $this->alienToCity[$event->alien()] = $event->city();
        }
        if ($event instanceof AlienWonCity) {
            $this->alienToCity[$event->alien()] = $event->city();
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
                new AlienLanded(1, 'A')
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
                new AlienReachedCity(1, 'B'),
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
                new AlienReachedCity(1, 'C'),
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

    public function testAnAlienArrivingAtAnOccupiedCityDoesNotImmediatelyReplaceOrFight()
    {
        $currentCity = new City('A');
        $currentCity->placeAlien(new Alien('Vagrant'));
        $occupiedCity = new City('B');
        $occupiedCity->placeAlien(new Alien('Resident'));
        $events = $currentCity->moveAlienTo($occupiedCity);

        $this->assertEquals(
            [
                'Alien Vagrant left city A',
                new AlienReachedCity('Vagrant', 'B'),
            ],
            $events
        );
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
                new AlienDead('Resident'),
                new AlienWonCity('Vagrant', 'B'),
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
        $movingAlien = $this->alien;
        if (!$nextCity->alien) {
            $nextCity->alien = $this->alien;
        }
        $this->alien = null;
        return [
            "Alien {$movingAlien} left city {$this}",
            new AlienReachedCity($movingAlien->__toString(), $nextCity->__toString()),
        ];
    }

    public function alienArrives(Alien $incoming)
    {
        return [
            "Alien {$incoming} fights Alien {$this->alien} in city {$this}",
            new AlienDead($this->alien),
            new AlienWonCity((string) $incoming, (string) $this, (string) $this->alien),
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

class AlienWonCity
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
        return "Alien {$this->alien} has won the possession of {$this->cityName}";
    }
}

class AlienDead
{
    private $alienName;
    
    public function __construct($alienName)
    {
        $this->alienName = $alienName;
    }

    public function alien()
    {
        return $this->alienName;
    }

    public function __toString()
    {
        return "Alien {$this->alien} is dead, Jim";
    }
}


class AlienReachedCity
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
        return "Alien {$this->alienName} reached city {$this->cityName}";
    }
}

class AlienNotPresent extends LogicException
{
}
