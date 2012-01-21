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

class   Erebot_Module_Roulette
extends Erebot_Module_Base
{
    protected $_roulette = NULL;

    public function _reload($flags)
    {
        $fmt = $this->getFormatter(FALSE);

        if ($flags & self::RELOAD_MEMBERS) {
            $nbChambers    = $this->parseInt('chambers', 6);
            try {
                $this->_roulette =
                    new Erebot_Module_Roulette_Game($nbChambers);
            }
            catch (Erebot_Module_Roulette_AtLeastTwoChambers_Exception $e) {
                throw new Exception(
                    $fmt->_('There must be at least 2 chambers')
                );
            }
        }

        if ($flags & self::RELOAD_HANDLERS) {
            $registry   = $this->_connection->getModule(
                'Erebot_Module_TriggerRegistry'
            );
            $matchAny  = Erebot_Utils::getVStatic($registry, 'MATCH_ANY');

            if (!($flags & self::RELOAD_INIT)) {
                $this->_connection->removeEventHandler($this->_handler);
                $registry->freeTriggers($this->_trigger, $matchAny);
            }

            $trigger        = $this->parseString('trigger', 'roulette');
            $this->_trigger = $registry->registerTriggers($trigger, $matchAny);
            if ($this->_trigger === NULL)
                throw new Exception(
                    $fmt->_('Could not register Roulette trigger')
                );

            $this->_handler = new Erebot_EventHandler(
                new Erebot_Callable(array($this, 'handleRoulette')),
                new Erebot_Event_Match_All(
                    new Erebot_Event_Match_InstanceOf(
                        'Erebot_Interface_Event_ChanText'
                    ),
                    new Erebot_Event_Match_TextStatic($trigger, TRUE)
                )
            );
            $this->_connection->addEventHandler($this->_handler);
            $this->registerHelpMethod(array($this, 'getHelp'));
        }
    }

    protected function _unload()
    {
    }

    public function getHelp(
        Erebot_Interface_Event_Base_TextMessage $event,
                                                $words
    )
    {
        if ($event instanceof Erebot_Interface_Event_Base_Private) {
            $target = $event->getSource();
            $chan   = NULL;
        }
        else
            $target = $chan = $event->getChan();

        $fmt        = $this->getFormatter($chan);
        $trigger    = $this->parseString('trigger', 'roulette');

        $bot        = $this->_connection->getBot();
        $moduleName = strtolower(get_class());
        $nbArgs     = count($words);

        if ($nbArgs == 1 && $words[0] == $moduleName) {
            $msg = $fmt->_(
                'Provides the <b><var name="trigger"/></b> command which '.
                'makes you play in the russian roulette game.',
                array('trigger' => $trigger)
            );
            $this->sendMessage($target, $msg);
            return TRUE;
        }

        if ($nbArgs < 2)
            return FALSE;

        if ($words[1] == $trigger) {
            $msg = $fmt->_(
                "<b>Usage:</b> !<var name='trigger'/>. Makes you press ".
                "the trigger of the russian roulette gun.",
                array('trigger' => $trigger)
            );
            $this->sendMessage($target, $msg);
            return TRUE;
        }
    }

    public function handleRoulette(
        Erebot_Interface_EventHandler   $handler,
        Erebot_Interface_Event_ChanText $event
    )
    {
        $nick       = $event->getSource();
        $chan       = $event->getChan();
        $action     = NULL;
        $chamber    = $this->_roulette->getPassedChambersCount()+1;
        $total      = $this->_roulette->getChambersCount();
        $fmt        = $this->getFormatter($chan);

        try {
            $state = $this->_roulette->next($nick);
        }
        catch (Erebot_Module_Roulette_TwiceInARowException $e) {
            $this->sendMessage(
                $chan,
                $fmt->_('You cannot go twice in a row')
            );
            return $event->preventDefault(TRUE);
        }

        switch ($state) {
            case Erebot_Module_Roulette_Game::STATE_RELOAD:
                $action = $fmt->_('spins the cylinder');
                // Fall through
            case Erebot_Module_Roulette_Game::STATE_NORMAL:
                $ending = $fmt->_('+click+');
                break;

            case Erebot_Module_Roulette_Game::STATE_BANG:
                $ending = $fmt->_('<b>*BANG*</b>');
                $action = $fmt->_('reloads');
                break;
        }

        $msg = $fmt->_(
            '<var name="nick"/>: chamber <var name="chamber"/> of '.
            '<var name="total"/> =&gt; <var name="message"/>',
            array(
                'nick' => $nick,
                'chamber' => $chamber,
                'total' => $total,
                'message' => $ending,
            )
        );
        $this->sendMessage($chan, $msg);

        if ($action !== NULL)
            $this->sendCommand("PRIVMSG $chan :\001ACTION $action\001");
        return $event->preventDefault(TRUE);
    }
}

