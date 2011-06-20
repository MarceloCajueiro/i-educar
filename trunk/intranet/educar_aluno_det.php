<?php

/**
 * i-Educar - Sistema de gest�o escolar
 *
 * Copyright (C) 2006  Prefeitura Municipal de Itaja�
 *                     <ctima@itajai.sc.gov.br>
 *
 * Este programa � software livre; voc� pode redistribu�-lo e/ou modific�-lo
 * sob os termos da Licen�a P�blica Geral GNU conforme publicada pela Free
 * Software Foundation; tanto a vers�o 2 da Licen�a, como (a seu crit�rio)
 * qualquer vers�o posterior.
 *
 * Este programa � distribu��do na expectativa de que seja �til, por�m, SEM
 * NENHUMA GARANTIA; nem mesmo a garantia impl��cita de COMERCIABILIDADE OU
 * ADEQUA��O A UMA FINALIDADE ESPEC�FICA. Consulte a Licen�a P�blica Geral
 * do GNU para mais detalhes.
 *
 * Voc� deve ter recebido uma c�pia da Licen�a P�blica Geral do GNU junto
 * com este programa; se n�o, escreva para a Free Software Foundation, Inc., no
 * endere�o 59 Temple Street, Suite 330, Boston, MA 02111-1307 USA.
 *
 * @author    Prefeitura Municipal de Itaja� <ctima@itajai.sc.gov.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   iEd_Pmieducar
 * @since     Arquivo dispon�vel desde a vers�o 1.0.0
 * @version   $Id$
 */

require_once 'include/clsBase.inc.php';
require_once 'include/clsDetalhe.inc.php';
require_once 'include/clsBanco.inc.php';
require_once 'include/pmieducar/geral.inc.php';

require_once 'App/Model/ZonaLocalizacao.php';
require_once 'Educacenso/Model/AlunoDataMapper.php';
require_once 'Transporte/Model/AlunoDataMapper.php';
require_once 'Ciasc/Model/CodigoAlunoDataMapper.php';

/**
 * clsIndexBase class.
 *
 * @author    Prefeitura Municipal de Itaja� <ctima@itajai.sc.gov.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   iEd_Pmieducar
 * @since     Classe dispon�vel desde a vers�o 1.0.0
 * @version   @@package_version@@
 */
class clsIndexBase extends clsBase
{
  function Formular()
  {
    $this->SetTitulo($this->_instituicao . ' i-Educar - Aluno');
    $this->processoAp = 578;
  }
}

/**
 * indice class.
 *
 * @author    Prefeitura Municipal de Itaja� <ctima@itajai.sc.gov.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   iEd_Pmieducar
 * @since     Classe dispon�vel desde a vers�o 1.0.0
 * @version   @@package_version@@
 */
class indice extends clsDetalhe
{
  var $titulo;

  var $cod_aluno;
  var $ref_idpes_responsavel;
  var $idpes_pai;
  var $idpes_mae;
  var $ref_cod_pessoa_educ;
  var $ref_cod_aluno_beneficio;
  var $ref_cod_religiao;
  var $ref_usuario_exc;
  var $ref_usuario_cad;
  var $ref_idpes;
  var $data_cadastro;
  var $data_exclusao;
  var $ativo;
  var $nm_pai;
  var $nm_mae;
  var $ref_cod_raca;

  function Gerar()
  {
    @session_start();
    $this->pessoa_logada = $_SESSION['id_pessoa'];
    session_write_close();

    // Verifica��o de permiss�o para cadastro.
    $this->obj_permissao = new clsPermissoes();

    $this->nivel_usuario = $this->obj_permissao->nivel_acesso($this->pessoa_logada);

    $this->titulo = 'Aluno - Detalhe';
    $this->addBanner('imagens/nvp_top_intranet.jpg', 'imagens/nvp_vert_intranet.jpg', 'Intranet');

    $this->cod_aluno = $_GET['cod_aluno'];

    $tmp_obj = new clsPmieducarAluno($this->cod_aluno);
    $registro = $tmp_obj->detalhe();

    if (! $registro) {
      header('Location: educar_aluno_lst.php');
      die();
    }
    else {
      foreach ($registro as $key => $value) {
        $this->$key = $value;
      }
    }

    if ($this->ref_idpes) {
      $obj_pessoa_fj = new clsPessoaFj($this->ref_idpes);
      $det_pessoa_fj = $obj_pessoa_fj->detalhe();

      $obj_fisica = new clsFisica($this->ref_idpes);
      $det_fisica = $obj_fisica->detalhe();

      $obj_fisica_raca = new clsCadastroFisicaRaca();
      $lst_fisica_raca = $obj_fisica_raca->lista( $this->ref_idpes );

      if ($lst_fisica_raca) {
        $det_fisica_raca = array_shift($lst_fisica_raca);

        $obj_raca = new clsCadastroRaca($det_fisica_raca['ref_cod_raca']);
        $det_raca = $obj_raca->detalhe();
      }

      $registro['nome_aluno'] = $det_pessoa_fj['nome'];
      $registro['cpf']        = int2IdFederal($det_fisica['cpf']);
      $registro['data_nasc']  = dataToBrasil($det_fisica['data_nasc']);
      $registro['sexo']       = $det_fisica['sexo'] == 'F' ? 'Feminino' : 'Masculino';

      $obj_estado_civil       = new clsEstadoCivil();
      $obj_estado_civil_lista = $obj_estado_civil->lista();

      $lista_estado_civil = array();

      if ($obj_estado_civil_lista) {
        foreach ($obj_estado_civil_lista as $estado_civil) {
          $lista_estado_civil[$estado_civil['ideciv']] = $estado_civil['descricao'];
        }
      }

      $registro['ideciv'] = $lista_estado_civil[$det_fisica['ideciv']->ideciv];
      $registro['email']  = $det_pessoa_fj['email'];
      $registro['url']    = $det_pessoa_fj['url'];

      $registro['nacionalidade'] = $det_fisica['nacionalidade'];

      $registro['naturalidade']  = $det_fisica['idmun_nascimento']->detalhe();
      $registro['naturalidade']  = $registro['naturalidade']['nome'];

      $registro['pais_origem'] = $det_fisica['idpais_estrangeiro']->detalhe();
      $registro['pais_origem'] = $registro['pais_origem']['nome'];

      $registro['ref_idpes_responsavel'] = $det_fisica['idpes_responsavel'];

      $this->idpes_pai = $det_fisica['idpes_pai'];
      $this->idpes_mae = $det_fisica['idpes_mae'];

      $this->nm_pai = $detalhe_aluno['nm_pai'];
      $this->nm_mae = $detalhe_aluno['nm_mae'];

      if ($this->idpes_pai) {
        $obj_pessoa_pai = new clsPessoaFj($this->idpes_pai);
        $det_pessoa_pai = $obj_pessoa_pai->detalhe();

        if ($det_pessoa_pai) {
          $registro['nm_pai'] = $det_pessoa_pai['nome'];

          // CPF
          $obj_cpf = new clsFisica($this->idpes_pai);
          $det_cpf = $obj_cpf->detalhe();

          if ($det_cpf['cpf']) {
            $this->cpf_pai = int2CPF($det_cpf['cpf']);
          }
        }
      }

      if ($this->idpes_mae) {
        $obj_pessoa_mae = new clsPessoaFj($this->idpes_mae);
        $det_pessoa_mae = $obj_pessoa_mae->detalhe();

        if ($det_pessoa_mae) {
          $registro['nm_mae'] = $det_pessoa_mae['nome'];

          // CPF
          $obj_cpf = new clsFisica($this->idpes_mae);
          $det_cpf = $obj_cpf->detalhe();

          if ($det_cpf['cpf']) {
            $this->cpf_mae = int2CPF($det_cpf['cpf']);
          }
        }
      }

      $registro['ddd_fone_1'] = $det_pessoa_fj['ddd_1'];
      $registro['fone_1']     = $det_pessoa_fj['fone_1'];

      $registro['ddd_fone_2'] = $det_pessoa_fj['ddd_2'];
      $registro['fone_2']     = $det_pessoa_fj['fone_2'];

      $registro['ddd_fax']  = $det_pessoa_fj['ddd_fax'];
      $registro['fone_fax'] = $det_pessoa_fj['fone_fax'];

      $registro['ddd_mov']  = $det_pessoa_fj['ddd_mov'];
      $registro['fone_mov'] = $det_pessoa_fj['fone_mov'];

      $obj_deficiencia_pessoa       = new clsCadastroFisicaDeficiencia();
      $obj_deficiencia_pessoa_lista = $obj_deficiencia_pessoa->lista($this->ref_idpes);

      if ($obj_deficiencia_pessoa_lista) {
        $deficiencia_pessoa = array();

        foreach ($obj_deficiencia_pessoa_lista as $deficiencia) {
          $obj_def = new clsCadastroDeficiencia($deficiencia['ref_cod_deficiencia']);
          $det_def = $obj_def->detalhe();

          $deficiencia_pessoa[$deficiencia['ref_cod_deficiencia']] = $det_def['nm_deficiencia'];
        }
      }

      $ObjDocumento = new clsDocumento($this->ref_idpes);
      $detalheDocumento = $ObjDocumento->detalhe();

      $registro['rg'] = $detalheDocumento['rg'];

      if ($detalheDocumento['data_exp_rg']) {
        $registro['data_exp_rg'] = date('d/m/Y',
          strtotime(substr($detalheDocumento['data_exp_rg'], 0, 19)));
      }

      $registro['sigla_uf_exp_rg'] = $detalheDocumento['sigla_uf_exp_rg'];
      $registro['tipo_cert_civil'] = $detalheDocumento['tipo_cert_civil'];
      $registro['num_termo']       = $detalheDocumento['num_termo'];
      $registro['num_livro']       = $detalheDocumento['num_livro'];
      $registro['num_folha']       = $detalheDocumento['num_folha'];

      if ($detalheDocumento['data_emissao_cert_civil']) {
        $registro['data_emissao_cert_civil'] = date('d/m/Y',
          strtotime(substr($detalheDocumento['data_emissao_cert_civil'], 0, 19)));
      }

      $registro['sigla_uf_cert_civil'] = $detalheDocumento['sigla_uf_cert_civil'];
      $registro['cartorio_cert_civil'] = $detalheDocumento['cartorio_cert_civil'];
      $registro['num_cart_trabalho']   = $detalheDocumento['num_cart_trabalho'];
      $registro['serie_cart_trabalho'] = $detalheDocumento['serie_cart_trabalho'];

      if ($detalheDocumento['data_emissao_cart_trabalho']) {
        $registro['data_emissao_cart_trabalho'] = date('d/m/Y',
          strtotime(substr($detalheDocumento['data_emissao_cart_trabalho'], 0, 19)));
      }

      $registro['sigla_uf_cart_trabalho'] = $detalheDocumento['sigla_uf_cart_trabalho'];
      $registro['num_tit_eleitor']        = $detalheDocumento['num_titulo_eleitor'];
      $registro['zona_tit_eleitor']       = $detalheDocumento['zona_titulo_eleitor'];
      $registro['secao_tit_eleitor']      = $detalheDocumento['secao_titulo_eleitor'];
      $registro['idorg_exp_rg']           = $detalheDocumento['ref_idorg_rg'];

      $obj_endereco = new clsPessoaEndereco($this->ref_idpes);

      if ($obj_endereco_det = $obj_endereco->detalhe()) {
        $registro['id_cep']        = $obj_endereco_det['cep']->cep;
        $registro['id_bairro']     = $obj_endereco_det['idbai']->idbai;
        $registro['id_logradouro'] = $obj_endereco_det['idlog']->idlog;
        $registro['numero']        = $obj_endereco_det['numero'];
        $registro['letra']         = $obj_endereco_det['letra'];
        $registro['complemento']   = $obj_endereco_det['complemento'];
        $registro['andar']         = $obj_endereco_det['andar'];
        $registro['apartamento']   = $obj_endereco_det['apartamento'];
        $registro['bloco']         = $obj_endereco_det['bloco'];
        $registro['nm_logradouro'] = $obj_endereco_det['logradouro'];
        $registro['cep_']          = int2CEP($registro['id_cep']);

        $obj_bairro     = new clsBairro($registro['id_bairro']);
        $obj_bairro_det = $obj_bairro->detalhe();

        if ($obj_bairro_det) {
          $registro['nm_bairro']= $obj_bairro_det['nome'];
        }

        $obj_log = new clsLogradouro($registro['id_logradouro']);
        $obj_log_det = $obj_log->detalhe();

        if ($obj_log_det) {
          $registro['nm_logradouro'] = $obj_log_det['nome'];
          $registro['idtlog']        = $obj_log_det['idtlog']->detalhe();
          $registro['idtlog']        = $registro['idtlog']['descricao'];

          $obj_mun = new clsMunicipio($obj_log_det['idmun']);
          $det_mun = $obj_mun->detalhe();

          if ($det_mun) {
            $registro['cidade'] = ucfirst(strtolower($det_mun['nome']));
          }
        }

        $obj_bairro = new clsBairro($registro["id_bairro"]);
        $obj_bairro_det = $obj_bairro->detalhe();

        if ($obj_bairro_det) {
          $registro['nm_bairro'] = $obj_bairro_det['nome'];
        }
      }
      else {
        $obj_endereco = new clsEnderecoExterno($this->ref_idpes);

        if ($obj_endereco_det = $obj_endereco->detalhe()) {
          $registro['id_cep']        = $obj_endereco_det['cep'];
          $registro['cidade']        = $obj_endereco_det['cidade'];
          $registro['nm_bairro']     = $obj_endereco_det['bairro'];
          $registro['nm_logradouro'] = $obj_endereco_det['logradouro'];
          $registro['numero']        = $obj_endereco_det['numero'];
          $registro['letra']         = $obj_endereco_det['letra'];
          $registro['complemento']   = $obj_endereco_det['complemento'];
          $registro['andar']         = $obj_endereco_det['andar'];
          $registro['apartamento']   = $obj_endereco_det['apartamento'];
          $registro['bloco']         = $obj_endereco_det['bloco'];
          $registro['idtlog']        = $obj_endereco_det['idtlog']->detalhe();
          $registro['idtlog']        = $registro['idtlog']['descricao'];

          $det_uf = $obj_endereco_det['sigla_uf']->detalhe();
          $registro['ref_sigla_uf'] = $det_uf['nome'];

          $registro['cep_'] = int2CEP($registro['id_cep']);
        }
      }
    }

    // Adiciona a informa��o de zona de localiza��o junto ao bairro do
    // endere�o.
    $zona = App_Model_ZonaLocalizacao::getInstance();
    $registro['nm_bairro'] = sprintf(
      '%s (Zona %s)',
      $registro['nm_bairro'], $zona->getValue($obj_endereco_det['zona_localizacao'])
    );

    if ($registro['cod_aluno']) {
      $this->addDetalhe(array('C�digo Aluno', $registro['cod_aluno']));
    }

    if ($registro['caminho_foto']) {
      $this->addDetalhe(array(
        'Foto',
        sprintf(
          '<img src="arquivos/educar/aluno/small/%s" border="0">',
          $registro['caminho_foto']
        )
      ));
    }

    if ($registro['nome_aluno']) {
      $this->addDetalhe(array('Nome Aluno', $registro['nome_aluno']));
    }

    if (idFederal2int($registro['cpf'])) {
      $this->addDetalhe(array('CPF', $registro['cpf']));
    }

    if ($registro['data_nasc']) {
      $this->addDetalhe(array('Data de Nascimento', $registro['data_nasc']));
    }

    /**
     * Analfabeto.
     */
    $this->addDetalhe(array('Analfabeto', $registro['analfabeto'] == 0 ? 'N�o' : 'Sim'));

    if ($registro['sexo']) {
      $this->addDetalhe(array('Sexo', $registro['sexo']));
    }

    if ($registro['ideciv']) {
      $this->addDetalhe(array('Estado Civil', $registro['ideciv']));
    }

    if ($registro['id_cep']) {
      $this->addDetalhe(array('CEP', $registro['cep_']));
    }

    if ($registro['ref_sigla_uf']) {
      $this->addDetalhe(array('UF', $registro['ref_sigla_uf']));
    }

    if ($registro['cidade']) {
      $this->addDetalhe(array('Cidade', $registro['cidade']));
    }

    if ($registro['nm_bairro']) {
      $this->addDetalhe(array('Bairro', $registro['nm_bairro']));
    }

    if ($registro['nm_logradouro']) {
      $logradouro = '';

      if ($registro['idtlog']) {
        $logradouro .= $registro['idtlog'] . ' ';
      }

      $logradouro .= $registro['nm_logradouro'];
      $this->addDetalhe(array('Logradouro', $logradouro));
    }

    if ($registro['numero']) {
      $this->addDetalhe(array('N�mero', $registro['numero']));
    }

    if ($registro['letra']) {
      $this->addDetalhe(array('Letra', $registro['letra']));
    }

    if ($registro['complemento']) {
      $this->addDetalhe(array('Complemento', $registro['complemento']));
    }

    if ($registro['bloco']) {
      $this->addDetalhe(array('Bloco', $registro['bloco']));
    }

    if ($registro['andar']) {
      $this->addDetalhe(array('Andar', $registro['andar']));
    }

    if ($registro['apartamento']) {
      $this->addDetalhe(array('Apartamento', $registro['apartamento']));
    }

    if ($registro['naturalidade']) {
      $this->addDetalhe(array('Naturalidade', $registro['naturalidade']));
    }

    if ($registro['nacionalidade']) {
      $lista_nacionalidade = array(
        'NULL' => 'Selecione',
        1      => 'Brasileiro',
        2      => 'Naturalizado Brasileiro',
        3      => 'Estrangeiro'
      );

      $registro['nacionalidade'] = $lista_nacionalidade[$registro['nacionalidade']];
      $this->addDetalhe(array('Nacionalidade', $registro['nacionalidade']));
    }

    if ($registro['pais_origem']) {
      $this->addDetalhe(array('Pa�s de Origem', $registro['pais_origem']));
    }

    $responsavel = $tmp_obj->getResponsavelAluno();

    if ($responsavel) {
      $this->addDetalhe(array('Respons�vel Aluno', $responsavel['nome_responsavel']));
    }

    if ($registro['ref_idpes_responsavel']) {
      $obj_pessoa_resp = new clsPessoaFj($registro['ref_idpes_responsavel']);
      $det_pessoa_resp = $obj_pessoa_resp->detalhe();

      if ($det_pessoa_resp) {
        $registro['ref_idpes_responsavel'] = $det_pessoa_resp['nome'];
      }

      $this->addDetalhe(array('Respons�vel', $registro['ref_idpes_responsavel']));
    }

    if ($registro['nm_pai']) {
      $this->addDetalhe(array('Pai', $registro['nm_pai']));
    }

    if ($registro["nm_mae"]) {
      $this->addDetalhe(array('M�e', $registro['nm_mae']));
    }

    if ($registro['fone_1']) {
      if ($registro['ddd_fone_1']) {
        $registro['ddd_fone_1'] = sprintf('(%s)&nbsp;', $registro['ddd_fone_1']);
      }

      $this->addDetalhe(array('Telefone 1', $registro['ddd_fone_1'] . $registro['fone_1']));
    }

    if ($registro['fone_2']) {
      if ($registro['ddd_fone_2']) {
        $registro['ddd_fone_2'] = sprintf('(%s)&nbsp;', $registro['ddd_fone_2']);
      }

      $this->addDetalhe(array('Telefone 2', $registro['ddd_fone_2'] . $registro['fone_2']));
    }

    if ($registro['fone_mov']) {
      if ($registro['ddd_mov']) {
        $registro['ddd_mov'] = sprintf('(%s)&nbsp;', $registro['ddd_mov']);
      }

      $this->addDetalhe(array('Celular', $registro['ddd_mov'] . $registro['fone_mov']));
    }

    if ($registro['fone_fax']) {
      if($registro['ddd_fax']) {
        $registro['ddd_fax'] = sprintf('(%s)&nbsp;', $registro['ddd_fax']);
      }

      $this->addDetalhe(array('Fax', $registro['ddd_fax'] . $registro['fone_fax']));
    }

    if ($registro['email']) {
      $this->addDetalhe(array('E-mail', $registro['email']));
    }

    if ($registro['url']) {
      $this->addDetalhe(array('P�gina Pessoal', $registro['url']));
    }

    if ($registro['ref_cod_aluno_beneficio']) {
      $obj_beneficio     = new clsPmieducarAlunoBeneficio($registro['ref_cod_aluno_beneficio']);
      $obj_beneficio_det = $obj_beneficio->detalhe();

      $this->addDetalhe(array('Benef�cio', $obj_beneficio_det['nm_beneficio']));
    }

    if ($registro['ref_cod_religiao']) {
      $obj_religiao     = new clsPmieducarReligiao($registro['ref_cod_religiao']);
      $obj_religiao_det = $obj_religiao->detalhe();

      $this->addDetalhe(array('Religi�o', $obj_religiao_det['nm_religiao']));
    }

    if ($det_raca['nm_raca']) {
      $this->addDetalhe(array('Ra�a', $det_raca['nm_raca']));
    }

    if ($deficiencia_pessoa) {
      $tabela = '<table border="0" width="300" cellpadding="3"><tr bgcolor="#A1B3BD" align="center"><td>Defici�ncias</td></tr>';
      $cor    = '#D1DADF';

      foreach ($deficiencia_pessoa as $indice => $valor) {
        $cor = $cor == '#D1DADF' ? '#E4E9ED' : '#D1DADF';

        $tabela .= sprintf('<tr bgcolor="%s" align="center"><td>%s</td></tr>',
          $cor, $valor);
      }

      $tabela .= '</table>';

      $this->addDetalhe(array('Defici�ncias', $tabela));
    }

    if ($registro['rg']) {
      $this->addDetalhe(array('RG', $registro['rg']));
    }

    if ($registro['data_exp_rg']) {
      $this->addDetalhe(array('Data de Expedi��o RG', $registro['data_exp_rg']));
    }

    if ($registro['idorg_exp_rg']) {
      $this->addDetalhe(array('�rg�o Expedi��o RG', $registro['idorg_exp_rg']));
    }

    if ($registro['sigla_uf_exp_rg']) {
      $this->addDetalhe(array('Estado Expedidor', $registro['sigla_uf_exp_rg']));
    }

    /**
     * @todo CoreExt_Enum?
     */
    if ($registro['tipo_cert_civil']) {
      $lista_tipo_cert_civil       = array();
      $lista_tipo_cert_civil["0"] = 'Selecione';
      $lista_tipo_cert_civil[91]  = 'Nascimento';
      $lista_tipo_cert_civil[92]  = 'Casamento';

      $this->addDetalhe(array('Tipo Certificado Civil', $registro['tipo_cert_civil']));
    }

    if ($registro['num_termo']) {
      $this->addDetalhe(array('Termo', $registro['num_termo']));
    }

    if ($registro['num_livro']) {
      $this->addDetalhe(array('Livro', $registro['num_livro']));
    }

    if ($registro['num_folha']) {
      $this->addDetalhe(array('Folha', $registro['num_folha']));
    }

    if ($registro['data_emissao_cert_civil']) {
      $this->addDetalhe(array('Emiss�o Certid�o Civil', $registro['data_emissao_cert_civil']));
    }

    if ($registro['sigla_uf_cert_civil']) {
      $this->addDetalhe(array('Sigla Certid�o Civil', $registro['sigla_uf_cert_civil']));
    }

    if ($registro['cartorio_cert_civil']) {
      $this->addDetalhe(array('Cart�rio', $registro['cartorio_cert_civil']));
    }

    if ($registro['num_tit_eleitor']) {
      $this->addDetalhe(array('T�tulo de Eleitor', $registro['num_tit_eleitor']));
    }

    if ($registro['zona_tit_eleitor']) {
      $this->addDetalhe(array('Zona', $registro['zona_tit_eleitor']));
    }

    if ($registro['secao_tit_eleitor']) {
      $this->addDetalhe(array('Se��o', $registro['secao_tit_eleitor']));
    }

    // Transporte escolar.
    $transporteMapper = new Transporte_Model_AlunoDataMapper();
    $transporteAluno  = NULL;
    try {
      $transporteAluno = $transporteMapper->find(array('aluno' => $this->cod_aluno));
    }
    catch (Exception $e) {
    }

    $this->addDetalhe(array('Transporte escolar', isset($transporteAluno) ? 'Sim' : 'N�o'));
    if ($transporteAluno) {
      $this->addDetalhe(array('Respons�vel transporte', $transporteAluno->responsavel));
    }

    // Adiciona uma aba com dados do Inep/Educacenso caso aluno tenha c�digo Inep.
    if (isset($this->cod_aluno)) {
      $alunoMapper = new Educacenso_Model_AlunoDataMapper();

      $alunoInep = NULL;
      try {
        $alunoInep = $alunoMapper->find(array('aluno' => $this->cod_aluno));
      }
      catch(Exception $e) {
      }

      if ($alunoInep) {
        $this->addDetalhe(array('C�digo do aluno no Educacenso/Inep', $alunoInep->alunoInep));

        if (isset($alunoInep->nomeInep)) {
          $this->addDetalhe(array('Nome do aluno no Educacenso/Inep', $alunoInep->nomeInep));
        }
      }
    }

    //informa��o seriesciasc
    $SerieciascMapper = new Ciasc_Model_CodigoAlunoDataMapper();

    try {
        $ciasc = $SerieciascMapper->find(array('cod_aluno' => $this->cod_aluno));
    }
    catch(Exception $e) {
    }

    if (!empty($ciasc)){
        $this->addDetalhe(array('Matr�cula S�rie/CIASC', $ciasc->cod_ciasc));
    }

    $this->addDetalhe($this->montaTabelaMatricula());
    //$this->addDetalhe(array('Matr�cula', $this->montaTabelaMatricula()));

    if ($this->obj_permissao->permissao_cadastra(578, $this->pessoa_logada, 7)) {
      $this->url_novo   = 'educar_aluno_cad.php';
      $this->url_editar = 'educar_aluno_cad.php?cod_aluno=' . $registro['cod_aluno'];

      $this->array_botao = array('Matr�cula', 'Atualizar Hist�rico', 'Ficha do Aluno');
      $this->array_botao_url_script = array(
        sprintf('go("educar_matricula_lst.php?ref_cod_aluno=%d");', $registro['cod_aluno']),
        sprintf('go("educar_historico_escolar_lst.php?ref_cod_aluno=%d");', $registro['cod_aluno']),
        sprintf('showExpansivelImprimir(400, 200, "educar_relatorio_aluno_dados.php?ref_cod_aluno=%d", [], "Relat�rio i-Educar")', $registro['cod_aluno'])
      );
    }

    $this->url_cancelar = 'educar_aluno_lst.php';
    $this->largura      = '100%';
  }


  function montaTabelaMatricula()
  {
    $db = new clsBanco();

    require_once 'include/portabilis/ml.php';
    $div = new DIV(new P('<strong>Matriculas:</strong>'));    

    $matriculas = new clsPmieducarMatricula();#null, null, null, null, null, null, $this->cod_aluno, null, null, null, 1);
    $matriculas->setOrderby('ano DESC, ref_ref_cod_serie DESC, aprovado, cod_matricula');
    $matriculas = $matriculas->lista(null, null, null, null, null, null, $this->cod_aluno, null, null, null, null, null, 1);

    if ($matriculas)
    {
      $tr = new TR(new TH('Ano', array('class' => 'center')), new TH('Situa��o'), new TH('Turma'), new TH('S�rie'), new TH('Curso'), new TH('Escola'));

      if ($this->nivel_usuario == 1)
        $tr->append(new TH('Institui��o'));    

      $tr->append(new TH('Formando', array('class' => 'center')));
      $tr->append(new TH('Entrada', array('class' => 'center')));
      $tr->append(new TH('Sa�da', array('class' => 'center')));

      $t = new HtmlTable($tr, array('class'=>'horizontal-expand styled small strong'));

      $classRow = '';
      $possuiSolTransfEmAberto = false;
      foreach($matriculas as $m)
      {
        
        $turma = new clsPmieducarMatriculaTurma();
        $turma = $turma->lista($m['cod_matricula'], NULL, NULL,
          NULL, NULL, NULL, NULL, NULL, 1);
        if ($turma)
        {
          $turma = array_shift($turma);

          $turma = new clsPmieducarTurma($turma['ref_cod_turma']);
          $turma = $turma->detalhe();
          $turma  = $turma['nm_turma'];
        }
        else
          $turma = '';

        $serie = new clsPmieducarSerie($m['ref_ref_cod_serie']);
        $serie = $serie->detalhe();
        $serie = $serie['nm_serie'];

        $situacao = $m['aprovado'];
        if ($situacao == 1)
          $situacao = 'Aprovado';
        elseif ($situacao == 2)
          $situacao = 'Reprovado';
        elseif ($situacao == 3)
        {
          $situacao = 'Em Andamento';

          if ($db->UnicoCampo("select count(cod_transferencia_solicitacao) from pmieducar.transferencia_solicitacao where ativo = 1 and ref_cod_matricula_saida = {$m['cod_matricula']} and ref_cod_matricula_entrada is null and data_transferencia is null") > 0)
          {
            #$situacao = '* ' . $situacao;    
            $situacao .=  ' *';   
            $possuiSolTransfEmAberto = true;
          }
        }
        elseif ($situacao == 4)
          $situacao = 'Transferido';

        $curso = new clsPmieducarCurso($m['ref_cod_curso']);
        $curso = $curso->detalhe();
        $curso = $curso['nm_curso'];

        $instituicao = new clsPmieducarInstituicao($m['ref_cod_instituicao']);
        $instituicao = $instituicao->detalhe();
        $instituicao = $instituicao['nm_instituicao'];

        $escola = new clsPmieducarEscola($m['ref_ref_cod_escola']);
        $escola = $escola->detalhe();
        $escola = $escola['nome'];

        $sql = sprintf('SELECT
                  ref_cod_matricula_entrada,
                  ref_cod_matricula_saida,
                  to_char(data_transferencia, \'DD/MM/YYYY\') AS dt_transferencia
                FROM
                  pmieducar.transferencia_solicitacao
                WHERE
                  (ref_cod_matricula_entrada = %d
                  OR ref_cod_matricula_saida = %d)
                  AND ativo = 1',
                $m['cod_matricula'], $m['cod_matricula']
        );

        $db->Consulta($sql);

        while ($db->ProximoRegistro())
        {
          list($ref_cod_matricula_entrada, $ref_cod_matricula_saida, $dTrans) = $db->Tupla();

          if ($ref_cod_matricula_saida == $m['cod_matricula'])
          {
              $dTransSaida = $dTrans;
              $dTransEntrada = '';
          }
          elseif ($ref_cod_matricula_entrada == $m['cod_matricula'])
          {
            $dTransEntrada = $dTrans;
            $dTransSaida = '';
          }
        }
        $formando = $m['formando'] ? 'Sim' : '';

        $instEsc = $this->obj_permissao->getInstituicaoEscola($this->pessoa_logada);      
        if ($this->nivel_usuario == 1 || ($m['ref_cod_instituicao'] == $instEsc['instituicao'] && $m['ref_ref_cod_escola'] == $instEsc['escola']))
          $href = 'educar_matricula_det.php?cod_matricula='.$m['cod_matricula'];
        else
          $href = '';

        if ($href)
        {
          $tr = new TR(new TD(new A($m['ano'], array('href' => $href, 'class' => 'decorated')), array('class' => 'center')), 
                        new TD(new A($situacao, array('href' => $href))), 
                        new TD(new A($turma, array('href' => $href))),                         
                        new TD(new A($serie, array('href' => $href))), 
                        new TD(new A($curso, array('href' => $href))), 
                        new TD(new A($escola, array('href' => $href))), 
                        array('class' => $classRow));

          if ($this->nivel_usuario == 1)
            $tr->append(new TD(new A($instituicao, array('href' => $href))));

          $tr->append(new TD(new A($formando, array('href' => $href)), array('class' => 'center')));
          $tr->append(new TD(new A($dTransEntrada, array('href' => $href)), array('class' => 'center')));
          $tr->append(new TD(new A($dTransSaida, array('href' => $href)), array('class' => 'center')));
        }
        else
        {
          $tr = new TR(new TD($m['ano'], array('class' => 'center')), 
                        new TD($situacao), 
                        new TD($turma),                         
                        new TD($serie), 
                        new TD($curso), 
                        new TD($escola), 
                        array('class' => $classRow)); 

          if ($this->nivel_usuario == 1)
            $tr->append(new TD($instituicao));

          $tr->append(new TD($formando, array('class' => 'center')));
          $tr->append(new TD($dTransEntrada, array('class' => 'center')));
          $tr->append(new TD($dTransSaida, array('class' => 'center')));
        }

        $t->append($tr);

        if ($classRow)
          $classRow = '';
        else
          $classRow = 'cellcolor';
  /*
        $t = new HtmlTable(                
                   new TR(new TH('Ano / Matr�cula'), new TD($m['ano'], ' / ', $m['cod_matricula'], $link), array('class' => 'strong')), 
                   new TR(new TH('Matr�cula'), new TD()), 
                   new TR(new TH('Situa��o'), new TD($situacao), array('class' => 'cellcolor')), 
                   new TR(new TH('Turma'), new TD($turma)),                         
                   new TR(new TH('S�rie'), new TD($serie), array('class' => 'cellcolor')), 
                   new TR(new TH('Curso'), new TD($curso)), 
                   new TR(new TH('Escola'), new TD($escola), array('class' => 'cellcolor')), array('class'=>'styled nocellcolor'));

        if ($this->nivel_usuario == 1)
          $t->append(new TR(new TH('Institui��o'), new TD($instituicao), array('class' => 'cellcolor')));

        if ($formando)
          $t->append(new TR(new TH('Formando'), new TD($formando)));

        if ($dTransEntrada)
          $t->append(new TR(new TH('Data transferencia admiss�o'), new TD($dTransEntrada), array('class' => 'cellcolor')));

        if ($dTransSaida)
          $t->append(new TR(new TH('Data transferencia sa�da'), new TD($dTransSaida)));
  */
      }
      $div->append($t);

      if ($possuiSolTransfEmAberto)
        $div->append(new P('* Matr�cula com solicita��o de transfer�ncia interna em aberto. ', new A('matricular aluno', array('class' => 'decorated', 'href' => "educar_matricula_cad.php?ref_cod_aluno={$_GET['cod_aluno']}"))));
    }
    else
      $div->append(new P('<strong>Este aluno n�o possui matr�culas. </strong>', new A('matr�cular aluno', array('class' => 'decorated', 'href' => "educar_matricula_cad.php?ref_cod_aluno={$_GET['cod_aluno']}"))));

    return $div->render();
  }  

/*  function montaTabelaMatricula()
  {
    $sql = sprintf('SELECT
              cod_matricula
            FROM
              pmieducar.matricula
            WHERE
              ref_cod_aluno = %d
              AND ativo = 1
            ORDER BY
              cod_matricula DESC', $this->cod_aluno);

    $db = new clsBanco();
    $db->Consulta($sql);

    if ($db->Num_Linhas()) {
      while ($db->ProximoRegistro()) {
        list($ref_cod_matricula) = $db->Tupla();

        if (is_numeric($ref_cod_matricula)) {
          $obj_matricula = new clsPmieducarMatricula();
          $obj_matricula->setOrderby('ano ASC');
          $lst_matricula = $obj_matricula->lista($ref_cod_matricula);

          if ($lst_matricula) {
            $registro = array_shift($lst_matricula);
          }

          $table .= sprintf(
            '<table class="tableDetalhe">
               <tr class="formdktd">
                 <td colspan="2"><strong>Matr�cula - Ano %d</strong></td>
               </tr>',
            $registro['ano']
          );

          $obj_ref_cod_curso = new clsPmieducarCurso($registro['ref_cod_curso']);
          $det_ref_cod_curso = $obj_ref_cod_curso->detalhe();
          $nm_curso = $det_ref_cod_curso['nm_curso'];

          $obj_serie = new clsPmieducarSerie($registro['ref_ref_cod_serie']);
          $det_serie = $obj_serie->detalhe();
          $nm_serie = $det_serie['nm_serie'];

          $obj_cod_instituicao = new clsPmieducarInstituicao($registro['ref_cod_instituicao']);
          $obj_cod_instituicao_det = $obj_cod_instituicao->detalhe();
          $nm_instituicao = $obj_cod_instituicao_det['nm_instituicao'];

          $obj_ref_cod_escola = new clsPmieducarEscola($registro['ref_ref_cod_escola']);
          $det_ref_cod_escola = $obj_ref_cod_escola->detalhe();
          $nm_escola = $det_ref_cod_escola['nome'];

          $obj_mat_turma = new clsPmieducarMatriculaTurma();
          $det_mat_turma = $obj_mat_turma->lista($ref_cod_matricula, NULL, NULL,
            NULL, NULL, NULL, NULL, NULL, 1);

          if ($det_mat_turma) {
            $det_mat_turma = array_shift($det_mat_turma);

            $obj_turma = new clsPmieducarTurma($det_mat_turma['ref_cod_turma']);
            $det_turma = $obj_turma->detalhe();
            $nm_turma  = $det_turma['nm_turma'];
          }
          else {
            $nm_turma = '';
          }

          $transferencias = array();

          if ($registro['aprovado'] == 1) {
            $aprovado = 'Aprovado';
          }
          elseif ($registro['aprovado'] == 2) {
            $aprovado = 'Reprovado';
          }
          elseif ($registro['aprovado'] == 3) {
            $aprovado = 'Em Andamento';
          }
          elseif ($registro['aprovado'] == 4) {
            if (is_numeric($registro['cod_matricula'])) 
            {
              $aprovado = 'Transferido';

              $sql = sprintf('SELECT
                        ref_cod_matricula_entrada,
                        ref_cod_matricula_saida,
                        to_char(data_transferencia, \'DD/MM/YYYY\') AS dt_transferencia
                      FROM
                        pmieducar.transferencia_solicitacao
                      WHERE
                        (ref_cod_matricula_entrada = %d
                        OR ref_cod_matricula_saida = %d)
                        AND ativo = 1',
                      $registro['cod_matricula'], $registro['cod_matricula']
              );

              $db2 = new clsBanco();
              $db2->Consulta($sql);

              if ($db2->Num_Linhas()) {
                while ($db2->ProximoRegistro()) {
                  list($ref_cod_matricula_entrada, $ref_cod_matricula_saida,
                    $dt_transferencia) = $db2->Tupla();

                  if ($ref_cod_matricula_saida == $registro['cod_matricula']) {
                    $transferencias[] = array(
                      'data_trans' => $dt_transferencia,
                      'desc'       => 'Data Transfer�ncia Sa�da'
                    );
                  }
                  elseif ($ref_cod_matricula_entrada == $registro['cod_matricula']) {
                    $transferencias[] = array(
                      'data_trans' => $dt_transferencia,
                      'desc'       => 'Data Transfer�ncia Admiss�o'
                    );
                  }
                }
              }
            }
          }
          elseif ($registro['aprovado'] == 5) {
            $aprovado = 'Reclassificado';
          }
          elseif ($registro['aprovado'] == 6) {
            $aprovado = 'Abandono';
          }
          elseif ($registro['aprovado'] == 7) {
            $aprovado = 'Em Exame';
          }

          $formando = $registro['formando'] == 0 ? 'N�o' : 'Sim';

          $table .= sprintf(
            '<tr class="formlttd"><td>N�mero da Matr�cula</td><td>%s</td></tr>',
            $registro['cod_matricula']
          );

          $table .= sprintf(
            '<tr class="formmdtd"><td>Institui��o</td><td>%s</td></tr>',
            $nm_instituicao
          );

          $table .= sprintf(
            '<tr class="formlttd"><td>Curso</td><td>%s</td></tr>',
            $nm_curso
          );

          $table .= sprintf(
            '<tr class="formlttd"><td>Escola</td><td>%s</td></tr>',
            $nm_escola
          );

          $table .= sprintf(
            '<tr class="formmdtd"><td>S�rie</td><td>%s</td></tr>',
            $nm_serie
          );

          $table .= sprintf(
            '<tr class="formlttd"><td>Turma</td><td>%s</td></tr>',
            $nm_turma
          );

          $table .= sprintf(
            '<tr class="formmdtd"><td>Situa��o</td><td>%s</td></tr>',
            $aprovado
          );

          $class = 'formmdtd';

          if (is_array($transferencias)) {
            asort($transferencias);

            foreach ($transferencias as $trans) {
              $table .= sprintf(
                '<tr class="%s"><td>%s</td><td>%s</td></tr>',
                $class, $trans['desc'], $trans['data_trans']
              );

              $class = $class == 'formmdtd' ? 'formlttd' : 'formmdtd';
            }
          }

          if ($registro['aprovado'] < 4) {
            if (is_numeric($registro["cod_matricula"])) {
              $sql = sprintf('SELECT
                        to_char(data_transferencia, \'DD/MM/YYYY\')
                      FROM
                        pmieducar.transferencia_solicitacao
                      WHERE
                        ref_cod_matricula_entrada = %d
                        AND ativo = 1', $registro['cod_matricula']);

              $db2 = new clsBanco();
              $data_transferencia = $db2->CampoUnico($sql);

              if ($data_transferencia) {
                $table .= sprintf('
                  <tr class="%s">
                    <td>Data Transfer�ncia Admiss�o</td>
                    <td>%s</td>
                  </tr>',
                  $class, $data_transferencia);

                $class = $class == 'formmdtd' ? 'formlttd' : 'formmdtd';
              }
            }
          }

          $table .= sprintf('<tr class="%s"><td>Formando</td><td>%s</td></tr>',
            $class == 'formmdtd' ? 'formlttd' : 'formmdtd', $formando);

          $table .= '</table>';
        }
      }
    }
    else {
      return '<strong>O aluno n�o est� matriculado em nenhuma escola</strong>';
    }

    return $table;
  }*/
}

// Instancia o objeto da p�gina
$pagina = new clsIndexBase();

// Instancia o objeto de conte�do
$miolo = new indice();

// Passa o conte�do para a p�gina
$pagina->addForm($miolo);

// Gera o HTML
$pagina->MakeAll();
