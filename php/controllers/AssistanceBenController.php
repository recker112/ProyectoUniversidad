<?php
class AssistanceBenController
{
	public function search()
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

		$sql="SELECT beneficiarys.seguimiento, beneficiarys.cedula, beneficiarys.nombre, beneficiarys.apellido, beneficiarys.nacimiento, beneficiarys.peso, beneficiarys.talla, users.name, beneficiarys.id as people_id, beneficiarys.sexo, beneficiarys.nacionalidad, beneficiarys.serial_patria, beneficiarys.codigo_patria, beneficiarys.direccion, beneficiarys.comunidad, beneficiarys.parroquia, beneficiarys.cantidad_de_hijos, beneficiarys.telef_local, beneficiarys.telef_celular, beneficiarys.estudiante, beneficiarys.grado_instruccion, beneficiarys.desea_estudiar, beneficiarys.que_desea_estudiar, beneficiarys.habilidad_posee, beneficiarys.inscrito_CNE, beneficiarys.ejerce_voto, beneficiarys.centro_electoral, beneficiarys.enfermedad, beneficiarys.discapacidad, beneficiarys.codigo_carnet_discapacidad, beneficiarys.embarazada, beneficiarys.fecha_embarazo, beneficiarys.fecha_parto, beneficiarys.bono_eventuales, beneficiarys.bono_lactancia, beneficiarys.bono_parto, beneficiarys.bono_jose_gregoreo, beneficiarys.bono_hogares, beneficiarys.pencionado, beneficiarys.pencionado_por, orga_social_politica
		FROM beneficiarys 
		LEFT JOIN users ON users.id = beneficiarys.created_by
		WHERE cedula='$search'";

		$res=mysqli_query($conection,$sql);

		$cant=mysqli_num_rows($res);
		
		if ($cant <= 0) {
			$_SESSION['statusBox'] = 'error';
			$_SESSION['statusBox_message'] = 'No hay nadie registrado con esa cédula';
			return null;
		}
		
		$dataPeople=mysqli_fetch_assoc($res);
		
		// Asistencias
		$sql = "SELECT peso, talla, id, MONTHNAME(assistance_ben.date) as mes, DATE_FORMAT(assistance_ben.date,'%Y') as year 
		FROM `assistance_ben` 
		WHERE beneficiary_id = '".$dataPeople['people_id']."' AND (NOT `peso`='NULL' AND NOT `talla`='NULL')
		ORDER BY date DESC";
		
		$res=mysqli_query($conection,$sql);
		
		$dataAsis = array();
		$dataNutricion = array();
		while($data=mysqli_fetch_assoc($res)) {
			$IMC = round($data['peso'] / ($data['talla']**2), 2);
			array_push($dataAsis, array($data['mes'], $data['year'], $data['id']));
			array_push($dataNutricion, array($data['peso'],$IMC,$data['talla']));
		}
		
		$MasterArray = array($dataPeople, $dataAsis, $dataNutricion);
		
		unset($_SESSION['statusBox']);
		unset($_SESSION['statusBox_message']);
		return $MasterArray;
	}
	
	public function getAssis($id) {
		//Consulta
		$db = new DB();
		$conection = $db->conectar();
		
		$sql = "SELECT peso, talla, id, date
		FROM `assistance_ben` 
		WHERE beneficiary_id = '$id'
		ORDER BY date DESC";
		
		$res=mysqli_query($conection,$sql);
		
		$array=[];
		$i=0;
		while($data=mysqli_fetch_assoc($res)) {
			$array[$i]['peso'] = $data['peso'];
			$array[$i]['talla'] = $data['talla'];
			$array[$i]['id'] = $data['id'];
			$array[$i]['date'] = $data['date'];
			$i++;
		}
		
		return $array;
	}
	
	public function create($id, $peso, $talla)
	{
		//Verify data
		$verifyEmpty = LoginController::VerifyEmpty([$id]);
		
		if ($verifyEmpty) {
			$_SESSION['statusBox'] = 'error';
			$_SESSION['statusBox_message'] = 'Id vacio';
			
			header('location: registrar_asistencia.php');
			return null;
		}
		
		//Verificar existencia
		$db = new DB();
		$conection = $db->conectar();

		$sql="SELECT * FROM beneficiarys WHERE id='$id'";

		$res=mysqli_query($conection,$sql);
		$dataUsed=mysqli_fetch_assoc($res);

		$cant=mysqli_num_rows($res);

		if ($cant === 0) {
			$_SESSION['statusBox'] = 'error';
			$_SESSION['statusBox_message'] = 'La cedula no está registrada en el sistema';
			
			header('location: registrar_asistencia.php');
			return null;
		}
		
		//Verificar que no se haya registrado HOY
		$sql="SELECT * FROM assistance_ben WHERE beneficiary_id='$id' ORDER BY date DESC LIMIT 1";

		$res=mysqli_query($conection,$sql);
		$dataAsis=mysqli_fetch_assoc($res);

		$date = (new DateTime())->format('Y-m-d');
		if ($dataAsis['date'] === $date) {
			$_SESSION['statusBox'] = 'error';
			$_SESSION['statusBox_message'] = 'La cedula ya fue registrada en la asistencia de hoy';
			
			header('location: registrar_asistencia.php');
			return null;
		}
		
		//Insertar asistencia
		if (!empty($peso) && !empty($talla)) {
			$sql="INSERT INTO assistance_ben (date, peso, talla, beneficiary_id) VALUES ('$date','$peso', '$talla','$id')";
		}else {
			$sql="INSERT INTO assistance_ben (date, beneficiary_id) VALUES ('$date','$id')";
		}

		$res=mysqli_query($conection,$sql);

		if (!$res) {
			$_SESSION['statusBox'] = 'error';
		$_SESSION['statusBox_message'] = 'No se pudo registrar la asistencia';
			header('location: registrar_asistencia.php');
			return null;
		}

		//Ok
		$_SESSION['statusBox'] = 'success';
		$_SESSION['statusBox_message'] = 'Asistencia añadida';
		$this->addLog('Asistencia del beneficiario '.$dataUsed['nombre']);
		header('location: registrar_asistencia.php');
	}
	
	public function delete($id, $cedula)
	{
		///Consulta
		$db = new DB();
		$conection = $db->conectar();
		
		$sql="DELETE FROM assistance_ben WHERE id='$id'";
		$res=mysqli_query($conection,$sql);
		
		$_SESSION['statusBox'] = 'success';
		$_SESSION['statusBox_message'] = 'Asistencia eliminada';
		header('location: buscar_asistencia.php?action&search='.$cedula);
	}
	
	static public function counts()
	{
		///Consulta
		$db = new DB();
		$conection = $db->conectar();
		
		$sql="SELECT COUNT(assistance_ben.id) as users,
			MONTHNAME(assistance_ben.date) as mes,
			DATE_FORMAT(assistance_ben.date,'%Y') as year
			FROM assistance_ben
			GROUP BY mes, year
			ORDER BY year, mes";
		
		$res=mysqli_query($conection,$sql);
		$request = [];
		while($data=mysqli_fetch_assoc($res)){
			$data['mes'] = ucfirst($data['mes']);
			$request[] = $data;
		}
		
		return json_encode($request);
	}
	
	public function registerNutricion($id) {
		///Consulta
		$db = new DB();
		$conection = $db->conectar();
		
		$sql="SELECT * FROM `assistance_ben` 
		WHERE `beneficiary_id`='$id' AND (NOT `peso`='NULL' AND NOT `talla`='NULL')";
		
		$res=mysqli_query($conection,$sql);
		$cant=mysqli_num_rows($res);
		$data=mysqli_fetch_assoc($res);
		
		$fecha_actual = strtotime(date("Y-m-d")."- 90 days");
		$fecha_registrada = strtotime($data['date']);
		
		// Verificar que haya al menos un registro
		if ($cant === 0) {
			return true;
		}
		
		if ($fecha_actual >= $fecha_registrada) {
			return true;
		}else {
			return false;
		}
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
}
?>