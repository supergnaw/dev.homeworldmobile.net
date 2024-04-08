<?php

declare(strict_types=1);

namespace app;

use SQLite3;

class NimbusNews
{
    // vars
    protected string $uri = "https://func-nimbusx-webview-live.azureedge.net/api/serving";
    protected string $db_file = "." . DIRECTORY_SEPARATOR . "nimbus_news.sqlite";
    protected SQLite3 | bool $conn = false;

    public function __construct()
    {
        $this->compile_news();
    }

    public function __invoke(): void
    {
        $this->connect();
        $this->create_db();
        $this->compile_news();
    }

    public function __destruct()
    {
        $this->close();
    }

    protected function compile_news(): void
    {
        $this->connect();
        $this->create_db();
        $this->update();
    }

    protected function connect(): bool
    {
        try {
            if (!$this->conn) {
                $this->conn = new SQLite3(filename: $this->db_file);
            }
        } catch (\Exception $e) {
            die($e);
        }
        return true;
    }

    protected function create_db(): void
    {
        // create database
        $sql = "CREATE TABLE IF NOT EXISTS `event_calls` (
                    `call_id` INTEGER PRIMARY KEY AUTOINCREMENT ,
                    `call_time` DATETIME DEFAULT (STRFTIME('%Y-%m-%d %H:%M:%f', 'NOW', 'utc'))
                );
                CREATE TABLE IF NOT EXISTS `event_entries` (  
                    `event_link` TEXT PRIMARY KEY ,
                    `event_action` TEXT ,
                    `event_name` TEXT ,
                    `event_start` DATETIME ,
                    `event_end` DATETIME ,
                    `event_language` TEXT
                );";
        $this->conn->exec($sql);
    }

    protected function last_call(): string
    {
        try {
            $this->connect();
            $sql = "SELECT * FROM `event_calls` ORDER BY `call_time` DESC LIMIT 1;";
            $results = $this->conn->query(query: $sql);
            $rows = [];
            while ($result = $results->fetchArray(mode: SQLITE3_ASSOC)) $rows[] = $result;
            if ($rows and array_key_exists(key: 'call_time', array: $rows[0])) {
                return $rows[0]['call_time'];
            }
        } catch (\Exception $e) {
            die($e);
        }
        return gmdate(format: "Y-m-d H:i:s.u", timestamp: 0);
    }

    protected function close(): bool
    {
        try {
            if ($this->conn) {
                $this->conn->close();
            }
        } catch (\Exception $e) {
            die($e);
        }
        $this->conn = false;
        return true;
    }

    protected function update(): bool
    {
        $last_call = strtotime(datetime: $this->last_call());
        $go_stale = strtotime("-24 hours", intval(gmdate(format: "U")));
        if ($go_stale < $last_call) {
            return true;
        }

        try {
            $response = file_get_contents($this->uri);
            if (!$response) {
                return false;
            }
            $obj = json_decode($response);
            $this->connect();
            $sql = "INSERT OR IGNORE INTO `event_entries` (
                        `event_link`,`event_action`,`event_name`,`event_start`,`event_end`,`event_language`
                    ) VALUES (
                        :event_link, :event_action, :event_name, :event_start, :event_end, :event_language
                    );";
            $stmt = $this->conn->prepare($sql);
            foreach ($obj as $evt) {
                $stmt->bindValue(param: 'event_link', value: $evt->link);
                $stmt->bindValue(param: 'event_action', value: $evt->action);
                $stmt->bindValue(param: 'event_name', value: $evt->name);
                $stmt->bindValue(param: 'event_start', value: $evt->start);
                $stmt->bindValue(param: 'event_end', value: $evt->end);
                $stmt->bindValue(param: 'event_language', value: $evt->language);
                $stmt->execute();
            }
        } catch (Exception $e) {
            die($e);
        }
        return true;
    }

    public function get_news(): array
    {
        $results = $this->conn->query(query: "SELECT * FROM `event_entries` ORDER BY `event_start` DESC, `event_end` DESC;");
        $events = [];
        while ($result = $results->fetchArray(mode: SQLITE3_ASSOC)) $events[] = $result;
        return $events;
    }
}