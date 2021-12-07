<?php

require_once CORE . 'Controller.php';
require_once CORE . 'Database.php';
require_once API . 'objects' . DIRECTORY_SEPARATOR . 'GameException.php';

class apiController extends Controller
{
    public function index()
    {
        Application::redirectTo();
    }

    public function wakeUp(float $ver = 0, int $id_player = 0)
    {
        include_once API . 'objects' . DIRECTORY_SEPARATOR . 'Player.php';
        header('Content-Type: application/json');
        if ($id_player > 0) {
            try {
                $_SESSION['id_player'] = $id_player;
                $player = new Player();
                $player->readOne($id_player);
                include_once(API . 'table' . DIRECTORY_SEPARATOR . 'leave.php');
                die(json_encode(array("status" => $player->getIdTable(), "message" => "You are already in a game")));
            } catch (GameException $e) {
                session_unset();
                Debug::Log("Exception from ApiController", __FILE__, 'EXCEPTION');
                GameException::exitMessage($e->getCode());
            }
        }
        if ($ver < 2.7)
            GameException::exitMessage(22);
        die(json_encode(array("status" => 0, "message" => session_id())));
    }

    /**
     * @param string $action - API script from Table set
     * @param int $id_player - (optional) player id
     *
     * Run $action script from /api/table/
     * if it isn't exist, respond with JSON: {"status":-1}
     */
    public function table(string $action, int $id_player = 0)
    {
        if (file_exists(API . 'table' . DIRECTORY_SEPARATOR . $action . '.php')) {
            require_once API . 'table' . DIRECTORY_SEPARATOR . $action . '.php';
        } else die(json_encode(array("status" => -1)));
    }

    public function macao(string $action, int $id_player = 0)
        /**
         * @param string $action - API script from Macao set
         *
         * Run $action script from /api/macao/
         * if it isn't exist, respond with JSON: {"status":-1}
         */
    {
        if (file_exists(API . 'macao' . DIRECTORY_SEPARATOR . $action . '.php')) {
            require_once API . 'macao' . DIRECTORY_SEPARATOR . $action . '.php';
        } else die(json_encode(array("status" => -1)));
    }

    public function razboi(string $action)
        /**
         * @param string $action - API script from War set
         *
         * Run $action script from /api/war/
         * if it isn't exist, respond with JSON: {"status":-1}
         */
    {
        if (file_exists(API . 'razboi' . DIRECTORY_SEPARATOR . $action . '.php')) {
            require_once API . 'razboi' . DIRECTORY_SEPARATOR . $action . '.php';
        } else die(json_encode(array("status" => -1)));
    }

    /**
     * @param string $action - API script from Septica set
     *
     * Run $action script from /api/septica/
     * if it isn't exist, respond with JSON: {"status":-1}
     */
    public function septica(string $action)
    {
        if (file_exists(API . 'septica' . DIRECTORY_SEPARATOR . $action . '.php')) {
            require_once API . 'septica' . DIRECTORY_SEPARATOR . $action . '.php';
        } else die(json_encode(array("status" => -1)));
    }

    public function stats(string $month = "", int $year = 0)
    {
        header('Content-Type: application/json');
        $conn = Database::getConnection();
        if ($month == "")
            $month = date("M");
        if ($year == 0)
            $year = date("Y");
        if ($month == 'all')
            $stmt = $conn->prepare("SELECT * FROM stats");
        else {
            $stmt = $conn->prepare("SELECT * FROM stats WHERE month = ? AND year = ?");
            $stmt->bind_param('si', $month, $year);
        }
        if (!$stmt->execute()) {
            Debug::Log("Unable to read stats, $stmt->errno: $stmt->error");
            http_response_code(500);
            die("{\"status\"=> -1}");
        }
        $result = $stmt->get_result();
        $response = array();
        while ($row = $result->fetch_assoc()) {
            $item = array("Macao" => $row['Macao'],
                "Razboi" => $row['Razboi'],
                "Septica" => $row['Septica']);
            $response[$row['year']][$row['month']] = $item;
            //array_push($response, $item);
        }

        try {
            require_once "api/objects/Table.php";
            $table = new Table();
            $result = $table->readPaging(0, 100);
            $table_list = array();
            while ($row = $result->fetch_assoc()) {
                $table_item = array(
                    "id" => $row['id'],
                    "tableName" => $row['name'],
                    "password" => $row['password'] == '' ? '' : 'X',
                    "game" => $row['game'],
                    "playersLimit" => $row['players_limit'],
                    "rules" => $row['rules']
                );
                array_push($table_list, $table_item);
            }
            //array_push($response, $table_list);
            $response['Tables'] = $table_list;
        } catch (GameException $e) {
            Debug::Log($e->getMessage());
        }
        die(json_encode($response, JSON_PRETTY_PRINT));
    }
}