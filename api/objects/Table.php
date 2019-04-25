<?php
/**
 * User: Nicu Neculache
 * Date: 14.04.2019
 * Time: 18:20
 */

class Table
{
    // database connection and table name
    private static $DBTable_name = "tables_list";
    private $conn;

    // object properties
    private $id;
    private $name;
    private $password;
    private $game;
    private $players_limit;
    private $rules;

    /**
     * Table constructor.
     * @param $database
     */
    public function __construct(mysqli $database)
    {
        $this->conn = $database;
    }

    public static function getTableName(): string
    {
        return Table::$DBTable_name;
    }

    /**
     * @param $id
     * @return bool
     */
    public function readOne($id)
    {
        // select all query
        $query = "SELECT * FROM " . Table::$DBTable_name . " WHERE id = $id";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute()) {
            // get retrieved row
            $row = $stmt->fetch();

            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->password = $row['password'];
            $this->game = $row['game'];
            $this->players_limit = $row['players_limit'];
            $this->rules = json_decode($row['rules']);
            return true;
        } else return false;
    }


    /**
     * @param $from_table_id
     * @param $count
     * @return bool|mysqli_result
     */
    public function readPaging($from_table_id, $count)
    {
        // select all query
        $query = "SELECT * FROM " . Table::$DBTable_name . " LIMIT $from_table_id, $count";

        // prepare query statement
        $result = $this->conn->query($query);

        // execute query
        if ($result)
            return $result;
        else return $this->conn->error;
    }

    public function search($from_table_id, $count, $name, $password, $game, $players_limit, $rules)
    {
        // select all query
        $query = "SELECT * FROM " . Table::$DBTable_name . " WHERE";

        if (!empty($name))
            $query = $query . " `name` = '" . $name . "'";
        if (!empty($password))
            $query = $query . " AND `password` = ''";
        if (!empty($game))
            $query = $query . " AND `game` = '" . $game . "'";
        if (!empty($players_limit)) {
            if (intdiv($players_limit, 10) != 0)
                $query = $query . " AND `players_limit` % 11 != 0";
            if ($players_limit % 10 != 0)
                $query = $query . " AND `players_limit` % 10 = '" . ($players_limit % 10) . "'";
        }
        if (!empty($rules)) {
            $rules = json_encode($rules);
            $query = $query . " AND `rules` = '" . $rules . "'";
        }

        $query = $query . " LIMIT $from_table_id, $count";
        $query = str_replace("WHERE AND", "WHERE", $query);

        // prepare query statement
        $result = $this->conn->query($query);

        // execute query
        if ($result)
            return $result;
        else return $this->conn->error;
    }

    /**
     * Create a new table
     * @return int => success = id | name already exist = 0 | other error = -1
     */
    public function create()
    {
        try {
            $rules = json_encode($this->rules);
            // select all query
            $query = "INSERT INTO " . Table::$DBTable_name . " (id, name, password, game, players_limit, rules) VALUES (NULL, '$this->name', $this->password, '$this->game', '$this->players_limit', '$rules')";

            // prepare query statement
            $stmt = $this->conn->prepare($query);

            // execute query
            if ($stmt->execute())
                return $stmt->insert_id;
            return -1;
        } catch (mysqli_sql_exception $e) {
            if($e->getCode() == 1062)
                return 0;
            return -1;
        }
    }

    /**
     * @return bool
     */
    public function deleteTable()
    {
        $query = "DELETE FROM " . Table::$DBTable_name . " WHERE id = $this->id";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute())
            return true;
        else return false;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getGame(): string
    {
        return $this->game;
    }

    /**
     * @param string $game
     */
    public function setGame(string $game): void
    {
        $this->game = $game;
    }

    /**
     * @return int
     */
    public function getPlayersLimit(): int
    {
        return $this->players_limit;
    }

    /**
     * @param int $players_limit
     */
    public function setPlayersLimit(int $players_limit): void
    {
        $this->players_limit = $players_limit;
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @param $rules
     */
    public function setRules($rules): void
    {
        $this->rules = $rules;
    }
}