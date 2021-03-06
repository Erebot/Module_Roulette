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

namespace Erebot\Module;

/**
 * \brief
 *      A module that provides an implementation
 *      of a Russian Roulette game.
 */
class Roulette extends \Erebot\Module\Base implements \Erebot\Interfaces\HelpEnabled
{
    /// The actual Russian Roulette gun.
    protected $roulette = null;


    /**
     * This method is called whenever the module is (re)loaded.
     *
     * \param int $flags
     *      A bitwise OR of the Erebot::Module::Base::RELOAD_*
     *      constants. Your method should take proper actions
     *      depending on the value of those flags.
     *
     * \note
     *      See the documentation on individual RELOAD_*
     *      constants for a list of possible values.
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function reload($flags)
    {
        $fmt = $this->getFormatter(false);

        if ($flags & self::RELOAD_MEMBERS) {
            $nbChambers    = $this->parseInt('chambers', 6);
            try {
                $this->roulette =
                    new \Erebot\Module\Roulette\Game($nbChambers);
            } catch (\Erebot\Module\Roulette\AtLeastTwoChambersException $e) {
                throw new \Exception(
                    $fmt->_('There must be at least 2 chambers')
                );
            }
        }

        if ($flags & self::RELOAD_HANDLERS) {
            $registry   = $this->connection->getModule(
                '\\Erebot\\Module\\TriggerRegistry'
            );
            if (!($flags & self::RELOAD_INIT)) {
                $this->connection->removeEventHandler($this->handler);
                $registry->freeTriggers($this->trigger, $registry::MATCH_ANY);
            }

            $trigger        = $this->parseString('trigger', 'roulette');
            $this->trigger = $registry->registerTriggers($trigger, $registry::MATCH_ANY);
            if ($this->trigger === null) {
                throw new \Exception(
                    $fmt->_('Could not register Roulette trigger')
                );
            }

            $this->handler = new \Erebot\EventHandler(
                array($this, 'handleRoulette'),
                new \Erebot\Event\Match\All(
                    new \Erebot\Event\Match\Type(
                        '\\Erebot\\Interfaces\\Event\\ChanText'
                    ),
                    new \Erebot\Event\Match\TextStatic($trigger, true)
                )
            );
            $this->connection->addEventHandler($this->handler);
        }
    }

    /**
     * Provides help about this module.
     *
     * \param Erebot::Interfaces::Event::Base::TextMessage $event
     *      Some help request.
     *
     * \param Erebot::Interfaces::TextWrapper $words
     *      Parameters passed with the request. This is the same
     *      as this module's name when help is requested on the
     *      module itself (in opposition with help on a specific
     *      command provided by the module).
     */
    public function getHelp(
        \Erebot\Interfaces\Event\Base\TextMessage   $event,
        \Erebot\Interfaces\TextWrapper              $words
    ) {
        if ($event instanceof \Erebot\Interfaces\Event\Base\PrivateMessage) {
            $target = $event->getSource();
            $chan   = null;
        } else {
            $target = $chan = $event->getChan();
        }

        $fmt        = $this->getFormatter($chan);
        $trigger    = $this->parseString('trigger', 'roulette');
        $nbArgs     = count($words);

        if ($nbArgs == 1 && $words[0] === get_called_class()) {
            $msg = $fmt->_(
                'Provides the <b><var name="trigger"/></b> command which '.
                'makes you play in the russian roulette game.',
                array('trigger' => $trigger)
            );
            $this->sendMessage($target, $msg);
            return true;
        }

        if ($nbArgs < 2) {
            return false;
        }

        if ($words[1] == $trigger) {
            $msg = $fmt->_(
                "<b>Usage:</b> !<var name='trigger'/>. Makes you press ".
                "the trigger of the russian roulette gun.",
                array('trigger' => $trigger)
            );
            $this->sendMessage($target, $msg);
            return true;
        }
    }

    /**
     * Handles a request to pull the trigger
     * of the Russian Roulette gun.
     *
     * \param Erebot::Interfaces::EventHandler $handler
     *      Handler that triggered this event.
     *
     * \param Erebot::Interfaces::Event::Base::ChanText $event
     *      A request to pull the trigger.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function handleRoulette(
        \Erebot\Interfaces\EventHandler   $handler,
        \Erebot\Interfaces\Event\ChanText $event
    ) {
        $nick       = $event->getSource();
        $chan       = $event->getChan();
        $action     = null;
        $chamber    = $this->roulette->getPassedChambersCount()+1;
        $total      = $this->roulette->getChambersCount();
        $fmt        = $this->getFormatter($chan);

        try {
            $state = $this->roulette->next($nick);
        } catch (\Erebot\Module\Roulette\TwiceInARowException $e) {
            $this->sendMessage(
                $chan,
                $fmt->_('You cannot go twice in a row')
            );
            return $event->preventDefault(true);
        }

        switch ($state) {
            case \Erebot\Module\Roulette\Game::STATE_RELOAD:
                $action = $fmt->_('spins the cylinder');
                // Fall through

            case \Erebot\Module\Roulette\Game::STATE_NORMAL:
                $ending = $fmt->_('+click+');
                break;

            case \Erebot\Module\Roulette\Game::STATE_BANG:
                $ending = $fmt->_('<b>*BANG*</b>');
                $action = $fmt->_('reloads');
                break;
        }

        $msg = $fmt->_(
            '<var name="nick"/>: chamber <var name="chamber"/> of '.
            '<var name="total"/> =&gt; <var name="message"/>',
            array(
                'nick'      => $nick,
                'chamber'   => $chamber,
                'total'     => $total,
                'message'   => $ending,
            )
        );
        $this->sendMessage($chan, $msg);

        if ($action !== null) {
            $this->sendCommand("PRIVMSG $chan :\001ACTION $action\001");
        }
        return $event->preventDefault(true);
    }
}
