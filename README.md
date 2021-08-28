# Módulo PagSeguro Transparente para Magento 2
## Com descontos nas taxas oficiais - A integração PagSeguro mais usada no Brasil
![Screenshot do módulo](https://i.imgur.com/ww2UhaP.jpg)

### Principais recursos
* Aceite todos os Cartões de Crédito com cálculo automático de parcelamento
* Aceite pagamentos com Boleto, TEF ou PIX (via "Pagar no PagSeguro")
* Exiba o parcelamento na página de produtos
* Retorno e atualização automática de status
* Economize pelo menos 0,84% + R$0,40 nas taxas oficiais do PagSeguro ([saiba mais](https://pagseguro.ricardomartins.net.br/compare.html)).

### Pré-requisitos
* Magento 2.3 ou superior
* PHP 7.x

### Site Oficial do Módulo
[https://pagseguro.ricardomartins.net.br/](https://pagseguro.ricardomartins.net.br/?utm_source=github&utm_medium=readme&utm_campaign=readme.md)

Disponível também para [Magento 1.x](https://pagseguro.ricardomartins.net.br/magento1.html?utm_source=github&utm_medium=readme&utm_campaign=readme.md) e [WooCommerce](https://pagseguro.ricardomartins.net.br/woocommerce.html?utm_source=github&utm_medium=readme&utm_campaign=readme.md)

### Central de ajuda e suporte
https://pagsegurotransparente.zendesk.com/hc/pt-br/

### Cursos Magento
No [Magenteiro.com/cursos](https://www.magenteiro.com/cursos?___store=default&utm_source=github&utm_medium=readme&utm_campaign=readme.md) você encontra uma dezena de cursos pagos e gratuitos sobre Magento, incluindo o famoso curso [Instalando e Configurando uma loja Magento 2 para o Brasil](https://www.magenteiro.com/magento2brasil?___store=default&utm_source=github&utm_medium=readme&utm_campaign=readme.md). 

## Instalação
#### 1. Autorize sua loja 
Acesse [https://pagseguro.ricardomartins.net.br/magento2/wizard.html](https://pagseguro.ricardomartins.net.br/magento2/wizard.html?utm_source=github&utm_medium=readme&utm_campaign=readme.md) e autorize sua loja PagSeguro.

#### 2. Instale o módulo via composer
 
    composer require ricardomartins/pagseguro
    bin/magento cache:clean
    bin/magento setup:upgrade
    bin/magento setup:di:compile

Se preferir, faça a [instalação manual](https://pagsegurotransparente.zendesk.com/hc/pt-br/articles/360031292751-Magento-2-Como-fazer-a-instala%C3%A7%C3%A3o-manual-do-m%C3%B3dulo-) copiando os arquivos.

#### 3. Configure o Magento

* Altere a quantidade de linhas de endereço em Lojas->Configurações->Clientes->Configurações->Nome e opções de endereço->Número de linhas no endereço.
Altere para 4 linhas.

* Em Formas de Pagamento, configure o e-mail da conta PagSeguro, Token PagSeguro e Public Key obtida no passo 1.

* Em _Stores > Order Status_, configure a loja para exibir pedidos com status Pagamento Pendente no frontend (opcional). [Saiba mais](https://pagsegurotransparente.zendesk.com/hc/pt-br/articles/360029981831).

* Limpe o cache, e pronto!


Se preferir, assista o passo a passo de instalação:

[![ASSISTA O PASSO A PASSO DE INSTALAÇÃO](https://img.youtube.com/vi/DQJ3W9Qfn58/0.jpg)](https://www.youtube.com/watch?v=DQJ3W9Qfn58)


### Bugs?
https://github.com/r-martins/PagSeguro-Magento-Transparente-M2/issues

### Autor
Ricardo Martins / [Magenteiro.com](https://www.magenteiro.com/?___store=default) / [Contribuidores especiais](https://github.com/r-martins/PagSeguro-Magento-Transparente-M2/pulls?utf8=%E2%9C%93&q=is%3Apr+is%3Amerged)
