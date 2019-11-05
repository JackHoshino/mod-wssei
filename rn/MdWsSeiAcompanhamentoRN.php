<?
require_once dirname(__FILE__).'/../../../SEI.php';

class MdWsSeiAcompanhamentoRN extends InfraRN {

    protected function inicializarObjInfraIBanco(){
        return BancoSEI::getInstance();
    }

    public function encapsulaAcompanhamento(array $post){
        $acompanhamentoDTO = new AcompanhamentoDTO();

        if (!empty($post['protocolo'])){
            $acompanhamentoDTO->setDblIdProtocolo($post['protocolo']);
        }
        if (!empty($post['unidade'])){
            $acompanhamentoDTO->setNumIdUnidade($post['unidade']);
        }else{
            $acompanhamentoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        }

        if (!empty($post['grupo'])){
            $acompanhamentoDTO->setNumIdGrupoAcompanhamento($post['grupo']);
        }
        if (!empty($post['usuario'])){
            $acompanhamentoDTO->setNumIdUsuarioGerador($post['usuario']);
        }else{
            $acompanhamentoDTO->setNumIdUsuarioGerador(SessaoSEI::getInstance()->getNumIdUsuario());
        }
        if (!empty($post['observacao'])){
            $acompanhamentoDTO->setStrObservacao($post['observacao']);
        }
        $acompanhamentoDTO->setDthGeracao(InfraData::getStrDataHoraAtual());
        $acompanhamentoDTO->setNumTipoVisualizacao(AtividadeRN::$TV_VISUALIZADO);
        $acompanhamentoDTO->setNumIdAcompanhamento(null);

        return $acompanhamentoDTO;

    }

    protected function cadastrarAcompanhamentoControlado(AcompanhamentoDTO $acompanhamentoDTO){
        try{
            if($acompanhamentoDTO->isSetDblIdProtocolo() && $acompanhamentoDTO->isSetNumIdUnidade()){
                $protocoloRN = new ProtocoloRN();
                $protocoloDTO = new ProtocoloDTO();
                
                $protocoloDTO->setDblIdProtocolo($acompanhamentoDTO->getDblIdProtocolo());
                $protocoloDTO->retNumIdUnidadeGeradora();
                /** Consulta o componente SEI para retorno dos dados do protocolo para valida��o **/
                $protocoloDTO = $protocoloRN->consultarRN0186($protocoloDTO);
                if(!$protocoloDTO || $protocoloDTO->getNumIdUnidadeGeradora() != $acompanhamentoDTO->getNumIdUnidade()){
                    throw new Exception('Protocolo n�o encontrado.');
                }
            }
            $acompanhamentoRN = new AcompanhamentoRN();
            $acompanhamentoRN->cadastrar($acompanhamentoDTO);
            return MdWsSeiRest::formataRetornoSucessoREST('Acompanhamento realizado com sucesso!');
        }catch (Exception $e){
            return MdWsSeiRest::formataRetornoErroREST($e);
        }
    }
}