<?
require_once dirname(__FILE__).'/../../../SEI.php';

class MdWsSeiUnidadeRN extends InfraRN {

    protected function inicializarObjInfraIBanco(){
        return BancoSEI::getInstance();
    }

    /**
     * Pesquisa as unidades pela sigla
     */
    protected function pesquisarUnidadeConectado(UnidadeDTO $unidadeDTOParam){
        try{
            $unidadeRN = new UnidadeRN();
            $unidadeDTO = new UnidadeDTO();
            if($unidadeDTOParam->getNumMaxRegistrosRetorno()){
                $unidadeDTO->setNumMaxRegistrosRetorno($unidadeDTOParam->getNumMaxRegistrosRetorno());
            }else{
                $unidadeDTO->setNumMaxRegistrosRetorno(10);
            }
            if(!is_null($unidadeDTOParam->getNumPaginaAtual())){
                $unidadeDTO->setNumPaginaAtual($unidadeDTOParam->getNumPaginaAtual());
            }else{
                $unidadeDTO->setNumPaginaAtual(0);
            }
            if($unidadeDTOParam->isSetStrSigla()){
                $filter = '%'.$unidadeDTOParam->getStrSigla().'%';
                $unidadeDTO->setStrSigla($filter, InfraDTO::$OPER_LIKE, true);
            }
            $unidadeDTO->setStrSinAtivo('S');
            $unidadeDTO->retNumIdUnidade();
            $unidadeDTO->retStrSigla();
            $unidadeDTO->retStrDescricao();
            $unidadeDTO->setOrdStrSigla(InfraDTO::$TIPO_ORDENACAO_ASC);
            $ret = $unidadeRN->listarRN0127($unidadeDTO);
            $result = array();
            /** @var UnidadeDTO $unDTO */
            foreach($ret as $unDTO){
                $result[] = array(
                    'id' => $unDTO->getNumIdUnidade(),
                    'sigla' => $unDTO->getStrSigla(),
                    'descricao' => $unDTO->getStrDescricao()
                );
            }

            return MdWsSeiRest::formataRetornoSucessoREST(null, $result, $unidadeDTO->getNumTotalRegistros());
        }catch (Exception $e){
            return MdWsSeiRest::formataRetornoErroREST($e);
        }
    }

}