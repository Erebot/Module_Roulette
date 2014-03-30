<?php
/*
    This file is part of Erebot.

    Erebot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Erebot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Erebot.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace Erebot\Module\Roulette;

/**
 * \brief
 *      An implementation of a Russian Roulette game.
 */
class Game
{
    /// Stores something that identifies the last shooter.
    protected $lastShooter;

    /// Number of shots fired until now.
    protected $shotCount;

    /// Number of the chamber containing the bullet.
    protected $loadedChamber;

    /// Number of chambers in this gun.
    protected $nbChambers;


    /// The current chamber was empty.
    const STATE_NORMAL      = 'normal';

    /// The current chamber was empty and was the last possible empty chamber.
    const STATE_RELOAD      = 'reload';

    /// The current chamber contained a bullet.
    const STATE_BANG        = 'bang';


    /**
     * Creates a new game of Russian Roulette.
     *
     * \param int $nbChambers
     *      Number of chambers the gun will be made of.
     */
    public function __construct($nbChambers)
    {
        $this->setChambersCount($nbChambers);
    }

    /**
     * Pulls the trigger.
     *
     * \param mixed $shooter
     *      Something that identifies the current shooter.
     *      This is used to prevent the same person from
     *      shooting twice in a row.
     *
     * \retval opaque
     *      One of the STATE_* constants, indicating whether
     *      a real bullet or an empty chamber was shot.
     *
     * \throw Erebot_Module_Roulette_TwiceInARowException
     *      The same person tried to shoot twice in a row.
     */
    public function next($shooter)
    {
        if ($shooter == $this->lastShooter) {
            throw new \Erebot\Module\Roulette\TwiceInARowException();
        }

        $this->lastShooter = $shooter;
        $this->shotCount++;

        if ($this->shotCount == $this->nbChambers-1 &&
            $this->loadedChamber == $this->nbChambers) {
            $this->reset();
            return self::STATE_RELOAD;
        }

        if ($this->shotCount == $this->loadedChamber) {
            $this->reset();
            return self::STATE_BANG;
        }

        return self::STATE_NORMAL;
    }

    /**
     * Spins the cylinder.
     */
    public function reset()
    {
        $this->loadedChamber    = $this->getRandom($this->nbChambers);
        $this->shotCount        = 0;
        $this->lastShooter      = null;
    }

    /**
     * Returns a random number between
     * 1 and a maximum value (inclusive).
     *
     * \param int $max
     *      Highest value that may be returned
     *      by this PRNG.
     *
     * \retval int
     *      A random value between 1 and $max.
     */
    protected function getRandom($max)
    {
        return mt_rand(1, $max);
    }

    /**
     * Returns the numbers of shots that have
     * already taken place.
     *
     * \retval int
     *      Current number of shots.
     */
    public function getPassedChambersCount()
    {
        return $this->shotCount;
    }

    /**
     * Returns the number of chambers in the gun.
     *
     * \retval int
     *      Number of chambers in the gun.
     */
    public function getChambersCount()
    {
        return $this->nbChambers;
    }

    /**
     * Sets the number of chambers in the gun.
     *
     * \param int $nbChambers
     *      Number of chambers to use for the gun.
     */
    public function setChambersCount($nbChambers)
    {
        if (!is_int($nbChambers) || $nbChambers < 2) {
            throw new \Erebot\Module\Roulette\AtLeastTwoChambersException();
        }

        $this->nbChambers = $nbChambers;
        $this->reset();
    }
}
