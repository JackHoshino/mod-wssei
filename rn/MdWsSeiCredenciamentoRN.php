<?
require_once dirname(__FILE__).'/../../../SEI.php';

class MdWsSeiCredenciamentoRN extends InfraRN {

    protected function inicializarObjInfraIBanco(){
        return BancoSEI::getInstance();
    }

    /**
     * Concede a credencial de acesso a um processo sigiloso
     * @param ConcederCredencialDTO $concederCredencialDTO
     * @return array
     */
    protected function concederCredenciamentoControlado(ConcederCredencialDTO $concederCredencialDTO){
        try{
            if(!$concederCredencialDTO->isSetDblIdProcedimento() || !$concederCredencialDTO->getDblIdProcedimento()){
                throw new Exception('Processo não informado!');
            }
            if(!$concederCredencialDTO->isSetNumIdUnidade() || !$concederCredencialDTO->getNumIdUnidade()){
                throw new Exception('Unidade não informada!');
            }
            if(!$concederCredencialDTO->isSetNumIdUsuario() || !$concederCredencialDTO->getNumIdUsuario()){
                throw new Exception('Usuário não informado!');
            }

            $atividadeRN = new AtividadeRN();
            $pesquisaPendenciaDTO = new PesquisaPendenciaDTO();
            $pesquisaPendenciaDTO->setDblIdProtocolo($concederCredencialDTO->getDblIdProcedimento());
            $pesquisaPendenciaDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
            $pesquisaPendenciaDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
            $arrProcedimentoDTO = $atividadeRN->listarPendenciasRN0754($pesquisaPendenciaDTO);

            if (count($arrProcedimentoDTO)==0){
                throw new Exception('Processo não encontrado.');
            }
            $arrAtividadesOrigem = $arrProcedimentoDTO[0]->getArrObjAtividadeDTO();
            $concederCredencialDTO->setArrAtividadesOrigem($arrAtividadesOrigem);
            $atividadeRN->concederCredencial($concederCredencialDTO);

            return MdWsSeiRest::formataRetornoSucessoREST("Credencial concedida com sucesso!");
        }catch (Exception $e){
            return MdWsSeiRest::formataRetornoErroREST($e);
        }
    }

    /**
     * Remove a credencial de acesso a um processo sigiloso
     * @param AtividadeDTO $atividadeDTO
     * @return array
     */
    protected function cassarCredencialControlado(AtividadeDTO $atividadeDTO){
        try{
            if(!$atividadeDTO->isSetNumIdAtividade() || !$atividadeDTO->getNumIdAtividade()){
                throw new Exception('Atividade não informado!');
            }

            $objAtividadeRN = new AtividadeRN();
            $objAtividadeRN->cassarCredenciais(array($atividadeDTO));
            return MdWsSeiRest::formataRetornoSucessoREST("Credencial cassada com sucesso!");
        }catch (Exception $e){
            return MdWsSeiRest::formataRetornoErroREST($e);
        }
    }

    /**
     * Lista as credenciais de acesso de um processo sigiloso
     * @param ProcedimentoDTO $procedimentoDTOParam
     * @return array
     */
    protected function listarCredenciaisProcessoConectado(ProcedimentoDTO $procedimentoDTOParam){
        try{
            if(!$procedimentoDTOParam->isSetDblIdProcedimento() || !$procedimentoDTOParam->getDblIdProcedimento()){
                throw new Exception('Atividade não informado!');
            }
            if(is_null($procedimentoDTOParam->getNumPaginaAtual())){
                $procedimentoDTOParam->setNumPaginaAtual(0);
            }
            if (!$procedimentoDTOParam->isSetNumMaxRegistrosRetorno()) {
                $procedimentoDTOParam->setNumMaxRegistrosRetorno(10);
            }

            $result = array();
            $objAtividadeRN = new MdWsSeiAtividadeRN();
            $ret = $objAtividadeRN->listarCredenciais($procedimentoDTOParam);
            /** @var AtividadeDTO $atividadeDTO */
            foreach($ret as $atividadeDTO){
                $dataCassacao = null;
                foreach ($atividadeDTO->getArrObjAtributoAndamentoDTO() as $atributoAndamentoDTO) {
                    if ($atributoAndamentoDTO->getStrNome() == 'DATA_HORA') {
                        $dataCassacao .= substr($atributoAndamentoDTO->getStrValor(), 0, 16);
                    }
                }
                $result[] = array(
                    'atividade' => $atividadeDTO->getNumIdAtividade(),
                    'siglaUsuario' => $atividadeDTO->getStrSiglaUsuario(),
                    'siglaUnidade' => $atividadeDTO->getStrSiglaUnidade(),
                    'nomeUsuario' => $atividadeDTO->getStrNomeUsuario(),
                    'descricaoUnidade' => $atividadeDTO->getStrDescricaoUnidade(),
                    'dataAbertura' => substr($atividadeDTO->getDthAbertura(),0,16),
                    'credencialCassada' => in_array($atividadeDTO->getNumIdTarefa(), TarefaRN::getArrTarefasCassacaoCredencial(false)),
                    'dataCassacao' => $dataCassacao,
                );
            }

            return MdWsSeiRest::formataRetornoSucessoREST(null, $result, $procedimentoDTOParam->getNumTotalRegistros());
        }catch (Exception $e){
            return MdWsSeiRest::formataRetornoErroREST($e);
        }
    }

}