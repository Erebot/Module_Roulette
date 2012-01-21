Usage
=====

This section assumes default values are used for all triggers.
Please refer to :ref:`configuration options <configuration options>`
for more information on how to customize triggers.


Provided commands
-----------------

This module provides the following command:

..  table:: Commands provided by |project|

    +---------------------------+-------------------------------------------+
    | Command                   | Description                               |
    +===========================+===========================================+
    | ``!roulette``             | Pulls the trigger on the Russian roulette |
    |                           | gun.                                      |
    +---------------------------+-------------------------------------------+

Example
-------

..  sourcecode:: irc

    15:34:45 < Foo> !roulette
    15:34:45 < Erebot> Foo: chamber 1 of 6 => +click+
    15:43:05 < Bar> !roulette
    15:43:06 < Erebot> Bar: chamber 2 of 6 => *BANG*
    15:43:06 * Erebot reloads

..  vim: ts=4 et
