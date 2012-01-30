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

/**
 * \brief
 *      An implementation of a Russian Roulette game.
 */
class   Erebot_Module_Roulette_Game
{
    /// Stores something that identifies the last shooter.
    protected $_lastShooter;

    /// Number of shots fired until now.
    protected $_shotCount;

    /// Number of the chamber containing the bullet.
    protected $_loadedChamber;

    /// Number of chambers in this gun.
    protected $_nbChambers;


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
        if ($shooter == $this->_lastShooter)
            throw new Erebot_Module_Roulette_TwiceInARowException();

        $this->_lastShooter = $shooter;
        $this->_shotCount++;

        if ($this->_shotCount == $this->_nbChambers-1 &&
            $this->_loadedChamber == $this->_nbChambers) {
            $this->reset();
            return self::STATE_RELOAD;
        }

        if ($this->_shotCount == $this->_loadedChamber) {
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
        $this->_loadedChamber = $this->_getRandom($this->_nbChambers);
        $this->_shotCount   = 0;
        $this->_lastShooter = NULL;
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
    protected function _getRandom($max)
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
        return $this->_shotCount;
    }

    /**
     * Returns the number of chambers in the gun.
     *
     * \retval int
     *      Number of chambers in the gun.
     */
    public function getChambersCount()
    {
        return $this->_nbChambers;
    }

    /**
     * Sets the number of chambers in the gun.
     *
     * \param int $nbChambers
     *      Number of chambers to use for the gun.
     */
    public function setChambersCount($nbChambers)
    {
        if (!is_int($nbChambers) || $nbChambers < 2)
            throw new Erebot_Module_Roulette_AtLeastTwoChambersException();

        $this->_nbChambers = $nbChambers;
        $this->reset();
    }
}

