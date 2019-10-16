

<?
require_once dirname(__FILE__) . '/../../../SEI.php';

class MdWsSeiAgendamentoRN extends InfraRN
{

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    protected function notificacaoAtividadesControlado()
    {
        try {
            ini_set('max_execution_time', '0');
            ini_set('memory_limit', '1024M');
            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();
            $bolErro = false;

            $numSeg = InfraUtil::verificarTempoProcessamento();

            InfraDebug::getInstance()->gravar('REALIZANDO NOTIFICA��O DE ATIVIDADES');

            $notificacaoAtividadeRN = new MdWsSeiNotificacaoAtividadeRN();
            /** Realiza a chamada para retorno de atividades a notificar */
            $arrAtividadeDTO = $notificacaoAtividadeRN->listarNotificacoesParaAgendamento();
            if($arrAtividadeDTO){
                InfraDebug::getInstance()->gravar(count($arrAtividadeDTO).' ATIVIDADES A SEREM NOTIFICADAS.');
                $UrlServicoNotificacao = ConfiguracaoSEI::getInstance()->getValor('WSSEI', 'UrlServicoNotificacao', false);
                $IdApp = ConfiguracaoSEI::getInstance()->getValor('WSSEI', 'IdApp', false);
                $ChaveAutorizacao = ConfiguracaoSEI::getInstance()->getValor('WSSEI', 'ChaveAutorizacao', false);
                if(!$UrlServicoNotificacao || !$IdApp || !$ChaveAutorizacao){
                    $arrParamNaoSetados = array();
                    if(!$UrlServicoNotificacao){
                        $arrParamNaoSetados[] = 'WSSEI:UrlServicoNotificacao';
                    }
                    if(!$IdApp){
                        $arrParamNaoSetados[] = 'WSSEI:IdApp';
                    }
                    if(!$ChaveAutorizacao){
                        $arrParamNaoSetados[] = 'WSSEI:ChaveAutorizacao';
                    }
                    InfraDebug::getInstance()->gravar('AS NOTIFICA��ES N�O SER�O ENVIADAS PORQUE OS PARAMETROS A SEGUIR: '.implode(', ', $arrParamNaoSetados).'.');
                }else{
                    $titulo = 'Atribui��o de Processo';
                    /** @var MdWsSeiAtividadeDTO $atividadeDTO */
                    foreach($arrAtividadeDTO as $atividadeDTO) {
                        SessaoSEI::getInstance(false)->simularLogin(SessaoSEI::$USUARIO_SEI, null, null, $atividadeDTO->getNumIdUnidade());
                        try{
                            $notificacaoDTO = new MdWsSeiNotificacaoDTO();
                            $notificacaoDTO->setStrTitulo('Atribui��o de Processo');
                            $mensagem = 'Ol� '.$atividadeDTO->getStrNomeUsuarioAtribuicao().', ';
                            $mensagem .= 'O processo '.$atividadeDTO->getStrProtocoloFormatadoProtocolo().' foi atribu�do a voc�. ';
                            $mensagem .= 'Clique na notifica��o para abrir o processo !!!';
                            $notificacaoDTO->setStrMensagem($mensagem);
                            $notificacaoDTO->setStrResumo($mensagem);
                            /** Gera identificador �nico do usu�rio para notifica��o **/
                            $identificador = MdWsSeiRest::geraIdentificadorUsuario(
                                $atividadeDTO->getStrSiglaUsuarioAtribuicao(),
                                $atividadeDTO->getStrSiglaOrgaoUsuarioAtribuicao()
                            );
                            $notificacaoDTO->setStrIdentificadorUsuario($identificador);
                            $notificacaoDTO->setBolNotificar(true);
                            $notificacaoDTO->setStrUrlServicoNotificacao($UrlServicoNotificacao);
                            $notificacaoDTO->setNumIdApp($IdApp);
                            $notificacaoDTO->setStrChaveAutorizacao($ChaveAutorizacao);

                            $notificacaoRN = new MdWsSeiNotificacaoRN();
                            /** Realiza a chamada da classe de notifica��o **/
                            if(!$notificacaoRN->notificar($notificacaoDTO)){
                                throw new Exception('FALHA AO ENVIAR NOTIFICA��O AO SERVI�O DE MENSAGERIA.');
                            };
                            $notificacaoAtividadeDTO = new MdWsSeiNotificacaoAtividadeDTO();
                            $notificacaoAtividadeDTO->setNumIdAtividade($atividadeDTO->getNumIdAtividade());
                            $notificacaoAtividadeDTO->setDthNotificacao(InfraData::getStrDataHoraAtual());
                            $notificacaoAtividadeDTO->setStrTitulo($titulo);
                            $notificacaoAtividadeDTO->setStrMensagem($mensagem);
                            /** Realiza o Cadastro da notifica��o para controle de atividades notificadas **/
                            $notificacaoAtividadeRN->cadastrar($notificacaoAtividadeDTO);
                            InfraDebug::getInstance()->gravar('NOTIFICA��O DA ATIVIDADE ID: '.$atividadeDTO->getNumIdAtividade().' PARA IDENTIFICADOR: ['.$identificador.'] REALIZADA COM SUCESSO!');
                        }catch (Exception $e) {
                            $bolErro = true;
                            InfraDebug::getInstance()->gravar('ERRO AO REALIZAR NOTIFICA��O PARA O ID USU�RIO: '.$atividadeDTO->getNumIdUsuarioAtribuicao(). ', ATIVIDADE: '.$atividadeDTO->getNumIdAtividade().'.');
                            InfraDebug::getInstance()->gravar('MENSAGEM DE ERRO: '.$e->getMessage());
                        }
                    }
                    if($bolErro){
                        InfraDebug::getInstance()->gravar('ERRO AO NOTIFICAR UM OU MAIS USU�RIO(S).');
                    }
                }
            }else{
                InfraDebug::getInstance()->gravar('NENHUMA ATIVIDADE AGUARDANDO NOTIFICA��O.');
            }
            $numSeg = InfraUtil::verificarTempoProcessamento($numSeg);
            InfraDebug::getInstance()->gravar('TEMPO TOTAL DE EXECUCAO: ' . $numSeg . ' s');
            InfraDebug::getInstance()->gravar('FIM');

            LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug(), InfraLog::$INFORMACAO);

        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);

            throw new InfraException('Erro ao executar atualiza��o de um ou mais estados de agendamentos.', $e);
        }
    }

}

?>

