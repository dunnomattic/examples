<?php

class Session implements SessionHandlerInterface
{
    public $db;
    public function __construct()
    {
        // Instantiate database.
        $this->db = new Database();
        // Set save handler. Referencing self in a non-standard way.
        session_set_save_handler($this, true);
        session_start();
    }

    public function open($path, $name) : bool
    {
        if ($this->db) {
            setcookie(session_name(),session_id(),time()+1800, '/', '.XXXXXX.com');
            return true;
        }
        return false;
    }

    public function close() : bool
    {
        /*if ($this->db->close()) {
            return true;
        }
        return false;*/
        // no op, don't disconnect from database, even if available, let PHP manage it
        return false;
    }

    // later on, change this return type hint to ?string so it allows for nulls
    //public function read($id) : ?string
    public function read($id)
    {
        // Set query
        $this->db->query('SELECT data FROM auth.sessions WHERE id = :id');

        // Bind the Id
        $this->db->bind(':id', $id);

        // Attempt execution
        // If successful
        if ($this->db->execute()) {
            // Save returned row
            $row = $this->db->single();
            // Return the data
            return $row['data'];
        } else {
            // Return an empty string
            return '';
        }
    }

    public function write($id, $data) : bool
    {
        // Create time stamp
        $access = time();
         
        // Set query
        $this->db->query('REPLACE INTO auth.sessions VALUES (:id, :access, :uid, :data)');
         
        // Bind data
        $this->db->bind(':id', $id);
        $this->db->bind(':access', $access);
        $this->db->bind(':uid', (empty($_SESSION["uid"]) ? 0 : $_SESSION["uid"]));
        $this->db->bind(':data', $data);

        // Attempt Execution
        if ($this->db->execute()) {
            return true;
        }
        return false;
    }

    public function destroy($id) : bool
    {
        // Set query
        $this->db->query('DELETE FROM auth.sessions WHERE id = :id');
         
        // Bind data
        $this->db->bind(':id', $id);
         
        // Attempt execution
        if ($this->db->execute()) {
            return true;
        }
        return false;
    }

    public function gc($max) : bool
    {
        // Calculate what is to be deemed old
        $old = time() - $max;

        // Set query
        $this->db->query('DELETE FROM auth.sessions WHERE access < :old');
         
        // Bind data
        $this->db->bind(':old', $old);
         
        // Attempt execution
        if ($this->db->execute()) {
            return true;
        }
        return false;
    }
}

$handler = new Session();
session_set_save_handler($handler, true);
if (!empty($_SESSION["mimickedBy"])) {
    apache_note('username', $_SESSION["email"] . ":" . $_SESSION["mimickedBy"] . "@XXXXXX.com");
}
else if (!empty($_SESSION["email"])) {
    apache_note('username', $_SESSION["email"]);
}
