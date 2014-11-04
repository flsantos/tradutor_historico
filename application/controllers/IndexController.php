<?php
include 'application/controllers/BaseController.php';

		
class IndexController extends BaseController{
	function __construct(){
		parent::__construct();	
	}
	
	function index(){
		$this->load->view('index');	
	}
	

	function GetHistorico(){
		$aluno = $this->request->aluno;
		
		//TODO: $historico = pegar e stripar historico do matriculaweb
		$historico=new stdClass();	
		$semestres = array();
		
		//$json = '{"semestres":[{"materias":[{"cod": 1, "orig": "Computação Básica", "nota": "MS"},{"cod": 5, "orig": "Organização e Arquitetura de Computadores", "nota": "MS"},{"cod": 2, "orig": "Cálculo 1","nota": "MS"},{"cod": 3, "orig": "Variável Complexa 1", "nota":"SS"}]},{"materias": [{"cod": 1, "orig": "Computação Básica","nota": "MS"},{"cod": 2, "orig": "Cálculo 1", "nota": "MS"},{"cod": 3, "orig": "Variável Complexa 1","nota": "MS"}]}]}';
		$json = get_historico_aluno($aluno->matricula, $aluno->senha);
		$historico = json_decode($json, true);
		
		$this->returnOK($historico);
	}
}
