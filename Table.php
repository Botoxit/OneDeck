<?php
/**
 * User: Neculache Nicu
 * Date: 14.04.2019
 * Time: 18:20
 */

class Table
{
    private $id = 0;
    private $cards;
    private $round = 0;
    private $deck;
    private $last_update = 0;

    /**
     * table constructor.
     * @param int $id
     * @param $cards
     * @param int $round
     * @param $deck
     * @param int $last_update
     */
    public function __construct($id, $cards, $round, $deck, $last_update)
    {
        $this->id = $id;
        $this->cards = $cards;
        $this->round = $round;
        $this->deck = $deck;
        $this->last_update = $last_update;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCards()
    {
        return $this->cards;
    }

    /**
     * @return int
     */
    public function getRound()
    {
        return $this->round;
    }

    /**
     * @return mixed
     */
    public function getDeck()
    {
        return $this->deck;
    }

    /**
     * @return int
     */
    public function getLastUpdate()
    {
        return $this->last_update;
    }
}