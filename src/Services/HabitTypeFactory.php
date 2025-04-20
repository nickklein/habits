<?php

namespace NickKlein\Habits\Services;

use NickKlein\Habits\Interfaces\HabitTypeInterface;
use NickKlein\Habits\Services\TimeHabitHandler;

class HabitTypeFactory
{
    
    public function __construct()
    {
        //
    }
    
    /**
     * Get the appropriate habit type handler based on habit type
     *
     * @param string $habitType
     * @return HabitTypeInterface
     * @throws \InvalidArgumentException
     */
    public function getHandler(string $habitType): HabitTypeInterface
    {
        switch ($habitType) {
            case 'time':
                return new TimeHabitHandler();
            /*case 'unit':*/
                /*return new UnitHabitHandler();*/
            /*case 'ml':*/
                /*return new MlHabitHandler();*/
            default:
                throw new \InvalidArgumentException("Unsupported habit type: {$habitType}");
        }
    }
}
