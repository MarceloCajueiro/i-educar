<?php

require_once 'lib/Portabilis/View/Helper/DynamicInput/CoreSelect.php';

/**
 * Portabilis_View_Helper_DynamicInput_EscolaDestinoTransporteEscolar class.
 *
 * @author    Gabriel Matos de Souza <gabriel@portabilis.com.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   Portabilis
 * @since     Classe disponível desde a versão ?
 * @version   @@package_version@@
 */
class Portabilis_View_Helper_DynamicInput_EscolaDestinoTransporteEscolar extends Portabilis_View_Helper_DynamicInput_CoreSelect {

  protected function inputOptions($options) {

    $resources     = $options['resources'];
    return $this->insertOption(null, "Todos", $resources);
  }

  public function escolaDestinoTransporteEscolar($options = array()) {
    parent::select($options);
  }
}