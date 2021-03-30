<?php
class BeneficiaryController
{
	static public function counts()
	{
		//Consulta
		$db = new DB();
		$conection = $db->conectar();

		$sql="SELECT COUNT(nombre) as users FROM beneficiarys WHERE 1";

		$res=mysqli_query($conection,$sql);
		$data=mysqli_fetch_assoc($res);
		
		return $data['users'];
	}
	
	public function show()
	{
		extract($_REQUEST);
		
		//Verify data
		$verifyEmpty = LoginController::VerifyEmpty([$search]);
		
		if ($verifyEmpty) {
			$_SESSION['statusBox'] = 'error';
			$_SESSION['statusBox_message'] = 'Debe rellenar todos los campos';
			return null;
		}
		
		//Consulta
		$db = new DB();
		$conection = $db->conectar();

		$sql="SELECT beneficiarys.seguimiento, beneficiarys.cedula, beneficiarys.nombre, beneficiarys.apellido, beneficiarys.nacimiento, beneficiarys.peso, beneficiarys.talla, users.name, beneficiarys.id as people_id, beneficiarys.sexo FROM beneficiarys 
		LEFT JOIN users ON users.id = beneficiarys.created_by
		WHERE cedula='$search'";

		$res=mysqli_query($conection,$sql);

		$cant=mysqli_num_rows($res);
		
		if ($cant <= 0) {
			$_SESSION['statusBox'] = 'error';
			$_SESSION['statusBox_message'] = 'No hay nadie registrado con esa cédula';
			return null;
		}
		
		$data=mysqli_fetch_assoc($res);
		
		unset($_SESSION['statusBox']);
		unset($_SESSION['statusBox_message']);
		return $data;
	}
	
	public function create()
	{
		extract($_REQUEST);
		
		//Verify data
		$verifyEmpty = LoginController::VerifyEmpty([$nacionalidad, $sexo]);
		
		//Consulta
		$db = new DB();
		$conection = $db->conectar();

		$sql="SELECT * FROM beneficiarys WHERE cedula='$cedula'";

		$res=mysqli_query($conection,$sql);

		$cant=mysqli_num_rows($res);

		if ($cant > 0) {
			$_SESSION['statusBox'] = 'error';
			$_SESSION['statusBox_message'] = 'La cedula ya está registrada en el sistema';
			header('location: edit_beneficiarios.php');
			return null;
		}
		
		//Verificar seguimiento
		$userId = $_SESSION['user_id'];
		$date = (new DateTime($fecha))->format('Y-m-d');
		if ($peso <= 0) {
			$_SESSION['statusBox'] = 'warning';
			$_SESSION['statusBox_message'] = 'El peso debe ser un número positivo';
			header('location: edit_beneficiarios.php');
			return null;
		}
		
		if ($talla <= 0) {
			$_SESSION['statusBox'] = 'warning';
			$_SESSION['statusBox_message'] = 'La estatura debe ser un número positivo';
			header('location: edit_beneficiarios.php');
			return null;
		}
		$seguimiento = boolval($seguimiento) ? 1 : 0;
		
		// Parse inputs to string SQL
		$sql_inputs = '';
		$sql_values = '';
		foreach($_POST as $key => $value) {
			if (strlen($value) > 0) {
				$sql_inputs= $sql_inputs."$key,";
				if ($value === 'on') {
					$sql_values= $sql_values."'1',";
				} else if ($key === 'nacimiento' || $key === 'fecha_embarazo' || $key === 'fecha_parto') {
					$date = (new DateTime($value))->format('Y-m-d');
					$sql_values= $sql_values."'$date',";
				}else {
					$sql_values= $sql_values."'$value',";
				}
			}
		}
		
		$sql="INSERT INTO beneficiarys ($sql_inputs created_by) VALUES ($sql_values '$userId')";
		
		$res=mysqli_query($conection,$sql);
		
		if (!$res) {
			$_SESSION['statusBox'] = 'error';
			$_SESSION['statusBox_message'] = 'No se pudo añadir al beneficiario';
			header('location: edit_beneficiarios.php');
			return null;
		}


		$_SESSION['statusBox'] = 'success';
		$_SESSION['statusBox_message'] = 'Beneficiario añadido';
		$this->addLog('Beneficiario '.$nombre.' añadido');
		header('location: edit_beneficiarios.php');
	}
	
	public function delete($id)
	{	
		//Verify data
		$verifyEmpty = LoginController::VerifyEmpty([$id]);
		
		if ($verifyEmpty) {
			$_SESSION['statusBox'] = 'error';
			$_SESSION['statusBox_message'] = 'Id vacio';
			header('location: delete_personas.php');
			return null;
		}
		
		//restore data
		$db = new DB();
		$conection = $db->conectar();
		$sql="SELECT * FROM beneficiarys
		WHERE id='$id'";

		$res=mysqli_query($conection,$sql);
		$data=mysqli_fetch_assoc($res);
		
		//Consulta
		$sql="DELETE FROM beneficiarys
		WHERE id='$id'";

		$res=mysqli_query($conection,$sql);

		$_SESSION['statusBox'] = 'success';
		$_SESSION['statusBox_message'] = 'Beneficiario borrado';
		$this->addLog('Beneficiario '.$data['nombre'].' eliminado');
		header('location: delete_personas.php');
	}
	
	public function addLog($action)
	{
		session_start();
		
		//Consulta
		$db = new DB();
		$conection = $db->conectar();

		$date = (new DateTime())->format('Y-m-d H:i:s');
		
		$user = $_SESSION['user_id'];
		$sql="INSERT INTO logs (user_id, action, date) VALUES ('$user', '$action', '$date')";
		
		$res=mysqli_query($conection,$sql);
	}
	
	public function clasificationIMC($IMC)
	{
		if ($IMC < 16) {
			return array('type' => 'C', 'color' => 'red-text darken-2', 'warning' => 'grave');
		}else if ($IMC <= 18.49) {
			return array('type' => 'B', 'color' => 'orange-text darken-2', 'warning' => 'leve y moderado');
		}else if ($IMC <=18.9) {
			return array('type' => 'A', 'color' => 'teal-text darken-2', 'warning' => 'moderado');
		}else if ($IMC > 18.9) {
			return array('type' => 'N', 'color' => 'grey-text darken-2', 'warning' => 'normal');
		}
	}
}
?>