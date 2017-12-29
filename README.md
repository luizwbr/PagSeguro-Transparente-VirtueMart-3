# PagSeguro-Transparente-VirtueMart-3
Plugin de pagamento integrado ao webservice do PagSeguro, compatível com VirtueMart.

------------------------
* Virtuemart 3.x / Joomla 3.x

Tutorial
-------

*Passo-a-passo Instalação Joomla*

* 1 - Instale o plugin via Gerenciador de Extensões do Joomla

Ao instalar irá aparecer esta tela:

![passo 1](http://weber.eti.br/images/easyblog_images/91/b2ap3_thumbnail_pagseguro_transparente.png)

* 2 - Habilite o plugin em Administrar Plugins

![passo 2](http://weber.eti.br/images/easyblog_images/91/b2ap3_thumbnail_passo2.png)

*Passo-a-Passo Instalação VirtueMart 2*

* 1 - Clique em Novo Método de pagamento

![passo 2](http://weber.eti.br/images/easyblog_images/62/cielo/b2ap3_thumbnail_novo_metodo_pagamento.png)

* 2 - Preencha as informações:

* Nome do Pagamento: Cartões de crédito, boleto bancário e transferência ( PagSeguro )

* Publicado: Sim

* Descrição do pagamento: Pague com cartão de crédito

* Método de pagamento: PagSeguro Transparente

* Grupo de Compradores: -default- e -anonymous- ou nenhum grupo de compradores

![passo 2](http://weber.eti.br/images/easyblog_images/91/b2ap3_thumbnail_passo3.png)

* 2.1 - Clique em Salvar

* 3 - Na aba configurações, preencha com os dados para integrar ao sistema.

![passo 3](http://weber.eti.br/images/easyblog_images/91/b2ap3_thumbnail_passo4.png)

* Logotipos ( os logotipos ficam salvos na pasta /images/stories/virtuemart/payment/ )

* Modo de teste ( sim ou não ). 
Não para colocar o sistema em modo produção, e sim para testar o método de pagamento.

Teste

* E-mail

* Token

Produção

* E-mail

* Token

* Valor mínimo

* Máx. Parcelas Sem Juros

* Máx. Parcelas Total

* Tipo Parcelamento Juros ( Cliente ou Loja )

* Tipo da Autorização

* Capturar Transação ou não

* Taxa Crédito à Vista

* Taxa Parcelado

* Taxa Débito

* Aprovado: Status do Pedido quando Aprovada a transação

* Cancelado: Status do Pedido quando Cancelada a transação

* Aguardando Pagto: Status do Pedido quando transação Pendente

* Cartões aceitos na loja ( configure quais serão exibidos )

* Boleto Bancário ( configure se será exibido ou não )

* Transferência Bancária ( configure se será exibido ou não )

4 - Pronto, o plugin está configurado para testes.

Requisitos mínimos
----------

* CURL SSL
* DomDocument ou SimpleXML
* JSON
* Bibliotecas php: bcmath e intl

Agradecimentos
-------

Redesign da tela de pagamentos com cartão, feito pelo @edrdesigner

Licença
-------

Copyright Luiz Felipe Weber.

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.

Dúvidas
----------

https://github.com/luizwbr/PagSeguro-Transparente-VirtueMart-3/issues

Contribuições
-------------

Achou e corrigiu um bug ou tem alguma feature em mente e deseja contribuir?

* Faça um fork
* Adicione sua feature ou correção de bug (git checkout -b my-new-feature)
* Commit suas mudanças (git commit -am 'Added some feature')
* Rode um push para o branch (git push origin my-new-feature)
* Envie um Pull Request
* Obs.: Adicione exemplos para sua nova feature. Se seu Pull Request for relacionado a uma versão específica, o Pull Request não deve ser enviado para o branch master e sim para o branch correspondente a versão.

Atenção
-------------

- O desenvolvedor não possui relação com a empresa PagSeguro, o plugin tem por objetivo a otimização dos recursos para e-commerce. 
- O desenvolvedor apenas oferece a integração por meio de código aberto, livre para alterações de qualquer natureza. 
- O desenvolvedor não autoriza qualquer comercialização não-autorizada deste plugin.

