<?php
class DB
{
  private $db;
  
  public function conectar()
  {
    $this->db = new mysqli('localhost', 'root', '1234', 'casa_alimentacion') or die 
    ('No conectado');
		
		$this->db->set_charset("utf8");
		$this->db->query("SET lc_time_names = 'es_ES'");

    return $this->db;
  }
	
	public function connectWithoutDB()
  {
    $this->db = new mysqli('localhost', 'root', '1234') or die 
    ('No conectado');
		
		$this->db->set_charset("utf8");
		$this->db->query("SET lc_time_names = 'es_ES'");

    return $this->db;
  }
}
?>