<?php
/**
 * User: Nicu Neculache
 * Date: 14.04.2019
 * Time: 18:20
 */

class Player
{
    private $id = 0;
    private $id_table = 0;
    private $name = "Player";
    private $cards;

    /**
     * Player constructor.
     * @param int $id
     * @param int $id_table
     * @param string $name
     * @param array $cards
     */
    public function __construct($id, $id_table, $name, array $cards)
    {
        $this->id = $id;
        $this->id_table = $id_table;
        $this->name = $name;
        $this->cards = $cards;
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getIdTable()
    {
        return $this->id_table;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getCards()
    {
        return $this->cards;
    }

    /**
     * @param mixed $cards
     */
    public function setCards(array $cards)
    {
        $this->cards = $cards;
    }
}