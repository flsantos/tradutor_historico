<?php	
include 'application/controllers/BaseController.php';
include "simple_html_dom.php";

require_once 'HTTP/Request2.php';
class HistoricoController extends BaseController{
	function index(){
		$this->load->model("Materia");
		$this->load->model("Traducao");
		$data = new stdClass();
		$historico = $this->request;
		
		foreach ($historico->semestres as $semestre){
			foreach ($semestre->materias as $materia){
				$m = new stdClass();
				$m->cod = $materia->cod;
				
				if($mt = $this->Materia->get($m)){
					$mt->trad = new stdClass();
					$mid = new stdClass();
					$mid->materias_id = $mt->id;
				
					$trad = $this->Traducao->get($mid);
					if(is_array($trad))
						$materia->trad = $trad;
					
				}else{
					$m = new stdClass();
					$m->orig = $materia->orig;
					$m->cod = $materia->cod;
					if(!$this->Materia->insert($m)){
						$alert = new stdClass();
						$alert->msg = "A materia não foi inserida corretamente";
						$data->alerts[] = $alert;
					}
					
					$this->TranslateMateria($materia);
					$materia->trad = array();
				}
			}
		}
		
		$data->historico = $historico;
		
		$this->session->set_userdata('config', json_encode($data));
		$this->load->view('historico');
	}
	
	public function GetHistorico(){
		$response  = new stdClass;
		if(!$config = $this->session->userdata('config')){
			$alert->msg = "Sua sessão expirou";
			$error = "expired_session";
			
			$response->alerts[] = $alert;
			$response->error[] = $error;
			$this->returnError($response);	
		}
		$config = json_decode($config);
		$this->returnOK($config);
		
	}
	
	private function insertTraducoes($materia, $trads, &$error = false){
		$this->load->model('Materia');
		$this->load->model('Traducao');
		
		$m = new stdClass();
		if(!isset($materia->cod))
			return false;
		$m->cod = $materia->cod;	
			
		if(!($m = $this->Materia->get($m)))
			return false;
		
		foreach($trads as $trad){
			if (!is_object($trad))
				$trad = new stdClass();
			$trad->materias_id = $m->id;
			$query = new stdClass();
			$query->materias_id = $m->id;
			
			if(!isset($trad->txt) || $trad->txt == 'NULL')
				return false;
			
			$query->txt = $trad->txt;
			if(!$t = $this->Traducao->get($query))
				return $this->Traducao->insert($m->id, $trad);
			else{
				$t = $t[0];
				$t->choosen++;
				return $this->Traducao->update($t);
			}
			return false;
		}
	}
	
	public function DownloadPDF(){
		$historico = $this->request;
		foreach($historico->semestres as $semestre){
			foreach($semestre->materias as $materia){
				$response = $this->insertTraducoes($materia, $materia->trad);
			}
		}
		
		if($response)
			$this->returnOK($response);
		else
			$this->returnOK($response);
	}
	
	public function TranslateMateria(&$materia){
		$word = $materia->orig;
		$request = new HTTP_Request2("https://www.googleapis.com/language/translate/v2?key=AIzaSyDmDkz5NwFo9wcR5ZF9Uc8XPNZZt3Su6mA&q=".$word."source=pt&target=en", HTTP_Request2::METHOD_GET);
		
		$a = $request->send();
		var_dump($a);
	}
	
	
	

	/*
	 * Descrição da estrutura do objeto "$historico"
	
	 -historico
	 -aluno_nome
	 -curso
	 -habilitacao
	 -grau
	 -ingresso_na_unb
	 -forma_de_ingresso
	 -decreto
	 -matricula
	 -data_emissao
	 -aluno_pai
	 -aluno_mae
	 -dt_nascimento
	 -pais_nascimento
	 -cred_exigidos
	 -cred_obtidos
	 -cred_obter
	 -semestres
	 {
	 -periodo
	 -materias
	 {
	 -cod
	 -nome
	 -mencao
	 -area_con_obr
	 -area_con_opt
	 -dom_con_obr
	 -dom_con_opt
	 -mod_livre
	 -outros
	 }
	 }
	
	 */
	function get_historico_aluno($matricula, $senha) {
	
		//$matricula = "090012020";
		//$senha = "yoys8875";
	
		$url="https://wwwsec.serverweb.unb.br/matriculaweb/graduacao/sec/login.aspx";
		$url_historico = "https://wwwsec.serverweb.unb.br/matriculaweb/graduacao/sec/he.aspx";
		$cookie="cookie.txt";
	
		$postdata = "inputMatricula=".$matricula."&inputSenha=".$senha;
	
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
		curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt ($ch, CURLOPT_REFERER, $url);
	
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt ($ch, CURLOPT_POST, 1);
		$result = curl_exec ($ch);
	
	
		curl_setopt ($ch, CURLOPT_URL, $url_historico);
		curl_setopt ($ch, CURLOPT_POST, 0);
		$result = curl_exec ($ch);
	
		////echo $result;
		curl_close($ch);
	
	
	
		$dom = new simple_html_dom();
		$dom->load($result);
	
	
	
		//$dom = file_get_html('c:/users/fernando/desktop/mw.html');
	
		$historico = new stdClass();
		$historico->semestres = array();
	
		//echo "---";
		//echo $dom->find("//span[id='alunome']")[0];
		$historico->aluno_nome = $dom->find("//span[id='alunome']")[0];
	
		//echo "---";
		//echo $dom->find("//span[id='curdenominacao']")[0];
		$historico->curso = $dom->find("//span[id='curdenominacao']")[0];
	
	
		//echo "---";
		//echo $dom->find("//span[id='opcdenominacao']")[0];
		$historico->habilitacao = $dom->find("//span[id='opcdenominacao']")[0];
	
		//echo "---";
		//echo $dom->find("//span[id='opcgrau']")[0];
		$historico->grau = $dom->find("//span[id='opcgrau']")[0];
	
	
		//echo "---";
		//echo $dom->find("//span[id='dadperingunb']")[0];
		$historico->ingresso_na_unb = $dom->find("//span[id='dadperingunb']")[0];
	
	
		//echo "---";
		//echo $dom->find("//span[id='dadforingunb']")[0];
		$historico->forma_de_ingresso = $dom->find("//span[id='dadforingunb']")[0];
	
	
	
		//echo "---";
		//echo $dom->find("//span[id='opcnroresolucao']")[0];
		$historico->decreto = $dom->find("//span[id='opcnroresolucao']")[0];
	
		//echo "---";
		//echo $dom->find("//span[id='alumatricula']")[0];
		$historico->matricula = $dom->find("//span[id='alumatricula']")[0];
	
	
		//echo "---";
		//echo $dom->find("//span[id='dataemissao']")[0];
		$historico->data_emissao = $dom->find("//span[id='dataemissao']")[0];
	
		//echo "---";
		//echo $dom->find("//span[id='alupai']")[0];
		$historico->aluno_pai = $dom->find("//span[id='alupai']")[0];
	
		//echo "---";
		//echo $dom->find("//span[id='alumae']")[0];
		$historico->aluno_mae = $dom->find("//span[id='alumae']")[0];
	
		//echo "---";
		//echo $dom->find("//span[id='aludtnasc']")[0];
		$historico->dt_nascimento = $dom->find("//span[id='aludtnasc']")[0];
	
		//echo "---";
		//echo $dom->find("//span[id='alupaisnasc']")[0];
		$historico->pais_nascimento = $dom->find("//span[id='alupaisnasc']")[0];
	
	
		//echo "---";
		//echo $dom->find("//span[id='OpcCredFormat']")[0];
		$historico->cred_exigidos = $dom->find("//span[id='OpcCredFormat']")[0];
	
		//echo "---";
		//echo $dom->find("//span[id='CredCurObtido']")[0];
		$historico->cred_obtidos = $dom->find("//span[id='CredCurObtido']")[0];
	
		//echo "---";
		//echo $dom->find("//span[id='CredCurObter']")[0];
		$historico->cred_obter = $dom->find("//span[id='CredCurObter']")[0];
	
	
	
		$linhas = $dom->find("//tr");
		$iniciou_semestre = false;
		foreach($linhas as $linha) {
			//echo "<div>";
	
			$materias = new stdClass();
			$periodo = "";
			//Significa que é uma linha de materia
			if (count($linha->children) == 9 and $iniciou_semestre == true) {
				//echo $linha->children[0]->innertext;
				$materias->cod = $linha->children[0]->innertext;
				//echo " --- ";
				//echo $linha->children[1]->children[0]->innertext;
				$materias->nome = $linha->children[1]->children[0]->innertext;
				//echo " --- ";
				//echo $linha->children[2]->children[0]->innertext;
				$materias->mencao = $linha->children[2]->children[0]->innertext;
				//echo " --- ";
				//echo $linha->children[3]->innertext;
				$materias->area_con_obr = $linha->children[3]->innertext;
				//echo " --- ";
				//echo $linha->children[4]->innertext;
				$materias->area_con_opt =$linha->children[4]->innertext;
				//echo " --- ";
				//echo $linha->children[5]->children[0]->innertext;
				$materias->dom_con_obr = $linha->children[5]->children[0]->innertext;
				//echo " --- ";
				//echo $linha->children[6]->innertext;
				$materias->dom_con_opt = $linha->children[6]->innertext;
				//echo " --- ";
				//echo $linha->children[7]->innertext;
				$materias->mod_livre = $linha->children[7]->innertext;
				//echo " --- ";
				//echo $linha->children[8]->innertext;
				$materias->outros = $linha->children[8]->innertext;
	
				$semestre = new stdClass();
				$semestre->materias = $materias;
				$semestre->periodo = $periodo;
	
				$historico->semestres[] = $semestre;
			}
	
			//Significa que é uma linha de inicio de semestre
			if (preg_match('/Per.*odo:/', $linha->innertext)) {
				$iniciou_semestre = true;
				//echo $linha->children[0]->children[0]->innertext;
				$periodo = $linha->children[0]->children[0]->innertext;
			}
			//echo "</div>";
		}
	
	
		return $historico;
	}
	
	
}