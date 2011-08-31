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

class   Erebot_Module_Roulette_Game
{
    protected $_lastShooter;
    protected $_shootCount;
    protected $_shootToBang;
    protected $_nbChambers;

    const STATE_NORMAL      = 'normal';
    const STATE_RELOAD      = 'reload';
    const STATE_BANG        = 'bang';

    public function __construct($nbChambers)
    {
        $this->setChambersCount($nbChambers);
    }

    public function next($shooter)
    {
        if ($shooter == $this->_lastShooter)
            throw new Erebot_Module_Roulette_TwiceInARowException();

        $this->_lastShooter = $shooter;
        $this->_shootCount++;

        if ($this->_shootCount == $this->_nbChambers-1 &&
            $this->_shootToBang == $this->_nbChambers) {
            $this->reset();
            return self::STATE_RELOAD;
        }

        if ($this->_shootCount == $this->_shootToBang) {
            $this->reset();
            return self::STATE_BANG;
        }

        return self::STATE_NORMAL;
    }

    public function reset()
    {
        $this->_shootToBang = $this->getRandom($this->_nbChambers);
        $this->_shootCount  = 0;
        $this->_lastShooter = NULL;
    }

    protected function getRandom($max)
    {
        return mt_rand(1, $max);
    }

    public function setChambersCount($nbChambers)
    {
        if (!is_int($nbChambers) || $nbChambers < 2)
            throw new Erebot_Module_Roulette_AtLeastTwoChambersException();

        $this->_nbChambers = $nbChambers;
        $this->reset();
    }

    public function getPassedChambersCount()
    {
        return $this->_shootCount;
    }

    public function getChambersCount()
    {
        return $this->_nbChambers;
    }
}

